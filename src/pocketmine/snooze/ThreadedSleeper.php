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
 * Notifiable Threaded class which tracks counts of notifications it receives.
 */
class ThreadedSleeper extends ThreadSafe{
	/**
	 * @var int
	 */
	private $notifCount = 0;

	/**
	 * Called from the main thread to wait for notifications, or until timeout.
	 *
	 * @param int $timeout defaults to 0 (no timeout, wait indefinitely)
	 */
	public function sleep(int $timeout = 0) : void{
		$this->synchronized(function(int $timeout) : void{
			assert($this->notifCount >= 0, "notification count should be >= 0, got $this->notifCount");
			if($this->notifCount === 0){
				$this->wait($timeout);
			}
		}, $timeout);
	}

	/**
	 * Call this from sleeper notifiers to wake up the main thread.
	 */
	public function wakeup() : void{
		$this->synchronized(function(){
			++$this->notifCount;
			$this->notify();
		});
	}

	/**
	 * Decreases pending notification count by the given number.
	 *
	 * @param int $notifCount
	 */
	public function clearNotifications(int $notifCount) : void{
		$this->synchronized(function() use ($notifCount) : void{
			/*
			child threads can flag themselves as having a notification, which can get detected while the server is
			awake. In these cases it's possible for the notification count to drop below zero due to getting
			decremented here before incrementing on the child thread. This is quite a psychotic edge case, but it
			means that it's necessary to synchronize for this, even though it's a simple statement.
			*/
			$this->notifCount -= $notifCount;
			assert($this->notifCount >= 0, "notification count should be >= 0, got $this->notifCount");
		});
	}

	public function hasNotifications() : bool{
		//don't need to synchronize here, pthreads automatically locks/unlocks
		return $this->notifCount > 0;
	}
}


