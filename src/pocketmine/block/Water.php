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

namespace pocketmine\block;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;

class Water extends Liquid{

	protected $id = self::FLOWING_WATER;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Water";
    }

    public function getLightFilter() : int{
        return 2;
    }

    public function getStillForm() : Block{
        return Block::get(Block::STILL_WATER, $this->meta);
    }

    public function getFlowingForm() : Block{
        return Block::get(Block::FLOWING_WATER, $this->meta);
    }

    public function getBucketFillSound() : int{
        return LevelSoundEventPacket::SOUND_BUCKET_FILL_WATER;
    }

    public function getBucketEmptySound() : int{
        return LevelSoundEventPacket::SOUND_BUCKET_EMPTY_WATER;
    }

    public function tickRate() : int{
        return 5;
    }

    public function onEntityCollide(Entity $entity) : void{
        $entity->resetFallDistance();
        if($entity->isOnFire()){
            $entity->extinguish();
        }
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        $ret = $this->getLevel()->setBlock($this, $this, true, false);
        $this->getLevel()->scheduleDelayedBlockUpdate($this, $this->tickRate());

        return $ret;
    }
}


