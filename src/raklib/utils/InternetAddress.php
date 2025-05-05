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

namespace raklib\utils;

class InternetAddress{

	/**
	 * @var string
	 */
	public $ip;
	/**
	 * @var int
	 */
	public $port;
	/**
	 * @var int
	 */
	public $version;

	public function __construct(string $address, int $port, int $version){
		$this->ip = $address;
		if($port < 0 or $port > 65535){
			throw new \InvalidArgumentException("Invalid port range");
		}
		$this->port = $port;
		$this->version = $version;
	}

	/**
	 * @return string
	 */
	public function getIp() : string{
		return $this->ip;
	}

	/**
	 * @return int
	 */
	public function getPort() : int{
		return $this->port;
	}

	/**
	 * @return int
	 */
	public function getVersion() : int{
		return $this->version;
	}

	public function __toString(){
		return $this->ip . " " . $this->port;
	}

	public function toString() : string{
		return $this->__toString();
	}

	public function equals(InternetAddress $address) : bool{
		return $this->ip === $address->ip and $this->port === $address->port and $this->version === $address->version;
	}
}


