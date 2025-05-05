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

class NetherReactor extends Solid{
	protected $id = Block::NETHER_REACTOR;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        static $prefixes = [
            "",
            "Active ",
            "Used "
        ];
        return ($prefixes[$this->meta] ?? "") . "Nether Reactor Core";
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_PICKAXE;
    }

    public function getToolHarvestLevel() : int{
        return TieredTool::TIER_WOODEN;
    }

    public function getHardness() : float{
        return 3;
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        return [
            Item::get(Item::IRON_INGOT, 0, 6),
            Item::get(Item::DIAMOND, 0, 3)
        ];
    }
}


