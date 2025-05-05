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

namespace pocketmine\scheduler;

use pmmp\thread\Thread as NativeThread;
use pmmp\thread\ThreadSafeArray;
use pocketmine\event\Timings;
use pocketmine\Server;

class AsyncPool{

	private const WORKER_START_OPTIONS = NativeThread::INHERIT_INI | NativeThread::INHERIT_CONSTANTS;

	/** @var AsyncTask[] */
	private array $tasks = [];
	/** @var int[] */
	private array $taskWorkers = [];

	/** @var AsyncWorker[] */
	private array $workers = [];
	/** @var int[] */
	private array $workerUsage = [];
	private array $workerLastUsed = [];
	/** @var \Closure[] */
	private $workerStartHooks = [];

	public function __construct(
		private Server $server,
		protected int $size,
		protected int $workerMemoryLimit
	){
		for($i = 0; $i < $this->size; ++$i){
			$this->workerUsage[$i] = 0;
			$this->workers[$i] = new AsyncWorker($this->server->getLogger(), $i + 1);
			$this->workers[$i]->setClassLoaders();
			$this->workers[$i]->start();
		}
	}

	/**
	 * Registers a Closure callback to be fired whenever a new worker is started by the pool.
	 * The signature should be `function(int $worker) : void`
	 *
	 * This function will call the hook for every already-running worker.
	 *
	 * @param \Closure $hook
	 */
	public function addWorkerStartHook(\Closure $hook) : void{
		Utils::validateCallableSignature(function(int $worker) : void{}, $hook);
		$this->workerStartHooks[spl_object_id($hook)] = $hook;
		foreach($this->workers as $i => $worker){
			$hook($i);
		}
	}

	/**
	 * Removes a previously-registered callback listening for workers being started.
	 *
	 * @param \Closure $hook
	 */
	public function removeWorkerStartHook(\Closure $hook) : void{
		unset($this->workerStartHooks[spl_object_id($hook)]);
	}

	public function getSize() : int{
		return $this->size;
	}

	public function increaseSize(int $newSize){
		if($newSize > $this->size){
			for($i = $this->size; $i < $newSize; ++$i){
				$this->workerUsage[$i] = 0;
				$this->workers[$i] = new AsyncWorker($this->server->getLogger(), $i + 1);
				$this->workers[$i]->setClassLoaders();
				$this->workers[$i]->start();
			}
			$this->size = $newSize;
		}
	}

	/**
	 * Fetches the worker with the specified ID, starting it if it does not exist, and firing any registered worker
	 * start hooks.
	 *
	 * @param int $worker
	 *
	 * @return AsyncWorker
	 */
	private function getWorker(int $worker) : AsyncWorker{
		if(!isset($this->workers[$worker])){
			$this->workerUsage[$worker] = 0;
			$this->workers[$worker] = new AsyncWorker($this->server->getLogger(), $worker, $this->workerMemoryLimit);
			$this->workers[$worker]->setClassLoaders();
			$this->workers[$worker]->start(self::WORKER_START_OPTIONS);
			foreach($this->workerStartHooks as $hook){
				$hook($worker);
			}
		}
		return $this->workers[$worker];
	}

	public function submitTaskToWorker(AsyncTask $task, int $worker){
		if(isset($this->tasks[$task->getTaskId()]) or $task->isGarbage()){
			return;
		}

		if($worker < 0 or $worker >= $this->size){
			throw new \InvalidArgumentException("Invalid worker $worker");
		}

		$task->progressUpdates = new ThreadSafeArray;
		$task->worker = ($workerInstance = $this->getWorker($worker));
		$task->workerId = $worker;
		$this->tasks[$task->getTaskId()] = $task;

		$workerInstance->stack($task);
		$this->workerUsage[$worker]++;
		$this->taskWorkers[$task->getTaskId()] = $worker;
		$this->workerLastUsed[$worker] = time();
	}

	public function submitTask(AsyncTask $task){
		if(isset($this->tasks[$task->getTaskId()]) or $task->isGarbage()){
			return;
		}

		$worker = $this->selectWorker();
		$this->submitTaskToWorker($task, $worker);
		return $worker;
	}

	public function shutdownUnusedWorkers() : int{
		$time = time();

		$ret = 0;
		foreach($this->workerUsage as $i => $usage){
			if($usage === 0 and (!isset($this->workerLastUsed[$i]) or $this->workerLastUsed[$i] + 300 < $time)){
				$this->workers[$i]->quit();
				unset($this->workers[$i], $this->workerUsage[$i], $this->workerLastUsed[$i]);
				$ret++;
			}
		}
		return $ret;
	}

	public function selectWorker() : int{
		$worker = null;
		$minUsage = PHP_INT_MAX;
		foreach($this->workerUsage as $i => $usage){
			if($usage < $minUsage){
				$worker = $i;
				$minUsage = $usage;
				if($usage === 0){
					break;
				}
			}
		}
		if($worker === null or ($minUsage > 0 and count($this->workers) < $this->size)){
			//select a worker to start on the fly
			for($i = 0; $i < $this->size; ++$i){
				if(!isset($this->workers[$i])){
					$worker = $i;
					break;
				}
			}
		}

		assert($worker !== null);
		return $worker;
	}

	/*
	* @return int[]
	*/
   public function getRunningWorkers() : array{
	   return array_keys($this->workers);
   }

	private function removeTask(AsyncTask $task, bool $force = false){
		if(isset($this->taskWorkers[$task->getTaskId()])){
			if(!$force and ($task->isRunning() or !$task->isGarbage())){
				return;
			}
			$this->workerUsage[$this->taskWorkers[$task->getTaskId()]]--;
		}

		unset($this->tasks[$task->getTaskId()]);
		unset($this->taskWorkers[$task->getTaskId()]);

		$task->cleanObject();
	}

	public function removeTasks(){
		do{
			foreach($this->tasks as $task){
				$task->cancelRun();
				$this->removeTask($task);
			}

			if(count($this->tasks) > 0){
				Server::microSleep(25000);
			}
		}while(count($this->tasks) > 0);

		for($i = 0; $i < $this->size; ++$i){
			$this->workerUsage[$i] = 0;
		}

		$this->taskWorkers = [];
		$this->tasks = [];

		$this->collectWorkers();
	}

	private function collectWorkers(){
		foreach($this->workers as $worker){
			$worker->collect();
		}
	}

	public function collectTasks(){
		Timings::$schedulerAsyncTimer->startTiming();

		foreach($this->tasks as $task){
			if(!$task->isGarbage()){
				$task->checkProgressUpdates($this->server);
			}
			if($task->isGarbage() and !$task->isRunning() and !$task->isCrashed()){
				if(!$task->hasCancelledRun()){
					$task->onCompletion($this->server);
					$this->server->getScheduler()->removeLocalComplex($task);
				}

				$this->removeTask($task);
			}elseif($task->isTerminated() or $task->isCrashed()){
				$this->server->getLogger()->critical("Could not execute asynchronous task " . (new \ReflectionClass($task))->getShortName() . ": Task crashed");
				$this->removeTask($task, true);
			}
		}

		$this->collectWorkers();

		Timings::$schedulerAsyncTimer->stopTiming();
	}
	public function shutdown() : void{
		$this->collectTasks();
		$this->removeTasks();
		foreach($this->workers as $worker){
			$worker->quit();
		}
		$this->workers = [];
		$this->workerLastUsed = [];
	}
}

