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

use pocketmine\event\Timings;
use pocketmine\event\TimingsHandler;
use pocketmine\utils\MainLogger;
use function get_class;

class TaskHandler{

	/** @var Task */
	protected $task;

	/** @var int */
	protected $taskId;

	/** @var int */
	protected $delay;

	/** @var int */
	protected $period;

	/** @var int */
	protected $nextRun;

	/** @var bool */
	protected $cancelled = false;

	/** @var TimingsHandler */
	public $timings;

	public $timingName = null;

	/**
	 * @param string $timingName
	 * @param Task   $task
	 * @param int    $taskId
	 * @param int    $delay
	 * @param int    $period
	 */
	public function __construct(string $timingName, Task $task, int $taskId, int $delay = -1, int $period = -1){
		$this->task = $task;
		$this->taskId = $taskId;
		$this->delay = $delay;
		$this->period = $period;
		$this->timingName = $timingName ?? "Unknown";
		$this->timings = Timings::getPluginTaskTimings($this, $period);
		$this->task->setHandler($this);
	}

	/**
	 * @return bool
	 */
	public function isCancelled() : bool{
		return $this->cancelled === true;
	}

	/**
	 * @return int
	 */
	public function getNextRun() : int{
		return $this->nextRun;
	}

	/**
	 * @param int $ticks
	 */
	public function setNextRun(int $ticks){
		$this->nextRun = $ticks;
	}

	/**
	 * @return int
	 */
	public function getTaskId() : int{
		return $this->taskId;
	}

	/**
	 * @return Task
	 */
	public function getTask() : Task{
		return $this->task;
	}

	/**
	 * @return int
	 */
	public function getDelay() : int{
		return $this->delay;
	}

	/**
	 * @return bool
	 */
	public function isDelayed() : bool{
		return $this->delay > 0;
	}

	/**
	 * @return bool
	 */
	public function isRepeating() : bool{
		return $this->period > 0;
	}

	/**
	 * @return int
	 */
	public function getPeriod() : int{
		return $this->period;
	}

	/**
	 * WARNING: Do not use this, it's only for internal use.
	 * Changes to this function won't be recorded on the version.
	 */
	public function cancel(){
		try{
			if(!$this->isCancelled()){
				$this->task->onCancel();
			}
		}catch(\Throwable $e){
			MainLogger::getLogger()->logException($e);
		}finally{
			$this->remove();
		}
	}

	public function remove(){
		$this->cancelled = true;
		$this->task->setHandler(null);
	}

	/**
	 * @param int $currentTick
	 */
	public function run(int $currentTick){
		$this->task->onRun($currentTick);
	}

	/**
	 * @return string
	 */
	public function getTaskName() : string{
		if($this->timingName !== null){
			return $this->timingName;
		}

		return get_class($this->task);
	}
}


