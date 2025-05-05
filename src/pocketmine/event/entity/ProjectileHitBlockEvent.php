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

use pocketmine\block\Block;
use pocketmine\entity\projectile\Projectile;
use pocketmine\math\RayTraceResult;

class ProjectileHitBlockEvent extends ProjectileHitEvent{
	public static $handlerList = null;

	/** @var Block */
	private $blockHit;

	public function __construct(Projectile $entity, RayTraceResult $rayTraceResult, Block $blockHit){
		parent::__construct($entity, $rayTraceResult);
		$this->blockHit = $blockHit;
	}

	/**
	 * Returns the Block struck by the projectile.
	 * Hint: to get the block face hit, look at the RayTraceResult.
	 *
	 * @return Block
	 */
	public function getBlockHit() : Block{
		return $this->blockHit;
	}
}

