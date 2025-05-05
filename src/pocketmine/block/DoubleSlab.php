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

abstract class DoubleSlab extends Solid{

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	abstract public function getSlabId() : int;

	public function getName() : string{
		return "Double " . Block::get($this->getSlabId(), $this->getDamage())->getName();
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return [
			Item::get($this->getSlabId(), $this->getDamage(), 2)
		];
	}

	public function isAffectedBySilkTouch() : bool{
		return false;
	}

	public function getPickedItem() : Item{
		return Item::get($this->getSlabId(), $this->getDamage());
	}
}


