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

use pocketmine\event\block\BlockGrowEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;
use function mt_rand;

abstract class Crops extends Flowable{

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        if($blockReplace->getSide(Vector3::SIDE_DOWN)->getId() === Block::FARMLAND){
            $this->getLevel()->setBlock($blockReplace, $this, true, true);

            return true;
        }

        return false;
    }

    public function onActivate(Item $item, Player $player = null) : bool{
        if($this->meta < 7 and $item->getId() === Item::DYE and $item->getDamage() === 0x0F){ //Bonemeal
            $block = clone $this;
            $block->meta += mt_rand(2, 5);
            if($block->meta > 7){
                $block->meta = 7;
            }

            $ev = new BlockGrowEvent($this, $block);
            $ev->call();
            if(!$ev->isCancelled()){
                $this->getLevel()->setBlock($this, $ev->getNewState(), true, true);
            }

            $item->pop();

            return true;
        }

        return false;
    }

    public function onNearbyBlockChange() : void{
        if($this->getSide(Vector3::SIDE_DOWN)->getId() !== Block::FARMLAND){
            $this->getLevel()->useBreakOn($this);
        }
    }

    public function ticksRandomly() : bool{
        return true;
    }

    public function onRandomTick() : void{
        if(mt_rand(0, 2) === 1){
            if($this->meta < 0x07){
                $block = clone $this;
                ++$block->meta;
                $ev = new BlockGrowEvent($this, $block);
                $ev->call();
                if(!$ev->isCancelled()){
                    $this->getLevel()->setBlock($this, $ev->getNewState(), true, true);
                }
            }
        }
    }

    public function isAffectedBySilkTouch() : bool{
        return false;
    }
}

