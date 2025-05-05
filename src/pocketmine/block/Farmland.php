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
use pocketmine\entity\Living;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;

class Farmland extends Transparent{

	protected $id = self::FARMLAND;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Farmland";
    }

    public function getHardness() : float{
        return 0.6;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_SHOVEL;
    }

    protected function recalculateBoundingBox() : ?AxisAlignedBB{
        return new AxisAlignedBB(
            $this->x,
            $this->y,
            $this->z,
            $this->x + 1,
            $this->y + 1, //TODO: this should be 0.9375, but MCPE currently treats them as a full block (https://bugs.mojang.com/browse/MCPE-12109)
            $this->z + 1
        );
    }

    public function onNearbyBlockChange() : void{
        if($this->getSide(Vector3::SIDE_UP)->isSolid()){
            $this->level->setBlock($this, Block::get(Block::DIRT), true);
        }
    }

    public function ticksRandomly() : bool{
        return true;
    }

    public function onRandomTick() : void{
        if(!$this->canHydrate()){
            if($this->meta > 0){
                $this->meta--;
                $this->level->setBlock($this, $this, false, false);
            }else{
                $this->level->setBlock($this, Block::get(Block::DIRT), false, true);
            }
        }elseif($this->meta < 7){
            $this->meta = 7;
            $this->level->setBlock($this, $this, false, false);
        }
    }

    protected function canHydrate() : bool{
        //TODO: check rain
        $start = $this->add(-4, 0, -4);
        $end = $this->add(4, 1, 4);
        for($y = $start->y; $y <= $end->y; ++$y){
            for($z = $start->z; $z <= $end->z; ++$z){
                for($x = $start->x; $x <= $end->x; ++$x){
                    $id = $this->level->getBlockIdAt($x, $y, $z);
                    if($id === Block::STILL_WATER or $id === Block::FLOWING_WATER){
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        return [
            Item::get(Item::DIRT)
        ];
    }

    public function isAffectedBySilkTouch() : bool{
        return false;
    }

    public function getPickedItem() : Item{
        return Item::get(Item::DIRT);
    }

    public function onEntityFallenUpon(Entity $entity, float $fallDistance) : void{
        if($entity instanceof Living){
            if($this->level->random->nextFloat() < ($fallDistance - 0.5)){
                $ev = new BlockFormEvent($this, Block::get(Block::DIRT));
                $ev->call();

                if(!$ev->isCancelled()){
                    $this->level->setBlock($this, $ev->getNewState(), true);
                }
            }
        }
    }
}

