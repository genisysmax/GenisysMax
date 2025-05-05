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

class WoodenSlab extends Slab{

	protected $id = self::WOODEN_SLAB;

    public function getDoubleSlabId() : int{
        return self::DOUBLE_WOODEN_SLAB;
    }

    public function getHardness() : float{
        return 2;
    }

    public function getName() : string{
        static $names = [
            0 => "Oak",
            1 => "Spruce",
            2 => "Birch",
            3 => "Jungle",
            4 => "Acacia",
            5 => "Dark Oak"
        ];
        return (($this->meta & 0x08) === 0x08 ? "Upper " : "") . ($names[$this->getVariant()] ?? "") . " Wooden Slab";
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_AXE;
    }

    public function getFuelTime() : int{
        return 300;
    }

    public function getFlameEncouragement() : int{
        return 5;
    }

    public function getFlammability() : int{
        return 20;
    }
}

