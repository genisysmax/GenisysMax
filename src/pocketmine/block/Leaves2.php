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

class Leaves2 extends Leaves{

    protected $id = self::LEAVES2;
    /** @var int */
    protected $woodType = self::WOOD2;


    public function getName() : string{
        static $names = [
            self::ACACIA => "Acacia Leaves",
            self::DARK_OAK => "Dark Oak Leaves"
        ];
        return $names[$this->getVariant()] ?? "Unknown";
    }

    public function getSaplingItem() : Item{
        return Item::get(Item::SAPLING, $this->getVariant() + 4);
    }

    public function canDropApples() : bool{
        return $this->getVariant() === self::DARK_OAK;
    }
}

