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

use pocketmine\entity\projectile\Projectile;
use pocketmine\math\RayTraceResult;

abstract class ProjectileHitEvent extends EntityEvent{
	public static $handlerList = null;

	/** @var RayTraceResult */
	private $rayTraceResult;

	/**
	 * @param Projectile $entity
	 */
	public function __construct(Projectile $entity, RayTraceResult $rayTraceResult){
		$this->entity = $entity;
		$this->rayTraceResult = $rayTraceResult;
	}

	/**
	 * @return Projectile
	 */
	public function getEntity(){
		return $this->entity;
	}

	/**
	 * Returns a RayTraceResult object containing information such as the exact position struck, the AABB it hit, and
	 * the face of the AABB that it hit.
	 *
	 * @return RayTraceResult
	 */
	public function getRayTraceResult() : RayTraceResult{
		return $this->rayTraceResult;
	}
}

