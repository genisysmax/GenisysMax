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

namespace pocketmine\event\level;

use pocketmine\event\Event;

/**
 * Called when a Level is loaded
 */
class LevelCreationEvent extends Event{
	public static $handlerList = null;

    public function __construct(
        private readonly string $levelName,
        private string $levelClass
    ) {}

    public function getLevelName() : string{
        return $this->levelName;
    }

    public function setLevelClass(string $class) : void{
        $this->levelClass = $class;
    }

    public function getLevelClass() : string{
        return $this->levelClass;
    }
}

