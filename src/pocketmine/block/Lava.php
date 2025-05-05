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
use pocketmine\event\entity\EntityCombustByBlockEvent;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;

class Lava extends Liquid{

	protected $id = self::FLOWING_LAVA;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getLightLevel() : int{
        return 15;
    }

    public function getName() : string{
        return "Lava";
    }

    public function getStillForm() : Block{
        return Block::get(Block::STILL_LAVA, $this->meta);
    }

    public function getFlowingForm() : Block{
        return Block::get(Block::FLOWING_LAVA, $this->meta);
    }

    public function getBucketFillSound() : int{
        return LevelSoundEventPacket::SOUND_BUCKET_FILL_LAVA;
    }

    public function getBucketEmptySound() : int{
        return LevelSoundEventPacket::SOUND_BUCKET_EMPTY_LAVA;
    }

    public function tickRate() : int{
        return 30;
    }

    public function getFlowDecayPerBlock() : int{
        return 2; //TODO: this is 1 in the nether
    }

    protected function checkForHarden(){
        $colliding = null;
        for($side = 1; $side <= 5; ++$side){ //don't check downwards side
            $blockSide = $this->getSide($side);
            if($blockSide instanceof Water){
                $colliding = $blockSide;
                break;
            }
        }

        if($colliding !== null){
            if($this->getDamage() === 0){
                $this->liquidCollide($colliding, Block::get(Block::OBSIDIAN));
            }elseif($this->getDamage() <= 4){
                $this->liquidCollide($colliding, Block::get(Block::COBBLESTONE));
            }
        }
    }

    protected function flowIntoBlock(Block $block, int $newFlowDecay) : void{
        if($block instanceof Water){
            $block->liquidCollide($this, Block::get(Block::STONE));
        }else{
            parent::flowIntoBlock($block, $newFlowDecay);
        }
    }

    public function onEntityCollide(Entity $entity) : void{
        $entity->fallDistance *= 0.5;

        $ev = new EntityDamageByBlockEvent($this, $entity, EntityDamageEvent::CAUSE_LAVA, 4);
        $entity->attack($ev);

        $ev = new EntityCombustByBlockEvent($this, $entity, 15);
        $ev->call();
        if(!$ev->isCancelled()){
            $entity->setOnFire($ev->getDuration());
        }

        $entity->resetFallDistance();
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        $ret = $this->getLevel()->setBlock($this, $this, true, false);
        $this->getLevel()->scheduleDelayedBlockUpdate($this, $this->tickRate());

        return $ret;
    }
}


