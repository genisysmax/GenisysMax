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

class GrassPath extends Transparent{

	protected $id = self::GRASS_PATH;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Grass Path";
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

    public function getHardness() : float{
        return 0.6;
    }

    public function onNearbyBlockChange() : void{
        if($this->getSide(Vector3::SIDE_UP)->isSolid()){
            $this->level->setBlock($this, Block::get(Block::DIRT), true);
        }
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        return [
            Item::get(Item::DIRT)
        ];
    }
}

