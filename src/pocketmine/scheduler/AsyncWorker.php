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

use pocketmine\thread\log\ThreadSafeLogger;
use pocketmine\thread\Worker;

class AsyncWorker extends Worker{

	private ThreadSafeLogger $logger;
	private int $id;

	public function __construct(ThreadSafeLogger $logger, int $id){
		$this->logger = $logger;
		$this->id = $id;
	}

	public function onRun() : void{
		$this->registerClassLoaders();
		\GlobalLogger::set($this->logger);

		gc_enable();
		ini_set("memory_limit", '-1');

		global $store;
		$store = [];
	}

	public function handleException(\Throwable $e){
		parent::onUncaughtException($e);
		$this->logger->logException($e);
	}

	public function getLogger() : ThreadSafeLogger{
		return $this->logger;
	}

	public function getThreadName() : string{
		return "Asynchronous Worker #" . $this->id;
	}
}

