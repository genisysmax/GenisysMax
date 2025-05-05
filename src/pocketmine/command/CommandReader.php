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

namespace pocketmine\command;

use pmmp\thread\ThreadSafeArray;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\thread\Thread;
use pocketmine\thread\ThreadException;
use pocketmine\utils\OS;
use pocketmine\utils\Utils;

class CommandReader extends Thread {

	const TYPE_READLINE = 0;
	const TYPE_STREAM = 1;
	const TYPE_PIPED = 2;

	/** @var ThreadSafeArray */
	protected ThreadSafeArray $buffer;
	private bool $shutdown = false;

	private int $type = self::TYPE_STREAM;
	
	/** @var SleeperNotifier|null */
	private SleeperNotifier $notifier;

	public function __construct(?SleeperNotifier $notifier = null) {
		$this->buffer = new ThreadSafeArray;
		$this->notifier = $notifier;

		$opts = getopt("", ["disable-readline", "enable-readline"]);

		if(extension_loaded("readline") and (Utils::getOS() === OS::WINDOWS ? isset($opts["enable-readline"]) : !isset($opts["disable-readline"])) and !$this->isPipe(STDIN)){
			$this->type = self::TYPE_READLINE;
		}

		$this->setClassLoaders();
    }

	public function shutdown(){
		$this->shutdown = true;
	}

	public function quit() : void{
		$wait = microtime(true) + 0.5;
		while(microtime(true) < $wait){
			if($this->isRunning()){
				usleep(100000);
			}else{
				parent::quit();
				return;
			}
		}

		$message = "Thread blocked for unknown reason";
		if($this->type === self::TYPE_PIPED){
			$message = "STDIN is being piped from another location and the pipe is blocked, cannot stop safely";
		}

		throw new ThreadException($message);
	}

	private function initStdin(){
		global $stdin;

		if(is_resource($stdin)){
			fclose($stdin);
		}

		$stdin = fopen("php://stdin", "r");
		if($this->isPipe($stdin)){
			$this->type = self::TYPE_PIPED;
		}else{
			$this->type = self::TYPE_STREAM;
		}
	}

	/**
	 * Checks if the specified stream is a FIFO pipe.
	 *
	 * @param resource $stream
	 *
	 * @return bool
	 */
	private function isPipe($stream) : bool{
		return is_resource($stream) and ((function_exists("posix_isatty") and !posix_isatty($stream)) or ((fstat($stream)["mode"] & 0170000) === 0010000));
	}

	/**
	 * Reads a line from the console and adds it to the buffer. This method may block the thread.
	 *
	 * @return bool if the main execution should continue reading lines
	 */
	private function readLine() : bool{
		$line = "";
		if($this->type === self::TYPE_READLINE){
			if(($raw = readline("> ")) !== false and ($line = trim($raw)) !== ""){
				readline_add_history($line);
			}else{
				return true;
			}
		}else{
			global $stdin;

			if(!is_resource($stdin)){
				$this->initStdin();
			}

			switch($this->type){
				/** @noinspection PhpMissingBreakStatementInspection */
				case self::TYPE_STREAM:
				    //stream_select doesn't work on piped streams for some reason
					$r = [$stdin];
					$w = $e = null;
					if(($count = stream_select($r, $w, $e, 0, 200000)) === 0){ //nothing changed in 200000 microseconds
						return true;
					}elseif($count === false){ //stream error
						$this->initStdin();
					}

				case self::TYPE_PIPED:
					if(($raw = fgets($stdin)) === false){ //broken pipe or EOF
						$this->initStdin();
						$this->synchronized(function(){
							$this->wait(200000);
						}); //prevent CPU waste if it's end of pipe
						return true; //loop back round
					}

					$line = trim($raw);
					break;
			}
		}

		if($line !== ""){
			$this->buffer[] = preg_replace("#\\x1b\\x5b([^\\x1b]*\\x7e|[\\x40-\\x50])#", "", $line);
			if($this->notifier !== null){
			    $this->notifier->wakeupSleeper();
            }
		}

		return true;
	}

	/**
	 * Reads a line from console, if available. Returns null if not available
	 *
	 * @return string|null
	 */
	public function getLine(){
		if($this->buffer->count() !== 0){
			return $this->buffer->shift();
		}

		return null;
	}

	public function onRun() : void{
		$this->registerClassLoaders();

		if($this->type !== self::TYPE_READLINE){
			$this->initStdin();
		}

		while(!$this->shutdown and $this->readLine()) ;

		if($this->type !== self::TYPE_READLINE){
			global $stdin;
			fclose($stdin);
		}

	}

	/**
	 * @return string
	 */
	public function getThreadName() : string{
		return "Console";
	}
}

