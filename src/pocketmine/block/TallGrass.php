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
use function mt_rand;

class TallGrass extends Flowable{

	protected $id = self::TALL_GRASS;

	public function __construct(int $meta = 1){
		$this->meta = $meta;
	}

    public function canBeReplaced() : bool{
        return true;
    }

    public function getName() : string{
        static $names = [
            0 => "Dead Shrub",
            1 => "Tall Grass",
            2 => "Fern"
        ];
        return $names[$this->getVariant()] ?? "Unknown";
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        $down = $this->getSide(Vector3::SIDE_DOWN)->getId();
        if($down === self::GRASS or $down === self::DIRT){
            $this->getLevel()->setBlock($blockReplace, $this, true);

            return true;
        }

        return false;
    }

    public function onNearbyBlockChange() : void{
        if($this->getSide(Vector3::SIDE_DOWN)->isTransparent()){ //Replace with common break method
            $this->getLevel()->setBlock($this, Block::get(Block::AIR), true, true);
        }
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_SHEARS;
    }

    public function getToolHarvestLevel() : int{
        return 1;
    }

    public function getDrops(Item $item) : array{
        if($this->isCompatibleWithTool($item)){
            return parent::getDrops($item);
        }

        if(mt_rand(0, 15) === 0){
            return [
                Item::get(Item::WHEAT_SEEDS)
            ];
        }

        return [];
    }

    public function getFlameEncouragement() : int{
        return 60;
    }

    public function getFlammability() : int{
        return 100;
    }
}

