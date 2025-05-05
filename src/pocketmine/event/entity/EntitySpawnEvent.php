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

use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\object\Item;
use pocketmine\entity\projectile\Projectile;
use pocketmine\entity\Vehicle;
use pocketmine\level\Position;

/**
 * Called when a entity is spawned
 */
class EntitySpawnEvent extends EntityEvent{
	public static $handlerList = null;

	/** @var int */
	private $entityType;

	/**
	 * @param Entity $entity
	 */
	public function __construct(Entity $entity){
		$this->entity = $entity;
		$this->entityType = $entity::NETWORK_ID;
	}

	/**
	 * @return Position
	 */
	public function getPosition(){
		return $this->entity->getPosition();
	}

	/**
	 * @return int
	 */
	public function getType(){
		return $this->entityType;
	}

	/**
	 * @return bool
	 */
	public function isCreature(){
		return $this->entity instanceof Creature;
	}

	/**
	 * @return bool
	 */
	public function isHuman(){
		return $this->entity instanceof Human;
	}

	/**
	 * @return bool
	 */
	public function isProjectile(){
		return $this->entity instanceof Projectile;
	}

	/**
	 * @return bool
	 */
	public function isVehicle(){
		return $this->entity instanceof Vehicle;
	}

	/**
	 * @return bool
	 */
	public function isItem(){
		return $this->entity instanceof Item;
	}

}

