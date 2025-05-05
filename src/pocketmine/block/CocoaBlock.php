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

class CocoaBlock extends Solid{

	protected $id = self::COCOA_BLOCK;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Cocoa Block";
    }

    public function getHardness() : float{
        return 0.2;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_AXE;
    }

    //TODO

    public function isAffectedBySilkTouch() : bool{
        return false;
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        return [
            Item::get(Item::DYE, 3, ($this->meta >> 2) === 2 ? mt_rand(2, 3) : 1)
        ];
    }

    public function getPickedItem() : Item{
        return Item::get(Item::DYE, 3);
    }
}


