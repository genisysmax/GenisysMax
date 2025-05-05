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

class SeaLantern extends Transparent{

	protected $id = self::SEA_LANTERN;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Sea Lantern";
    }

    public function getHardness() : float{
        return 0.3;
    }

    public function getLightLevel() : int{
        return 15;
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        return [
            Item::get(Item::PRISMARINE_CRYSTALS, 0, 3)
        ];
    }
}


