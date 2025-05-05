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

use pocketmine\block\Block;

class UndyedShulkerBox extends Item{
	public function __construct(int $meta = 0){
		parent::__construct(self::UNDYED_SHULKER_BOX, $meta, 1, "Undyed Shulker Box");
	}

	public function getBlock() : Block{
		return Block::get(Block::UNDYED_SHULKER_BOX);
	}

	public function getMaxStackSize() : int{
		return 1;
	}
}


