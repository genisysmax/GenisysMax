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

namespace pocketmine\snooze;

use pmmp\thread\ThreadSafe;
use function assert;

/**
 * Notifiers are Threaded objects which can be attached to threaded sleepers in order to wake them up. They also record
 * state so that the main thread handler can determine which notifier woke up the sleeper.
 */
class SleeperNotifier extends ThreadSafe{
	/** @var ThreadedSleeper */
	private $threadedSleeper;

	/** @var int */
	private $sleeperId;

	/** @var bool */
	private $notification = false;

	final public function attachSleeper(ThreadedSleeper $sleeper, int $id) : void{
		$this->threadedSleeper = $sleeper;
		$this->sleeperId = $id;
	}

	final public function getSleeperId() : int{
		return $this->sleeperId;
	}

	/**
	 * Call this method from other threads to wake up the main server thread.
	 */
	final public function wakeupSleeper() : void{
		assert($this->threadedSleeper !== null);

		$this->synchronized(function() : void{
			if(!$this->notification){
				$this->notification = true;

				$this->threadedSleeper->wakeup();
			}
		});
	}

	final public function hasNotification() : bool{
		return $this->notification;
	}

	final public function clearNotification() : void{
		$this->synchronized(function() : void{
			//this has to be synchronized to avoid races with waking up
			$this->notification = false;
		});
	}
}


