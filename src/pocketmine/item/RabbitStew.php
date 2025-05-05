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

class RabbitStew extends Food{
	public function __construct(int $meta = 0){
		parent::__construct(self::RABBIT_STEW, $meta, 1, "Rabbit Stew");
	}

	public function getMaxStackSize() : int{
		return 1;
	}

	public function getFoodRestore() : int{
		return 10;
	}

	public function getSaturationRestore() : float{
		return 12;
	}

	public function getResidue(){
		return Item::get(Item::BOWL);
	}
}


