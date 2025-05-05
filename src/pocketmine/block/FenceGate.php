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
use pocketmine\level\sound\DoorSound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;

class FenceGate extends Transparent{

    public function getHardness() : float{
        return 2;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_AXE;
    }

    protected function recalculateBoundingBox() : ?AxisAlignedBB{

        if(($this->getDamage() & 0x04) > 0){
            return null;
        }

        $i = ($this->getDamage() & 0x03);
        if($i === 2 or $i === 0){
            return new AxisAlignedBB(
                $this->x,
                $this->y,
                $this->z + 0.375,
                $this->x + 1,
                $this->y + 1.5,
                $this->z + 0.625
            );
        }else{
            return new AxisAlignedBB(
                $this->x + 0.375,
                $this->y,
                $this->z,
                $this->x + 0.625,
                $this->y + 1.5,
                $this->z + 1
            );
        }
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        $this->meta = ($player instanceof Player ? ($player->getDirection() - 1) & 0x03 : 0);
        $this->getLevel()->setBlock($blockReplace, $this, true, true);

        return true;
    }

    public function getVariantBitmask() : int{
        return 0;
    }

    public function onActivate(Item $item, Player $player = null) : bool{
        $this->meta = (($this->meta ^ 0x04) & ~0x02);

        if($player !== null){
            $this->meta |= (($player->getDirection() - 1) & 0x02);
        }

        $this->getLevel()->setBlock($this, $this, true);
        $this->level->addSound(new DoorSound($this));
        return true;
    }

    public function getFuelTime() : int{
        return 300;
    }

    public function getFlameEncouragement() : int{
        return 5;
    }

    public function getFlammability() : int{
        return 20;
    }
}


