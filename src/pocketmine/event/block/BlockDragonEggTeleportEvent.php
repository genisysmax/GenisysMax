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

namespace pocketmine\event\block;

use pocketmine\block\Block;
use pocketmine\event\Cancellable;
use pocketmine\math\Vector3;

class BlockDragonEggTeleportEvent extends BlockEvent implements Cancellable{
    public static $handlerList = null;

    private Vector3 $to;

    public function __construct(Block $block, Vector3 $to){
        $this->block = $block;
        $this->to = $to;
    }

    public function getTo(): Vector3{
        return $this->to;
    }

    public function setTo(Vector3 $to): void{
        $this->to = $to;
    }

}

