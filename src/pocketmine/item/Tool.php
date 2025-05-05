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

use pocketmine\block\Block;
use pocketmine\item\enchantment\Enchantment;

abstract class Tool extends Durable{
	public const TIER_WOODEN = 1;
	public const TIER_GOLD = 2;
	public const TIER_STONE = 3;
	public const TIER_IRON = 4;
	public const TIER_DIAMOND = 5;

	public const TYPE_NONE = 0;
	public const TYPE_SWORD = 1 << 0;
	public const TYPE_SHOVEL = 1 << 1;
	public const TYPE_PICKAXE = 1 << 2;
	public const TYPE_AXE = 1 << 3;
	public const TYPE_SHEARS = 1 << 4;
	public const TYPE_HOE = 1 << 5;

    public function __construct($id, $meta = 0, $count = 1, $name = "Unknown"){
		parent::__construct($id, $meta, $count, $name);
	}

	public function getMaxStackSize() : int{
		return 1;
	}

    public function getMiningEfficiency(Block $block) : float{
        $efficiency = 1;
        if(($block->getToolType() & $this->getBlockToolType()) !== 0){
            $efficiency = $this->getBaseMiningEfficiency();
            if(($enchantment = $this->getEnchantment(Enchantment::EFFICIENCY)) !== null){
                $efficiency += $enchantment->getLevel() ** 2 + 1;
            }
        }

        return $efficiency;
    }

    protected function getBaseMiningEfficiency() : float{
        return 1;
    }

	public function isTool(){
		return ($this->id === self::FLINT_STEEL or $this->id === self::SHEARS or $this->id === self::BOW or $this->isPickaxe() !== false or $this->isAxe() !== false or $this->isShovel() !== false or $this->isSword() !== false);
	}
}

