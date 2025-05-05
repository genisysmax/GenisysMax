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

use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\Player;

class Ice extends Transparent{

	protected $id = self::ICE;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Ice";
    }

    public function getHardness() : float{
        return 0.5;
    }

    public function getLightFilter() : int{
        return 2;
    }

    public function getFrictionFactor() : float{
        return 0.98;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_PICKAXE;
    }

    public function onBreak(Item $item, Player $player = null) : bool{
        if(($player === null or $player->isSurvival()) and !$item->hasEnchantment(Enchantment::SILK_TOUCH)){
            return $this->getLevel()->setBlock($this, Block::get(Block::WATER), true);
        }
        return parent::onBreak($item, $player);
    }

    public function ticksRandomly() : bool{
        return true;
    }

    public function onRandomTick() : void{
        if($this->level->getHighestAdjacentBlockLight($this->x, $this->y, $this->z) >= 12){
            $this->level->useBreakOn($this);
        }
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        return [];
    }
}

