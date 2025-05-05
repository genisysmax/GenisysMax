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

use pocketmine\entity\Entity;

class Slime extends Solid{

	protected $id = self::SLIME_BLOCK;

    public function __construct(){

    }

    public function hasEntityCollision() : bool{
        return true;
    }

    public function getHardness() : float{
        return 0;
    }

    public function getName() : string{
        return "Slime Block";
    }

    public function onEntityCollideUpon(Entity $entity) : void{
        $entity->resetFallDistance();
    }
}

