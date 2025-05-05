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
use pocketmine\entity\projectile\FishingHook;
use pocketmine\event\player\fish\FishingRodStartFishingEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;

class FishingRod extends Durable{

	public function __construct($meta = 0, $count = 1){
		parent::__construct(self::FISHING_ROD, $meta, $count, "Fishing Rod");
	}

	public function getMaxStackSize() : int{
		return 1;
	}

	public function getMaxDurability() : int{
		return 385;
	}

	public function onClickAir(Player $player, Vector3 $directionVector) : bool{
		$hook = $player->getFishingHook();
		if($hook === null or $hook->isFlaggedForDespawn()){
			$hook = new FishingHook($player->level, Entity::createBaseNBT($player->add(0, $player->getEyeHeight(), 0)), $player);

			$ev = new FishingRodStartFishingEvent($player, $hook, 1.0, 0.75, 1.0);
			$ev->call();

			if(!$ev->isCancelled()){
				$hook->setMotion($directionVector->normalize()->multiply($ev->getForce()));

				$player->setFishingHook($hook);
				$hook->handleHookCasting($hook->motionX, $hook->motionY, $hook->motionZ, $ev->getF1(), $ev->getF2());

				$player->level->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_THROW, 0, 0x13f); //Yay! Magic numbers
				$hook->spawnToAll();
			}else{
				$hook->flagForDespawn();
			}
		}else{
			$damage = $hook->handleHookRetraction();

			if($player->isSurvival() and $damage !== 0){
				$this->applyDamage($damage);
				$player->getInventory()->setItemInHand($this);
			}
		}
		return true;
	}
}

