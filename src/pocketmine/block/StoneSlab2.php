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

class StoneSlab2 extends StoneSlab{
    public const TYPE_RED_SANDSTONE = 0;
    public const TYPE_PURPUR = 1;
    public const TYPE_PRISMARINE = 2;
    public const TYPE_DARK_PRISMARINE = 3;
    public const TYPE_PRISMARINE_BRICKS = 4;
    public const TYPE_MOSSY_COBBLESTONE = 5;
    public const TYPE_SMOOTH_SANDSTONE = 6;
    public const TYPE_RED_NETHER_BRICK = 7;

    protected $id = self::STONE_SLAB2;

    public function getDoubleSlabId() : int{
        return self::DOUBLE_STONE_SLAB2;
    }

    public function getName() : string{
        static $names = [
            self::TYPE_RED_SANDSTONE => "Red Sandstone",
            self::TYPE_PURPUR => "Purpur",
            self::TYPE_PRISMARINE => "Prismarine",
            self::TYPE_DARK_PRISMARINE => "Dark Prismarine",
            self::TYPE_PRISMARINE_BRICKS => "Prismarine Bricks",
            self::TYPE_MOSSY_COBBLESTONE => "Mossy Cobblestone",
            self::TYPE_SMOOTH_SANDSTONE => "Smooth Sandstone",
            self::TYPE_RED_NETHER_BRICK => "Red Nether Brick"
        ];

        return (($this->meta & 0x08) > 0 ? "Upper " : "") . ($names[$this->getVariant()] ?? "") . " Slab";
    }
}

