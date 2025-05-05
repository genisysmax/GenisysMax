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

class DeadBush extends Flowable{

	protected $id = self::DEAD_BUSH;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Dead Bush";
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        if(!$this->getSide(Vector3::SIDE_DOWN)->isTransparent()){
            return parent::place($item, $blockReplace, $blockClicked, $face, $clickVector, $player);
        }

        return false;
    }

    public function onNearbyBlockChange() : void{
        if($this->getSide(Vector3::SIDE_DOWN)->isTransparent()){
            $this->getLevel()->useBreakOn($this);
        }
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_SHEARS;
    }

    public function getToolHarvestLevel() : int{
        return 1;
    }

    public function getDrops(Item $item) : array{
        if(!$this->isCompatibleWithTool($item)){
            return [
                Item::get(Item::STICK, 0, mt_rand(0, 2))
            ];
        }

        return parent::getDrops($item);
    }

    public function getFlameEncouragement() : int{
        return 60;
    }

    public function getFlammability() : int{
        return 100;
    }
}

