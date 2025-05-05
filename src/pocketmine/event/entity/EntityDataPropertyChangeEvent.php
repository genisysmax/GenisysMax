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

namespace pocketmine\event\entity;

use pocketmine\entity\Entity;
use pocketmine\event\Cancellable;

class EntityDataPropertyChangeEvent extends EntityEvent implements Cancellable{
	public static $handlerList = null;

	/** @var int */
	protected $id;
	/** @var int */
	protected $type;
	/** @var mixed */
	protected $value;
	/** @var bool */
	protected $send;

	public function __construct(Entity $entity, int $id, int $type, $value, bool $send){
		$this->entity = $entity;
		$this->id = $id;
		$this->type = $type;
		$this->value = $value;
		$this->send = $send;
	}

	/**
	 * @return int
	 */
	public function getId() : int{
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function getValue(){
		return $this->value;
	}

	/**
	 * @param mixed $value
	 */
	public function setValue($value) : void{
		$this->value = $value;
	}

	/**
	 * @return bool
	 */
	public function isSend() : bool{
		return $this->send;
	}

	/**
	 * @param bool $send
	 */
	public function setSend(bool $send) : void{
		$this->send = $send;
	}
}


