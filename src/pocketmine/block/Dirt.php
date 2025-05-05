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

use pocketmine\item\Hoe;
use pocketmine\item\Item;
use pocketmine\Player;

class Dirt extends Solid{

	protected $id = self::DIRT;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getHardness() : float{
        return 0.5;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_SHOVEL;
    }

    public function getName() : string{
        if($this->meta === 1){
            return "Coarse Dirt";
        }
        return "Dirt";
    }

    public function onActivate(Item $item, Player $player = null) : bool{
        if($item instanceof Hoe){
            $item->applyDamage(1);
            if($this->meta === 1){
                $this->getLevel()->setBlock($this, Block::get(Block::DIRT), true);
            }else{
                $this->getLevel()->setBlock($this, Block::get(Block::FARMLAND), true);
            }

            return true;
        }

        return false;
    }
}

