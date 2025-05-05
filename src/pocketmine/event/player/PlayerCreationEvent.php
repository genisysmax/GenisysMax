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

namespace pocketmine\event\player;

use pocketmine\event\Event;
use pocketmine\network\NetworkInterface;
use pocketmine\Player;
use function is_a;

/**
 * Allows the creation of players overriding the base Player class
 */
class PlayerCreationEvent extends Event{
	public static $handlerList = null;

	/** @var NetworkInterface */
	private $interface;
	/** @var mixed */
	private $clientId;
	/** @var string */
	private $address;
	/** @var int */
	private $port;

	/** @var Player::class */
	private $baseClass;
	/** @var Player::class */
	private $playerClass;

	/**
	 * @param NetworkInterface $interface
	 * @param Player::class   $baseClass
	 * @param Player::class   $playerClass
	 * @param mixed           $clientId
	 * @param string          $address
	 * @param int             $port
	 */
	public function __construct(NetworkInterface $interface, $baseClass, $playerClass, $clientId, string $address, int $port){
		$this->interface = $interface;
		$this->clientId = $clientId;
		$this->address = $address;
		$this->port = $port;

		if(!is_a($baseClass, Player::class, true)){
			throw new \RuntimeException("Base class $baseClass must extend " . Player::class);
		}

		$this->baseClass = $baseClass;

		if(!is_a($playerClass, Player::class, true)){
			throw new \RuntimeException("Class $playerClass must extend " . Player::class);
		}

		$this->playerClass = $playerClass;
	}

	/**
	 * @return NetworkInterface
	 */
	public function getInterface() : NetworkInterface{
		return $this->interface;
	}

	/**
	 * @return string
	 */
	public function getAddress() : string{
		return $this->address;
	}

	/**
	 * @return int
	 */
	public function getPort() : int{
		return $this->port;
	}

	/**
	 * @return mixed
	 */
	public function getClientId(){
		return $this->clientId;
	}

	/**
	 * @return Player::class
	 */
	public function getBaseClass(){
		return $this->baseClass;
	}

	/**
	 * @param Player::class $class
	 */
	public function setBaseClass($class){
		if(!is_a($class, $this->baseClass, true)){
			throw new \RuntimeException("Base class $class must extend " . $this->baseClass);
		}

		$this->baseClass = $class;
	}

	/**
	 * @return Player::class
	 */
	public function getPlayerClass(){
		return $this->playerClass;
	}

	/**
	 * @param Player::class $class
	 */
	public function setPlayerClass($class){
		if(!is_a($class, $this->baseClass, true)){
			throw new \RuntimeException("Class $class must extend " . $this->baseClass);
		}

		$this->playerClass = $class;
	}

}

