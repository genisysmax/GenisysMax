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
use pocketmine\item\TieredTool;
use function mt_rand;

class LapisOre extends Solid{

	protected $id = self::LAPIS_ORE;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getHardness() : float{
        return 3;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_PICKAXE;
    }

    public function getToolHarvestLevel() : int{
        return TieredTool::TIER_STONE;
    }

    public function getName() : string{
        return "Lapis Lazuli Ore";
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        return [
            Item::get(Item::DYE, 4, mt_rand(4, 8))
        ];
    }

    protected function getXpDropAmount() : int{
        return mt_rand(2, 5);
    }
}

