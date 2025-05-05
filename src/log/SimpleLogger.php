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

class SimpleLogger implements \Logger{
	public function emergency(mixed $message) : void{
		$this->log(LogLevel::EMERGENCY, $message);
	}

	public function alert(mixed $message) : void{
		$this->log(LogLevel::ALERT, $message);
	}

	public function critical(mixed $message) : void{
		$this->log(LogLevel::CRITICAL, $message);
	}

	public function error(mixed $message) : void{
		$this->log(LogLevel::ERROR, $message);
	}

	public function warning(mixed $message) : void{
		$this->log(LogLevel::WARNING, $message);
	}

	public function notice(mixed $message) : void{
		$this->log(LogLevel::NOTICE, $message);
	}

	public function info(mixed $message) : void{
		$this->log(LogLevel::INFO, $message);
	}

	public function debug(mixed $message) : void{
		$this->log(LogLevel::DEBUG, $message);
	}

	public function log(mixed $level, mixed $message) : void{
		echo "[" . strtoupper($level) . "] " . $message . PHP_EOL;
	}

	public function logException(\Throwable $e, $trace = null){
		$this->critical($e->getMessage());
		echo $e->getTraceAsString();
	}
}


