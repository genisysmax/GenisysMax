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
use function mt_rand;

class Wheat extends Crops{

	protected $id = self::WHEAT_BLOCK;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Wheat Block";
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        if($this->meta >= 0x07){
            return [
                Item::get(Item::WHEAT),
                Item::get(Item::WHEAT_SEEDS, 0, mt_rand(0, 3))
            ];
        }else{
            return [
                Item::get(Item::WHEAT_SEEDS)
            ];
        }
    }

    public function getPickedItem() : Item{
        return Item::get(Item::WHEAT_SEEDS);
    }
}

