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

namespace pocketmine\level\generator\populator;

use pocketmine\block\Water;
use pocketmine\level\ChunkManager;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;

class Pond extends Populator{
	private $waterOdd = 4;
	private $lavaOdd = 4;
	private $lavaSurfaceOdd = 4;

	public function populate(ChunkManager $level, int $chunkX, int $chunkZ, Random $random){
		if($random->nextRange(0, $this->waterOdd) === 0){
			$x = $random->nextRange($chunkX << 4, ($chunkX << 4) + 16);
			$y = $random->nextBoundedInt(128);
			$z = $random->nextRange($chunkZ << 4, ($chunkZ << 4) + 16);
			$pond = new \pocketmine\level\generator\object\Pond($random, new Water());
			if($pond->canPlaceObject($level, $v = new Vector3($x, $y, $z))){
				$pond->placeObject($level, $v);
			}
		}
	}

	public function setWaterOdd(int $waterOdd){
		$this->waterOdd = $waterOdd;
	}

	public function setLavaOdd(int $lavaOdd){
		$this->lavaOdd = $lavaOdd;
	}

	public function setLavaSurfaceOdd(int $lavaSurfaceOdd){
		$this->lavaSurfaceOdd = $lavaSurfaceOdd;
	}
}

