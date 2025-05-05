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
use pocketmine\Player;

class GlowingRedstoneOre extends RedstoneOre{

	protected $id = self::GLOWING_REDSTONE_ORE;

    protected $itemId = self::REDSTONE_ORE;

    public function getName() : string{
        return "Glowing Redstone Ore";
    }

    public function getLightLevel() : int{
        return 9;
    }

    public function onActivate(Item $item, Player $player = null) : bool{
        return false;
    }

    public function onNearbyBlockChange() : void{

    }

    public function ticksRandomly() : bool{
        return true;
    }

    public function onRandomTick() : void{
        $this->getLevel()->setBlock($this, Block::get(Block::REDSTONE_ORE, $this->meta), false, false);
    }
}

