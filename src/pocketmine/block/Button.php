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
use pocketmine\level\sound\ButtonClickSound;
use pocketmine\math\Vector3;
use pocketmine\Player;

abstract class Button extends Flowable{

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function onScheduledUpdate(): void
    {
        if ($this->isActivated()) {
            $this->meta ^= 0x08;
            $this->getLevel()->setBlock($this, $this, true, false);
            $this->getLevel()->addSound(new ButtonClickSound($this));
        }
    }

    public function onNearbyBlockChange() : void{
        $faces = [
            0 => Vector3::SIDE_UP,
            1 => Vector3::SIDE_WEST,
            2 => Vector3::SIDE_EAST,
            3 => Vector3::SIDE_NORTH,
            4 => Vector3::SIDE_SOUTH,
            5 => Vector3::SIDE_DOWN,
            6 => Vector3::SIDE_DOWN,
            7 => Vector3::SIDE_UP
        ];
        if(!$this->getSide($faces[$this->meta & 0x07])->isSolid()){
            $this->level->useBreakOn($this);
        }
    }

    public function onBreak(Item $item, Player $player = null): bool{
        if($this->isActivated()){
            $this->meta ^= 0x08;
            $this->getLevel()->setBlock($this, $this, true, false);
        }
        $this->getLevel()->setBlock($this, new Air(), true, false);
        return true;
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool{
        if($blockClicked->isTransparent() === false){
            $this->meta = $face;
            $this->getLevel()->setBlock($blockReplace, $this, true, false);
            return true;
        }
        return false;
    }

    public function isActivated(Block $from = null): bool{
        return (($this->meta & 0x08) === 0x08);
    }

    public function onActivate(Item $item, Player $player = null): bool{
        if(!$this->isActivated()){
            $this->meta ^= 0x08;
            $this->getLevel()->setBlock($this, $this, true, false);
            $this->getLevel()->addSound(new ButtonClickSound($this));
            $this->getLevel()->scheduleUpdate($this, 30);
        }
        return true;
    }

    public function getVariantBitmask() : int{
        return 0;
    }
}


