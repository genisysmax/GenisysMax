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
use pocketmine\entity\projectile\EnderPearl;
use pocketmine\event\Cancellable;
use pocketmine\math\Vector3;

class EntityEnderPearlEvent extends EntityEvent implements Cancellable{
	public static $handlerList = null;

	/** @var EnderPearl */
	private $projectile;
	/** @var Vector3 */
	private $to;

	public function __construct(Entity $entity, EnderPearl $projectile, Vector3 $to){
		$this->entity = $entity;
		$this->projectile = $projectile;
	}

	/**
	 * @return EnderPearl
	 */
	public function getEnderPearl() : EnderPearl{
		return $this->projectile;
	}

	/**
	 * @return Vector3
	 */
	public function getTo() : Vector3{
		return $this->to;
	}
}

