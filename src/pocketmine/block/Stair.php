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

abstract class Stair extends Transparent{

    protected function recalculateCollisionBoxes() : array{
        //TODO: handle corners

        $minYSlab = ($this->meta & 0x04) === 0 ? 0 : 0.5;
        $maxYSlab = $minYSlab + 0.5;

        $bbs = [
            new AxisAlignedBB(
                $this->x,
                $this->y + $minYSlab,
                $this->z,
                $this->x + 1,
                $this->y + $maxYSlab,
                $this->z + 1
            )
        ];

        $minY = ($this->meta & 0x04) === 0 ? 0.5 : 0;
        $maxY = $minY + 0.5;

        $rotationMeta = $this->meta & 0x03;

        $minX = $minZ = 0;
        $maxX = $maxZ = 1;

        switch($rotationMeta){
            case 0:
                $minX = 0.5;
                break;
            case 1:
                $maxX = 0.5;
                break;
            case 2:
                $minZ = 0.5;
                break;
            case 3:
                $maxZ = 0.5;
                break;
        }

        $bbs[] = new AxisAlignedBB(
            $this->x + $minX,
            $this->y + $minY,
            $this->z + $minZ,
            $this->x + $maxX,
            $this->y + $maxY,
            $this->z + $maxZ
        );

        return $bbs;
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        $faces = [
            0 => 0,
            1 => 2,
            2 => 1,
            3 => 3
        ];
        $this->meta = $player !== null ? $faces[$player->getDirection()] & 0x03 : 0;
        if(($clickVector->y > 0.5 and $face !== Vector3::SIDE_UP) or $face === Vector3::SIDE_DOWN){
            $this->meta |= 0x04; //Upside-down stairs
        }
        $this->getLevel()->setBlock($blockReplace, $this, true, true);

        return true;
    }

    public function getVariantBitmask() : int{
        return 0;
    }
}


