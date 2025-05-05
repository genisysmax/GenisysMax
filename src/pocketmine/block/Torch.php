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
use pocketmine\math\Vector3;
use pocketmine\Player;

class Torch extends Flowable{

	protected $id = self::TORCH;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getLightLevel() : int{
        return 14;
    }

    public function getName() : string{
        return "Torch";
    }

    public function onNearbyBlockChange() : void{
        $below = $this->getSide(Vector3::SIDE_DOWN);
        $meta = $this->getDamage();
        static $faces = [
            0 => Vector3::SIDE_DOWN,
            1 => Vector3::SIDE_WEST,
            2 => Vector3::SIDE_EAST,
            3 => Vector3::SIDE_NORTH,
            4 => Vector3::SIDE_SOUTH,
            5 => Vector3::SIDE_DOWN
        ];
        $face = $faces[$meta] ?? Vector3::SIDE_DOWN;

        if($this->getSide($face)->isTransparent() and !($face === Vector3::SIDE_DOWN and ($below->getId() === self::FENCE or $below->getId() === self::COBBLESTONE_WALL))){
            $this->getLevel()->useBreakOn($this);
        }
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        $below = $this->getSide(Vector3::SIDE_DOWN);

        if(!$blockClicked->isTransparent() and $face !== Vector3::SIDE_DOWN){
            $faces = [
                Vector3::SIDE_UP => 5,
                Vector3::SIDE_NORTH => 4,
                Vector3::SIDE_SOUTH => 3,
                Vector3::SIDE_WEST => 2,
                Vector3::SIDE_EAST => 1
            ];
            $this->meta = $faces[$face];
            $this->getLevel()->setBlock($blockReplace, $this, true, true);

            return true;
        }elseif(!$below->isTransparent() or $below->getId() === self::FENCE or $below->getId() === self::COBBLESTONE_WALL){
            $this->meta = 0;
            $this->getLevel()->setBlock($blockReplace, $this, true, true);

            return true;
        }

        return false;
    }

    public function getVariantBitmask() : int{
        return 0;
    }
}

