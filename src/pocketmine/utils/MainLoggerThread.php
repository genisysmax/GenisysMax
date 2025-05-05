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

namespace pocketmine\utils;

use pmmp\thread\Thread;
use pmmp\thread\ThreadSafeArray;
use function fclose;
use function fopen;
use function fwrite;
use function is_resource;
use function touch;

final class MainLoggerThread extends Thread{
	/** @phpstan-var ThreadSafeArray<int, string> */
	private ThreadSafeArray $buffer;
	private bool $syncFlush = false;
	private bool $shutdown = false;

	public function __construct(
		private string $logFile
	){
		$this->buffer = new ThreadSafeArray();
		touch($this->logFile);
	}

	public function write(string $line) : void{
		$this->synchronized(function() use ($line) : void{
			$this->buffer[] = $line;
			$this->notify();
		});
	}

	public function syncFlushBuffer() : void{
		$this->synchronized(function() : void{
			$this->syncFlush = true;
			$this->notify(); //write immediately
		});
		$this->synchronized(function() : void{
			while($this->syncFlush){
				$this->wait(); //block until it's all been written to disk
			}
		});
	}

	public function shutdown() : void{
		$this->synchronized(function() : void{
			$this->shutdown = true;
			$this->notify();
		});
		$this->join();
	}

	/**
	 * @param resource $logResource
	 */
	private function writeLogStream($logResource) : void{
		while(($chunk = $this->buffer->shift()) !== null){
			fwrite($logResource, $chunk);
		}

		$this->synchronized(function() : void{
			if($this->syncFlush){
				$this->syncFlush = false;
				$this->notify(); //if this was due to a sync flush, tell the caller to stop waiting
			}
		});
	}

	public function run() : void{
		$logResource = fopen($this->logFile, "ab");
		if(!is_resource($logResource)){
			throw new \RuntimeException("Couldn't open log file");
		}

		while(!$this->shutdown){
			$this->writeLogStream($logResource);
			$this->synchronized(function() : void{
				if(!$this->shutdown && !$this->syncFlush){
					$this->wait();
				}
			});
		}

		$this->writeLogStream($logResource);

		fclose($logResource);
	}
}

