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

/**
 * WARNING! Plugins that create tasks MUST extend PluginTask
 */
abstract class Task{

	/** @var TaskHandler */
	private $taskHandler = null;

	/**
	 * @return TaskHandler|null
	 */
	final public function getHandler(){
		return $this->taskHandler;
	}

	/**
	 * @return int
	 */
	final public function getTaskId() : int{
		if($this->taskHandler !== null){
			return $this->taskHandler->getTaskId();
		}

		return -1;
	}

	/**
	 * @param TaskHandler|null $taskHandler
	 */
	final public function setHandler(TaskHandler $taskHandler = null){
		if($this->taskHandler === null or $taskHandler === null){
			$this->taskHandler = $taskHandler;
		}
	}

	/**
	 * Actions to execute when run
	 *
	 * @param int $currentTick
	 *
	 * @return void
	 */
	abstract public function onRun(int $currentTick);

	/**
	 * Actions to execute if the Task is cancelled
	 */
	public function onCancel(){

	}

}


