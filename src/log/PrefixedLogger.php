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

class PrefixedLogger extends SimpleLogger{

	private Logger $delegate;
	private string $prefix;

	public function __construct(\Logger $delegate, string $prefix){
		$this->delegate = $delegate;
		$this->prefix = $prefix;
	}

	public function log(mixed $level, mixed $message) : void{
		$this->delegate->log($level, "[$this->prefix] $message");
	}

	public function logException(Throwable $e, $trace = null){
		$this->delegate->logException($e, $trace);
	}

	/**
	 * @return string
	 */
	public function getPrefix() : string{
		return $this->prefix;
	}

	/**
	 * @param string $prefix
	 */
	public function setPrefix(string $prefix) : void{
		$this->prefix = $prefix;
	}
}


