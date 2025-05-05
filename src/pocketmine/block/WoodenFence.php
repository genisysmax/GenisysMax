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

class WoodenFence extends Fence{
	public const FENCE_OAK = 0;
	public const FENCE_SPRUCE = 1;
	public const FENCE_BIRCH = 2;
	public const FENCE_JUNGLE = 3;
	public const FENCE_ACACIA = 4;
	public const FENCE_DARKOAK = 5;

	protected $id = self::FENCE;

	public function getHardness() : float{
		return 2;
	}

	public function getToolType() : int{
		return BlockToolType::TYPE_AXE;
	}

	public function getName() : string{
		static $names = [
			self::FENCE_OAK => "Oak Fence",
			self::FENCE_SPRUCE => "Spruce Fence",
			self::FENCE_BIRCH => "Birch Fence",
			self::FENCE_JUNGLE => "Jungle Fence",
			self::FENCE_ACACIA => "Acacia Fence",
			self::FENCE_DARKOAK => "Dark Oak Fence"
		];
		return $names[$this->getVariant()] ?? "Unknown";
	}

	public function getFuelTime() : int{
		return 300;
	}

	public function getFlameEncouragement() : int{
		return 5;
	}

	public function getFlammability() : int{
		return 20;
	}
}


