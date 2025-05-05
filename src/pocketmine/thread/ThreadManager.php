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

namespace pocketmine\thread;

use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;
use function spl_object_id;

class ThreadManager extends ThreadSafe{

	private static ?self $instance = null;

	/**
	* @phpstan-var ThreadSafeArray<int, Thread|Worker>
	*/
	private ThreadSafeArray $threads;

	private function __construct(){
		$this->threads = new ThreadSafeArray();
	}

	public static function init() : void{
		self::$instance = new ThreadManager();
	}

	public static function getInstance() : ThreadManager{
		if(self::$instance === null){
			self::$instance = new ThreadManager();
		}
		return self::$instance;
	}

	/**
	 * @param Worker|Thread $thread
	 */
	public function add(Worker|Thread $thread) : void{
		$this->threads[spl_object_id($thread)] = $thread;
	}

	/**
	 * @param Worker|Thread $thread
	 */
	public function remove(Worker|Thread $thread) : void{
		unset($this->threads[spl_object_id($thread)]);
	}

	/**
	 * @return Worker[]|Thread[]
	 */
	public function getAll() : array{
		$array = [];
		/**
		 * @var Worker|Thread $thread
		 */
		foreach($this->threads as $key => $thread){
			$array[$key] = $thread;
		}

		return $array;
	}

	public function stopAll() : int{
		$logger = \GlobalLogger::get();

		$erroredThreads = 0;

		foreach($this->getAll() as $thread){
			$logger->debug("Stopping " . $thread->getThreadName() . " thread");
			try{
				$thread->quit();
				$logger->debug($thread->getThreadName() . " thread stopped successfully.");
			}catch(ThreadException $e){
				++$erroredThreads;
				$logger->debug("Could not stop " . $thread->getThreadName() . " thread: " . $e->getMessage());
			}
		}

		return $erroredThreads;
	}
}


