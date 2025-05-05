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

namespace pocketmine\entity\projectile;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityEnderPearlEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\level\Level;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Player;

class EnderPearl extends Throwable{
	public const NETWORK_ID = self::ENDER_PEARL;

	public function __construct(Level $level, CompoundTag $nbt, Entity $shootingEntity = null){
		parent::__construct($level, $nbt, $shootingEntity);
	}

	public function onHit(ProjectileHitEvent $event) : void{
		$to = $event->getRayTraceResult()->getHitVector();
		if($this->shootingEntity instanceof Player and $this->shootingEntity->isAlive() and $to->y > 0){
			$ev = new EntityEnderPearlEvent($owner = $this->shootingEntity, $this, $to);
			$ev->call();

			if(!$ev->isCancelled()){
				$this->level->broadcastLevelEvent($owner, LevelEventPacket::EVENT_PARTICLE_ENDERMAN_TELEPORT);
				$this->level->addSound(new EndermanTeleportSound($owner));

				$owner->teleport($to);
				$this->level->addSound(new EndermanTeleportSound($owner));

				$ev = new EntityDamageEvent($owner, EntityDamageEvent::CAUSE_FALL, 5);
				$owner->attack($ev);
			}
		}

		$this->flagForDespawn();
	}
}


