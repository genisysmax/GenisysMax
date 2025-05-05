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

namespace pocketmine\item;

use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;

abstract class ProjectileItem extends Item{

	abstract public function getProjectileEntityType() : string;

	abstract public function getThrowForce() : float;

	/**
	 * Helper function to apply extra NBT tags to pass to the created projectile.
	 *
	 * @param CompoundTag $tag
	 */
	protected function addExtraTags(CompoundTag $tag) : void{

	}

	public function onClickAir(Player $player, Vector3 $directionVector) : bool{
		$nbt = EntityDataHelper::createBaseNBT($player->add(0, $player->getEyeHeight(), 0), $directionVector, $player->yaw, $player->pitch);
		$this->addExtraTags($nbt);

		$projectile = Entity::createEntity($this->getProjectileEntityType(), $player->level, $nbt, $player);
		$projectile->setMotion($projectile->getMotion()->multiply($this->getThrowForce()));
		if($player->isSurvival()){
			$player->getInventory()->setItemInHand(--$this->count > 0 ? $this : Item::get(Item::AIR));
		}
		if($projectile instanceof Projectile){
			$projectile->setShootingEntity($player);
			$projectileEv = new ProjectileLaunchEvent($projectile);
			$projectileEv->call();
			if($projectileEv->isCancelled()){
				$projectile->flagForDespawn();
			}else{
				$projectile->spawnToAll();
				$player->level->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_THROW, 0, 0x13f); //Yay! Magic numbers
			}
		}else{
			$projectile->spawnToAll();
		}

		return true;
	}
}

