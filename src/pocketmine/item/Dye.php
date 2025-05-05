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

use pocketmine\utils\Color;

class Dye extends Item{

	public const BLACK = 0, INK_SAC = 0;
	public const RED = 1;
	public const GREEN = 2;
	public const BROWN = 3, COCOA_BEANS = 3;
	public const BLUE = 4, LAPIS_LAZULI = 4;
	public const PURPLE = 5;
	public const CYAN = 6;
	public const LIGHT_GRAY = 7;
	public const GRAY = 8;
	public const PINK = 9;
	public const LIME = 10;
	public const YELLOW = 11;
	public const LIGHT_BLUE = 12;
	public const MAGENTA = 13;
	public const ORANGE = 14;
	public const WHITE = 15, BONE_MEAL = 15;

	public function __construct($meta = 0, $count = 1){
		parent::__construct(self::DYE, $meta, $count, "Dye");
	}

    public function getColorFromMeta() : Color{
        return match ($this->meta) {
            default => new Color(0xf0, 0xf0, 0xf0),
            1 => new Color(0xf9, 0x80, 0x1d),
            2 => new Color(0xc7, 0x4e, 0xbd),
            3 => new Color(0x3a, 0xb3, 0xda),
            4 => new Color(0xfe, 0xd8, 0x3d),
            5 => new Color(0x80, 0xc7, 0x1f),
            6 => new Color(0xf3, 0x8b, 0xaa),
            7 => new Color(0x47, 0x4f, 0x52),
            8 => new Color(0x9d, 0x9d, 0x97),
            9 => new Color(0x16, 0x9c, 0x9c),
            10 => new Color(0x89, 0x32, 0xb8),
            11 => new Color(0x3c, 0x44, 0xaa),
            12 => new Color(0x83, 0x54, 0x32),
            13 => new Color(0x5e, 0x7c, 0x16),
            14 => new Color(0xb0, 0x2e, 0x26),
            15 => new Color(0x1d, 0x1d, 0x21)
        };
    }
}



