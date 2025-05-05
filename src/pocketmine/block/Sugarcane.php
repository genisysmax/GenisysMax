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

class Sugarcane extends Flowable{

	protected $id = self::SUGARCANE_BLOCK;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Sugarcane";
    }

    public function onActivate(Item $item, Player $player = null) : bool{
        if($item->getId() === Item::DYE and $item->getDamage() === 0x0F){ //Bonemeal
            if($this->getSide(Vector3::SIDE_DOWN)->getId() !== self::SUGARCANE_BLOCK){
                for($y = 1; $y < 3; ++$y){
                    $b = $this->getLevel()->getBlockAt($this->x, $this->y + $y, $this->z);
                    if($b->getId() === self::AIR){
                        $ev = new BlockGrowEvent($b, Block::get(Block::SUGARCANE_BLOCK));
                        $ev->call();
                        if($ev->isCancelled()){
                            break;
                        }
                        $this->getLevel()->setBlock($b, $ev->getNewState(), true);
                    }else{
                        break;
                    }
                }
                $this->meta = 0;
                $this->getLevel()->setBlock($this, $this, true);
            }

            $item->pop();

            return true;
        }

        return false;
    }

    public function onNearbyBlockChange() : void{
        $down = $this->getSide(Vector3::SIDE_DOWN);
        if($down->isTransparent() and $down->getId() !== self::SUGARCANE_BLOCK){
            $this->getLevel()->useBreakOn($this);
        }
    }

    public function ticksRandomly() : bool{
        return true;
    }

    public function onRandomTick() : void{
        if($this->getSide(Vector3::SIDE_DOWN)->getId() !== self::SUGARCANE_BLOCK){
            if($this->meta === 0x0F){
                for($y = 1; $y < 3; ++$y){
                    $b = $this->getLevel()->getBlockAt($this->x, $this->y + $y, $this->z);
                    if($b->getId() === self::AIR){
                        $ev = new BlockGrowEvent($b, Block::get(Block::SUGARCANE_BLOCK));
                        $ev->call();
                        if($ev->isCancelled()){
                            break;
                        }
                        $this->getLevel()->setBlock($b, $ev->getNewState(), true);
                        break;
                    }
                }
                $this->meta = 0;
                $this->getLevel()->setBlock($this, $this, true);
            }else{
                ++$this->meta;
                $this->getLevel()->setBlock($this, $this, true);
            }
        }
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        $down = $this->getSide(Vector3::SIDE_DOWN);
        if($down->getId() === self::SUGARCANE_BLOCK){
            $this->getLevel()->setBlock($blockReplace, Block::get(Block::SUGARCANE_BLOCK), true);

            return true;
        }elseif($down->getId() === self::GRASS or $down->getId() === self::DIRT or $down->getId() === self::SAND){
            $block0 = $down->getSide(Vector3::SIDE_NORTH);
            $block1 = $down->getSide(Vector3::SIDE_SOUTH);
            $block2 = $down->getSide(Vector3::SIDE_WEST);
            $block3 = $down->getSide(Vector3::SIDE_EAST);
            if(($block0 instanceof Water) or ($block1 instanceof Water) or ($block2 instanceof Water) or ($block3 instanceof Water)){
                $this->getLevel()->setBlock($blockReplace, Block::get(Block::SUGARCANE_BLOCK), true);

                return true;
            }
        }

        return false;
    }

    public function getVariantBitmask() : int{
        return 0;
    }
}

