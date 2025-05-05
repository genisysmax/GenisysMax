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

use DateTime;
use DateTimeZone;
use LogLevel;
use pmmp\thread\Thread as NativeThread;
use pocketmine\thread\log\AttachableThreadSafeLogger;
use pocketmine\thread\log\ThreadSafeLoggerAttachment;
use pocketmine\thread\Thread;
use pocketmine\thread\Worker;

class MainLogger extends AttachableThreadSafeLogger{

    private string $format = TextFormat::AQUA . "[%s] " . TextFormat::RESET . "%s[%s/%s]: %s" . TextFormat::RESET;
	protected bool $shutdown;
	protected bool $logDebug;
	protected bool $logToFile = true;
	protected string $timezone;
	private MainLoggerThread $logWriterThread;

    /**
     * @param string $logFile
     * @param DateTimeZone $timezone
     * @param bool $logDebug
     */
	public function __construct(string $logFile, DateTimeZone $timezone, bool $logDebug = false) {
        parent::__construct();
		$this->logDebug = $logDebug;
		$this->timezone = $timezone->getName();
		$this->logWriterThread = new MainLoggerThread($logFile);
		$this->logWriterThread->start(NativeThread::INHERIT_NONE);
	}

	public function emergency(mixed $message) : void{
		$this->send($message, LogLevel::EMERGENCY, "EMERGENCY", TextFormat::RED);
	}

	public function alert(mixed $message) : void{
		$this->send($message, LogLevel::ALERT, "ALERT", TextFormat::RED);
	}

	public function critical(mixed $message) : void{
		$this->send($message, LogLevel::CRITICAL, "CRITICAL", TextFormat::RED);
	}

	public function error(mixed $message) : void{
		$this->send($message, LogLevel::ERROR, "ERROR", TextFormat::DARK_RED);
	}

	public function warning(mixed $message) : void{
		$this->send($message, LogLevel::WARNING, "WARNING", TextFormat::YELLOW);
	}

	public function notice(mixed $message) : void{
		$this->send($message, LogLevel::NOTICE, "NOTICE", TextFormat::AQUA);
	}

	public function info(mixed $message) : void{
		$this->send($message, LogLevel::INFO, "INFO", TextFormat::WHITE);
	}

	public function debug(mixed $message) : void{
		if($this->logDebug === false){
			return;
		}
		$this->send($message, LogLevel::DEBUG, "DEBUG", TextFormat::GRAY);
	}

	/**
	 * @param bool $logDebug
	 */
	public function setLogDebug(bool $logDebug) : void{
		$this->logDebug = $logDebug;
	}

	/**
	 * @param bool $logToFile
	 */
	public function setLogToFile(bool $logToFile) : void{
		$this->logToFile = $logToFile;
	}

	public function logException(\Throwable $e, $trace = null) : void{
		$this->critical(implode("\n", Utils::printableExceptionInfo($e, $trace)));
	}

	public function shutdownLogWriterThread() : void{
		if(NativeThread::getCurrentThreadId() === $this->logWriterThread->getCreatorId()){
			$this->logWriterThread->shutdown();
		}else{
			throw new \LogicException("Only the creator thread can shutdown the logger thread");
		}
	}

	public function log($level, $message) : void{
		switch($level){
			case LogLevel::EMERGENCY:
				$this->emergency($message);
				break;
			case LogLevel::ALERT:
				$this->alert($message);
				break;
			case LogLevel::CRITICAL:
				$this->critical($message);
				break;
			case LogLevel::ERROR:
				$this->error($message);
				break;
			case LogLevel::WARNING:
				$this->warning($message);
				break;
			case LogLevel::NOTICE:
				$this->notice($message);
				break;
			case LogLevel::INFO:
				$this->info($message);
				break;
			case LogLevel::DEBUG:
				$this->debug($message);
				break;
		}
	}

	public function shutdown() : void{
		$this->shutdownLogWriterThread();
		$this->shutdown = true;
		$this->notify();
	}

	protected function send($message, $level, $prefix, $color) : void{
		$time = new DateTime('now', new DateTimeZone($this->timezone));

		$thread = NativeThread::getCurrentThread();
		if($thread === null){
			$threadName = "Server thread";
		}elseif($thread instanceof Thread or $thread instanceof Worker){
			$threadName = $thread->getThreadName() . " thread";
		}else{
			$threadName = (new \ReflectionClass($thread))->getShortName() . " thread";
		}

		$message = sprintf($this->format, $time->format("H:i:s.v"), $color, $threadName, $prefix, TextFormat::clean($message, false));

		$this->synchronized(function() use ($message, $level, $time) : void{
			Terminal::writeLine($message);
			if ($this->logToFile) {
				$this->logWriterThread->write($time->format("Y-m-d") . " " . TextFormat::clean($message) . PHP_EOL);
			}

			if ($this->attachments !== NULL) {
				/**
				 * @var ThreadSafeLoggerAttachment $attachment
				 */
				foreach($this->attachments as $attachment){
					$attachment->log($level, $message);
				}
			}
		});
	}

	public function syncFlushBuffer() : void{
		$this->logWriterThread->syncFlushBuffer();
	}

	public function __destruct(){
		if(!$this->logWriterThread->isJoined() && NativeThread::getCurrentThreadId() === $this->logWriterThread->getCreatorId()){
			$this->shutdownLogWriterThread();
		}
	}
}

