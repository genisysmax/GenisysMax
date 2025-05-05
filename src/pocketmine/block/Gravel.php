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

class Gravel extends Fallable{

	protected $id = self::GRAVEL;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Gravel";
    }

    public function getHardness() : float{
        return 0.6;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_SHOVEL;
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        if(mt_rand(1, 10) === 1){
            return [
                Item::get(Item::FLINT)
            ];
        }

        return parent::getDropsForCompatibleTool($item);
    }
}

