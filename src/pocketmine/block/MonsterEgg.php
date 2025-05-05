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

class MonsterEgg extends Solid{

	protected $id = self::MONSTER_EGG;

	public const STONE_MONSTER_EGG = 0;
	public const COBBLESTONE_MONSTER_EGG = 1;
	public const STONE_BRICK_MONSTER_EGG = 2;
	public const MOSSY_STONE_BRICK_MONSTER_EGG = 3;
	public const CRACKED_STONE_BRICK_MONSTER_EGG = 4;
	public const CHISELED_STONE_BRICK_MONSTER_EGG = 5;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getName() : string{
        return match ($this->meta) {
            self::STONE_MONSTER_EGG => "Stone Monster Egg",
            self::COBBLESTONE_MONSTER_EGG => "Cobblestone Monster Egg",
            self::STONE_BRICK_MONSTER_EGG => "Stone Brick Monster Egg",
            self::MOSSY_STONE_BRICK_MONSTER_EGG => "Mossy Stone Brick Monster Egg",
            self::CRACKED_STONE_BRICK_MONSTER_EGG => "Cracked Stone Brick Monster Egg",
            self::CHISELED_STONE_BRICK_MONSTER_EGG => "Chiseled Stone Brick Monster Egg",
            default => "Infested Block",
        };

    }

	public function getHardness() : float{
		return 0.75;
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return [];
	}

	public function onBreak(Item $item, Player $player = null) : bool{
		// TODO: Spawn silverfish

		return parent::onBreak($item, $player);
	}
}

