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
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityEatItemEvent;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

abstract class Food extends Item implements FoodSource{
	public function canBeConsumed() : bool{
		return true;
	}

	public function canBeConsumedBy(Entity $entity) : bool{
		return $entity instanceof Human and $entity->getFood() < $entity->getMaxFood();
	}

	public function getResidue(){
		if($this->getCount() === 1){
			return Item::get(0);
		}else{
			$new = clone $this;
			$new->count--;
			return $new;
		}
	}

	public function getAdditionalEffects() : array{
		return [];
	}

	public function onConsume(Entity $entity): void{
        assert($entity instanceof Human);
        $entity->level->broadcastLevelSoundEvent($entity->add(0, $entity->getEyeHeight(), 0), LevelSoundEventPacket::SOUND_BURP);

		$ev = new EntityEatItemEvent($entity, $this);

        $entity->addSaturation($ev->getSaturationRestore());
        $entity->addFood($ev->getFoodRestore());
		foreach($ev->getAdditionalEffects() as $effect){
            $entity->addEffect($effect);
		}

        $entity->getInventory()->setItemInHand($ev->getResidue());
	}
}


