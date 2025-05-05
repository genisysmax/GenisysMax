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



namespace pocketmine\event\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\math\RayTraceResult;

class ProjectileHitEntityEvent extends ProjectileHitEvent{
	public static $handlerList = null;

	/** @var Entity */
	private $entityHit;

	public function __construct(Projectile $entity, RayTraceResult $rayTraceResult, Entity $entityHit){
		parent::__construct($entity, $rayTraceResult);
		$this->entityHit = $entityHit;
	}

	/**
	 * Returns the Entity struck by the projectile.
	 *
	 * @return Entity
	 */
	public function getEntityHit() : Entity{
		return $this->entityHit;
	}
}

