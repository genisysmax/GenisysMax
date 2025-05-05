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

class Planks extends Solid{
    public const OAK = 0;
    public const SPRUCE = 1;
    public const BIRCH = 2;
    public const JUNGLE = 3;
    public const ACACIA = 4;
    public const DARK_OAK = 5;

    protected $id = self::WOODEN_PLANKS;

    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }

    public function getHardness() : float{
        return 2;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_AXE;
    }

    public function getName() : string{
        static $names = [
            self::OAK => "Oak Wood Planks",
            self::SPRUCE => "Spruce Wood Planks",
            self::BIRCH => "Birch Wood Planks",
            self::JUNGLE => "Jungle Wood Planks",
            self::ACACIA => "Acacia Wood Planks",
            self::DARK_OAK => "Dark Oak Wood Planks"
        ];
        return $names[$this->getVariant()] ?? "Unknown";
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


