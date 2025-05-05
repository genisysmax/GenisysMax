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
use pocketmine\level\generator\object\Tree;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\Random;
use function mt_rand;

class Sapling extends Flowable{
    public const OAK = 0;
    public const SPRUCE = 1;
    public const BIRCH = 2;
    public const JUNGLE = 3;
    public const ACACIA = 4;
    public const DARK_OAK = 5;

    protected $id = self::SAPLING;

    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }

    public function getName() : string{
        static $names = [
            0 => "Oak Sapling",
            1 => "Spruce Sapling",
            2 => "Birch Sapling",
            3 => "Jungle Sapling",
            4 => "Acacia Sapling",
            5 => "Dark Oak Sapling"
        ];
        return $names[$this->getVariant()] ?? "Unknown";
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        $down = $this->getSide(Vector3::SIDE_DOWN);
        if($down->getId() === self::GRASS or $down->getId() === self::DIRT or $down->getId() === self::FARMLAND){
            $this->getLevel()->setBlock($blockReplace, $this, true, true);

            return true;
        }

        return false;
    }

    public function onActivate(Item $item, Player $player = null) : bool{
        if($item->getId() === Item::DYE and $item->getDamage() === 0x0F){ //Bonemeal
            //TODO: change log type
            #add BlockGrowEvent
            $block = clone $this;
            $ev = new BlockGrowEvent($this, $block);
            $ev->call();
            if(!$ev->isCancelled()){
                Tree::growTree($this->getLevel(), $this->x, $this->y, $this->z, new Random(mt_rand()), $this->getVariant());
            }

            $item->pop();

            return true;
        }

        return false;
    }

    public function onNearbyBlockChange() : void{
        if($this->getSide(Vector3::SIDE_DOWN)->isTransparent()){
            $this->getLevel()->useBreakOn($this);
        }
    }

    public function ticksRandomly() : bool{
        return true;
    }

    public function onRandomTick() : void{
        if($this->level->getFullLightAt($this->x, $this->y, $this->z) >= 8 and mt_rand(1, 7) === 1){
            if(($this->meta & 0x08) === 0x08){
                $block = clone $this;
                $ev = new BlockGrowEvent($this, $block);
                $ev->call();
                if(!$ev->isCancelled()){
                    Tree::growTree($this->getLevel(), $this->x, $this->y, $this->z, new Random(mt_rand()), $this->getVariant());
                }
            }else{
                $this->meta |= 0x08;
                $this->getLevel()->setBlock($this, $this, true);
            }
        }
    }

    public function getVariantBitmask() : int{
        return 0x07;
    }

    public function getFuelTime() : int{
        return 100;
    }
}

