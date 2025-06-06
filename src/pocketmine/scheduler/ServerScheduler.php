<?php

/*
 *
 *    _____            _               __  __            
 *   / ____|          (_)             |  \/  |           
 *  | |  __  ___ _ __  _ ___ _   _ ___| \  / | __ ___  __
 *  | | |_ |/ _ \ '_ \| / __| | | / __| |\/| |/ _` \ \/ /
 *  | |__| |  __/ | | | \__ \ |_| \__ \ |  | | (_| |>  < 
 *   \_____|\___|_| |_|_|___/\__, |___/_|  |_|\__,_/_/\_\
 *                            __/ |                      
 *                           |___/                       
 *
 * This program is licensed under the GPLv3 license.
 * You are free to modify and redistribute it under the same license.
 *
 * @author LINUXOV
 * @link vk.com/linuxof
 *
*/


declare(strict_types=1);

/**
 * Task scheduling related classes
 */
namespace pocketmine\scheduler;

use pmmp\thread\ThreadSafeArray;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginException;
use pocketmine\Server;
use pocketmine\utils\ReversePriorityQueue;

class ServerScheduler{
	public static $WORKERS = 2;
	/**
	 * @var ReversePriorityQueue<Task>
	 */
	protected $queue;

	/**
	 * @var TaskHandler[]
	 */
	protected $tasks = [];

	/** @var AsyncPool */
	protected $asyncPool;

	/** @var int */
	private $ids = 1;

	/** @var int */
	protected $currentTick = 0;

	/** @var \SplObjectStorage<AsyncTask, object|array> */
	protected $objectStore;

	public function __construct(){
		$this->queue = new ReversePriorityQueue();
		$this->asyncPool = new AsyncPool(Server::getInstance(), self::$WORKERS, (int) max(-1, (int) Server::getInstance()->getProperty("memory.async-worker-hard-limit", 256)));
		$this->objectStore = new \SplObjectStorage();
	}

	/**
	 * @param Task $task
	 *
	 * @return null|TaskHandler
	 */
	public function scheduleTask(Task $task){
		return $this->addTask($task, -1, -1);
	}

	public function getAsyncPool() : AsyncPool{
		return $this->asyncPool;
	}

	/**
	 * Submits an asynchronous task to the Worker Pool
	 *
	 * @param AsyncTask $task
	 *
	 * @return void
	 */
	public function scheduleAsyncTask(AsyncTask $task){
		if($task->getTaskId() !== null){
			throw new \UnexpectedValueException("Attempt to schedule the same AsyncTask instance twice");
		}
		$id = $this->nextId();
		$task->setTaskId($id);
		$task->progressUpdates = new ThreadSafeArray;
		$this->asyncPool->submitTask($task);
	}

	/**
	 * Submits an asynchronous task to a specific Worker in the Pool
	 *
	 * @param AsyncTask $task
	 * @param int       $worker
	 *
	 * @return void
	 */
	public function scheduleAsyncTaskToWorker(AsyncTask $task, int $worker){
		if($task->getTaskId() !== null){
			throw new \UnexpectedValueException("Attempt to schedule the same AsyncTask instance twice");
		}
		$id = $this->nextId();
		$task->setTaskId($id);
		$task->progressUpdates = new ThreadSafeArray;
		$this->asyncPool->submitTaskToWorker($task, $worker);
	}

	/**
	 * Stores any data that must not be passed to other threads or be serialized
	 *
	 * @internal Only call from AsyncTask.php
	 *
	 * @param AsyncTask    $for
	 * @param object|array $cmplx
	 *
	 * @throws \RuntimeException if this method is called twice for the same instance of AsyncTask
	 */
	public function storeLocalComplex(AsyncTask $for, $cmplx){
		if(isset($this->objectStore[$for])){
			throw new \RuntimeException("Already storing a complex for this AsyncTask");
		}
		$this->objectStore[$for] = $cmplx;
	}

	/**
	 * Fetches data that must not be passed to other threads or be serialized, previously stored with
	 * {@link ServerScheduler#storeLocalComplex}, without deletion of the data.
	 *
	 * @internal Only call from AsyncTask.php
	 *
	 * @param AsyncTask $for
	 *
	 * @return object|array
	 *
	 * @throws \RuntimeException if no data associated with this AsyncTask can be found
	 */
	public function peekLocalComplex(AsyncTask $for){
		if(!isset($this->objectStore[$for])){
			throw new \RuntimeException("No local complex stored for this AsyncTask");
		}
		return $this->objectStore[$for];
	}

	/**
	 * Fetches data that must not be passed to other threads or be serialized, previously stored with
	 * {@link ServerScheduler#storeLocalComplex}, and delete the data from the storage.
	 *
	 * @internal Only call from AsyncTask.php
	 *
	 * @param AsyncTask $for
	 *
	 * @return object|array
	 *
	 * @throws \RuntimeException if no data associated with this AsyncTask can be found
	 */
	public function fetchLocalComplex(AsyncTask $for){
		if(!isset($this->objectStore[$for])){
			throw new \RuntimeException("No local complex stored for this AsyncTask");
		}
		$cmplx = $this->objectStore[$for];
		unset($this->objectStore[$for]);
		return $cmplx;
	}

