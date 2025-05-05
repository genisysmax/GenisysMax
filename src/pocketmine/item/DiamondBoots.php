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

namespace pocketmine\item;

use pocketmine\inventory\ArmorInventory;

class DiamondBoots extends Armor{
	public function __construct($meta = 0, $count = 1){
		parent::__construct(self::DIAMOND_BOOTS, $meta, $count, "Diamond Boots");
	}

	public function getArmorPoints() : int{
		return 3;
	}

	public function getMaxDurability() : int{
		return 430;
	}

    public function getArmorSlot() : int{
        return ArmorInventory::SLOT_FEET;
    }
}

