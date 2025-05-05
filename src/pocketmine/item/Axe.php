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
use pocketmine\entity\Entity;

class Axe extends TieredTool {

	public function getBlockToolType(): int {
		return Tool::TYPE_AXE;
	}

	public function getBlockToolHarvestLevel(): int {
		return $this->tier;
	}

	public function getAttackPoints(): int {
		return self::getBaseDamageFromTier($this->tier) - 1;
	}

	public function onDestroyBlock(Block $block): bool {
		if ($block->getHardness() > 0) {
			return $this->applyDamage(1);
		}
		return false;
	}

	public function onAttackEntity(Entity $victim): bool {
		return $this->applyDamage(2);
	}

	public function isAxe(){
		return $this->tier;
	}
}

