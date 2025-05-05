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

use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;

class WaterLily extends Flowable{

	protected $id = self::WATER_LILY;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Lily Pad";
    }

    public function getHardness() : float{
        return 0.6;
    }

    protected function recalculateBoundingBox() : ?AxisAlignedBB{
        return new AxisAlignedBB(
            $this->x + 0.0625,
            $this->y,
            $this->z + 0.0625,
            $this->x + 0.9375,
            $this->y + 0.015625,
            $this->z + 0.9375
        );
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        if($blockClicked instanceof Water){
            $up = $blockClicked->getSide(Vector3::SIDE_UP);
            if($up->getId() === Block::AIR){
                $this->getLevel()->setBlock($up, $this, true, true);
                return true;
            }
        }

        return false;
    }

    public function onNearbyBlockChange() : void{
        if(!($this->getSide(Vector3::SIDE_DOWN) instanceof Water)){
            $this->getLevel()->useBreakOn($this);
        }
    }

    public function getVariantBitmask() : int{
        return 0;
    }
}


