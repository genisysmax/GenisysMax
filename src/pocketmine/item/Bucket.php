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

use pocketmine\BedrockPlayer;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\Lava;
use pocketmine\block\Liquid;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;

class Bucket extends Item{

	public const MILK = 1;

	public function __construct($meta = 0, $count = 1){
		parent::__construct(self::BUCKET, $meta, $count, "Bucket");
	}

	public function getMaxStackSize() : int{
		return 1;
	}

    public function getFuelTime() : int{
        if($this->meta === Block::LAVA or $this->meta === Block::FLOWING_LAVA){
            return 20000;
        }

        return 0;
    }

    public function getFuelResidue() : Item{
        if($this->meta === Block::LAVA or $this->meta === Block::FLOWING_LAVA){
            return Item::get(Item::BUCKET);
        }

        return parent::getFuelResidue();
    }

    public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector) : bool{
		$targetBlock = Block::get($this->meta);

		if($targetBlock instanceof Air){
			if($blockClicked instanceof Liquid and $blockClicked->getDamage() === 0){
				$result = clone $this;
				$result->setDamage($blockClicked->getId());
				$ev = new PlayerBucketFillEvent($player, $blockReplace, $face, $this, $result);
				$ev->call();
				if(!$ev->isCancelled()){
					$player->getLevel()->setBlock($blockClicked, new Air(), true, true);
					if($blockClicked instanceof Lava){
						$soundId = LevelSoundEventPacket::SOUND_BUCKET_FILL_LAVA;
 					}else{
 						$soundId = LevelSoundEventPacket::SOUND_BUCKET_FILL_WATER;
 					}
                    $blockClicked->getLevel()->broadcastLevelSoundEvent($blockClicked, $soundId);
					if($player->isSurvival()){
						$player->getInventory()->setItemInHand($ev->getItem());
					}
					return true;
				}else{
					$player->getInventory()->sendContents($player);
				}
			}
		}elseif($targetBlock instanceof Liquid){
			$result = clone $this;
			$result->setDamage(0);

			$ev = new PlayerBucketEmptyEvent($player, $blockReplace, $face, $this, $result);
			$ev->call();
			if(!$ev->isCancelled()){
				$player->getLevel()->setBlock($blockReplace, $targetBlock, true, true);
				if($targetBlock instanceof Lava){
 					$soundId = LevelSoundEventPacket::SOUND_BUCKET_EMPTY_LAVA;
 				}else{
 					$soundId = LevelSoundEventPacket::SOUND_BUCKET_EMPTY_WATER;
 				}
 				$targetBlock->getLevel()->broadcastLevelSoundEvent($targetBlock, $soundId);
				if($player->isSurvival()){
					$player->getInventory()->setItemInHand($ev->getItem());
				}
				return true;
			}else{
				$player->getInventory()->sendContents($player);
			}
		}

		return false;
	}

	public function getResidue(){
		return Item::get(Item::BUCKET, 0, 1);
	}

	public function canBeConsumed() : bool{
		return $this->meta === self::MILK;
	}

	public function onConsume(Entity $entity): void
    {
        assert($entity instanceof Human);
        $entity->getInventory()->setItemInHand($this->getResidue());
        $entity->level->broadcastLevelSoundEvent($entity->add(0, $entity->getEyeHeight(), 0), LevelSoundEventPacket::SOUND_BURP);
        $entity->removeAllEffects();
    }
	public function onReleaseUsing(Player $player) : void{
		if(!$player instanceof BedrockPlayer and $this->canBeConsumed()){ //Blame Mojang
			$this->onConsume($player);
		}
	}
}

