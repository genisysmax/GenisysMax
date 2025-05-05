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

class Potato extends Crops{

    protected $id = self::POTATO_BLOCK;

    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }

    public function getName() : string{
        return "Potato Block";
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        $result = [
            Item::get(Item::POTATO, 0, $this->getDamage() >= 0x07 ? mt_rand(1, 5) : 1)
        ];
        if($this->getDamage() >= 7 && mt_rand(0, 49) === 0){
            $result[] = Item::get(Item::POISONOUS_POTATO);
        }
        return $result;
    }

    public function getPickedItem() : Item{
        return Item::get(Item::POTATO);
    }
}