	/**
	 * Makes sure no data stored from {@link #storeLocalComplex} is left for a specific AsyncTask
	 *
	 * @internal Only call from AsyncTask.php
	 *
	 * @param AsyncTask $for
	 *
	 * @return bool returns false if any data are removed from this call, true otherwise
	 */
	public function removeLocalComplex(AsyncTask $for) : bool{
		if(isset($this->objectStore[$for])){
			Server::getInstance()->getLogger()->notice("AsyncTask " . get_class($for) . " stored local complex data but did not remove them after completion");
			unset($this->objectStore[$for]);
			return false;
		}
		return true;
	}

	public function getAsyncTaskPoolSize() : int{
		return $this->asyncPool->getSize();
	}

	public function increaseAsyncTaskPoolSize(int $newSize){
		$this->asyncPool->increaseSize($newSize);
	}

	/**
	 * @param Task $task
	 * @param int  $delay
	 *
	 * @return null|TaskHandler
	 */
	public function scheduleDelayedTask(Task $task, int $delay){
		return $this->addTask($task, $delay, -1);
	}

	/**
	 * @param Task $task
	 * @param int  $period
	 *
	 * @return null|TaskHandler
	 */
	public function scheduleRepeatingTask(Task $task, int $period){
		return $this->addTask($task, -1, $period);
	}

	/**
	 * @param Task $task
	 * @param int  $delay
	 * @param int  $period
	 *
	 * @return null|TaskHandler
	 */
	public function scheduleDelayedRepeatingTask(Task $task, int $delay, int $period){
		return $this->addTask($task, $delay, $period);
	}

	public function shutdown() : void{
	    $this->cancelAllTasks();
	    $this->asyncPool->shutdown();
	}

	/**
	 * @param int $taskId
	 */
	public function cancelTask(int $taskId){
		if($taskId !== null and isset($this->tasks[$taskId])){
			$this->tasks[$taskId]->cancel();
			unset($this->tasks[$taskId]);
		}
	}

	/**
	 * @param Plugin $plugin
	 */
	public function cancelTasks(Plugin $plugin){
		foreach($this->tasks as $taskId => $task){
			$ptask = $task->getTask();
			if($ptask instanceof PluginTask and $ptask->getOwner() === $plugin){
				$task->cancel();
				unset($this->tasks[$taskId]);
			}
		}
	}

	public function cancelAllTasks() : void{
		foreach($this->tasks as $task){
			$task->cancel();
		}
		$this->tasks = [];
		$this->asyncPool->removeTasks();
		while(!$this->queue->isEmpty()){
			$this->queue->extract();
		}
		$this->ids = 1;
	}

	/**
	 * @param int $taskId
	 *
	 * @return bool
	 */
	public function isQueued(int $taskId) : bool{
		return isset($this->tasks[$taskId]);
	}

	/**
	 * @param Task $task
	 * @param int  $delay
	 * @param int  $period
	 *
	 * @return null|TaskHandler
	 *
	 * @throws PluginException
	 */
	private function addTask(Task $task, int $delay, int $period){
		if($task instanceof PluginTask){
			if(!($task->getOwner() instanceof Plugin)){
				throw new PluginException("Invalid owner of PluginTask " . get_class($task));
			}elseif(!$task->getOwner()->isEnabled()){
				throw new PluginException("Plugin '" . $task->getOwner()->getName() . "' attempted to register a task while disabled");
			}
		}

		if($delay <= 0){
			$delay = -1;
		}

		if($period <= -1){
			$period = -1;
		}elseif($period < 1){
			$period = 1;
		}

		return $this->handle(new TaskHandler(get_class($task), $task, $this->nextId(), $delay, $period));
	}

	private function handle(TaskHandler $handler) : TaskHandler{
		if($handler->isDelayed()){
			$nextRun = $this->currentTick + $handler->getDelay();
		}else{
			$nextRun = $this->currentTick;
		}

		$handler->setNextRun($nextRun);
		$this->tasks[$handler->getTaskId()] = $handler;
		$this->queue->insert($handler, $nextRun);

		return $handler;
	}

	/**
	 * @param int $currentTick
	 */
	public function mainThreadHeartbeat(int $currentTick){
		$this->currentTick = $currentTick;
		while($this->isReady($this->currentTick)){
			/** @var TaskHandler $task */
			$task = $this->queue->extract();
			if($task->isCancelled()){
				unset($this->tasks[$task->getTaskId()]);
				continue;
			}else{
				$task->timings->startTiming();
				try{
					$task->run($this->currentTick);
				}catch(\Throwable $e){
					Server::getInstance()->getLogger()->critical("Could not execute task " . $task->getTaskName() . ": " . $e->getMessage());
					Server::getInstance()->getLogger()->logException($e);
				}
				$task->timings->stopTiming();
			}
			if($task->isRepeating()){
				$task->setNextRun($this->currentTick + $task->getPeriod());
				$this->queue->insert($task, $this->currentTick + $task->getPeriod());
			}else{
				$task->remove();
				unset($this->tasks[$task->getTaskId()]);
			}
		}

		$this->asyncPool->collectTasks();
	}

	private function isReady(int $currentTicks) : bool{
		return count($this->tasks) > 0 and $this->queue->current()->getNextRun() <= $currentTicks;
	}

	/**
	 * @return int
	 */
	private function nextId() : int{
		return $this->ids++;
	}

}

