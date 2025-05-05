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



namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;

class EndRod extends Flowable{

	protected $id = self::END_ROD;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "End Rod";
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        if($face === Vector3::SIDE_UP or $face === Vector3::SIDE_DOWN){
            $this->meta = $face;
        }else{
            $this->meta = $face ^ 0x01;
        }
        if($blockClicked instanceof EndRod and $blockClicked->getDamage() === $this->meta){
            $this->meta ^= 0x01;
        }

        return $this->level->setBlock($blockReplace, $this, true, true);
    }

    public function isSolid() : bool{
        return true;
    }

    public function getLightLevel() : int{
        return 14;
    }

    protected function recalculateBoundingBox() : ?AxisAlignedBB{
        $m = $this->meta & ~0x01;
        $width = 0.375;

        switch($m){
            case 0x00: //up/down
                return new AxisAlignedBB(
                    $this->x + $width,
                    $this->y,
                    $this->z + $width,
                    $this->x + 1 - $width,
                    $this->y + 1,
                    $this->z + 1 - $width
                );
            case 0x02: //north/south
                return new AxisAlignedBB(
                    $this->x,
                    $this->y + $width,
                    $this->z + $width,
                    $this->x + 1,
                    $this->y + 1 - $width,
                    $this->z + 1 - $width
                );
            case 0x04: //east/west
                return new AxisAlignedBB(
                    $this->x + $width,
                    $this->y + $width,
                    $this->z,
                    $this->x + 1 - $width,
                    $this->y + 1 - $width,
                    $this->z + 1
                );
        }

        return null;
    }

    public function getVariantBitmask() : int{
        return 0;
    }
}

