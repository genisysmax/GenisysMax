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

class Trapdoor extends Transparent{
	public const MASK_UPPER = 0x04;
	public const MASK_OPENED = 0x08;
	public const MASK_SIDE = 0x03;
	public const MASK_SIDE_SOUTH = 2;
	public const MASK_SIDE_NORTH = 3;
	public const MASK_SIDE_EAST = 0;
	public const MASK_SIDE_WEST = 1;

	protected $id = self::TRAPDOOR;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Wooden Trapdoor";
    }

    public function getHardness() : float{
        return 3;
    }

    protected function recalculateBoundingBox() : ?AxisAlignedBB{

        $damage = $this->getDamage();

        $f = 0.1875;

        if(($damage & self::MASK_UPPER) > 0){
            $bb = new AxisAlignedBB(
                $this->x,
                $this->y + 1 - $f,
                $this->z,
                $this->x + 1,
                $this->y + 1,
                $this->z + 1
            );
        }else{
            $bb = new AxisAlignedBB(
                $this->x,
                $this->y,
                $this->z,
                $this->x + 1,
                $this->y + $f,
                $this->z + 1
            );
        }

        if(($damage & self::MASK_OPENED) > 0){
            if(($damage & 0x03) === self::MASK_SIDE_NORTH){
                $bb->setBounds(
                    $this->x,
                    $this->y,
                    $this->z + 1 - $f,
                    $this->x + 1,
                    $this->y + 1,
                    $this->z + 1
                );
            }elseif(($damage & 0x03) === self::MASK_SIDE_SOUTH){
                $bb->setBounds(
                    $this->x,
                    $this->y,
                    $this->z,
                    $this->x + 1,
                    $this->y + 1,
                    $this->z + $f
                );
            }
            if(($damage & 0x03) === self::MASK_SIDE_WEST){
                $bb->setBounds(
                    $this->x + 1 - $f,
                    $this->y,
                    $this->z,
                    $this->x + 1,
                    $this->y + 1,
                    $this->z + 1
                );
            }
            if(($damage & 0x03) === self::MASK_SIDE_EAST){
                $bb->setBounds(
                    $this->x,
                    $this->y,
                    $this->z,
                    $this->x + $f,
                    $this->y + 1,
                    $this->z + 1
                );
            }
        }

        return $bb;
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        $directions = [
            0 => 1,
            1 => 3,
            2 => 0,
            3 => 2
        ];
        if($player !== null){
            $this->meta = $directions[$player->getDirection() & 0x03];
        }
        if(($clickVector->y > 0.5 and $face !== self::SIDE_UP) or $face === self::SIDE_DOWN){
            $this->meta |= self::MASK_UPPER; //top half of block
        }
        $this->getLevel()->setBlock($blockReplace, $this, true, true);
        return true;
    }

    public function getVariantBitmask() : int{
        return 0;
    }

    public function onActivate(Item $item, Player $player = null) : bool{
        $this->meta ^= self::MASK_OPENED;
        $this->getLevel()->setBlock($this, $this, true);
        $this->level->addSound(new DoorSound($this));
        return true;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_AXE;
    }

    public function getFuelTime() : int{
        return 300;
    }
}


