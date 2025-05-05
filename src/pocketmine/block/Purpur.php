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

class Purpur extends Quartz{

	protected $id = self::PURPUR_BLOCK;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        static $names = [
            self::NORMAL => "Purpur Block",
            self::CHISELED => "Chiseled Purpur", //wtf?
            self::PILLAR => "Purpur Pillar"
        ];

        return $names[$this->getVariant()] ?? "Unknown";
    }

    public function getHardness() : float{
        return 1.5;
    }

    public function getBlastResistance() : float{
        return 30;
    }
}

