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

namespace pocketmine\level\generator\object;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\Wood;
use pocketmine\level\ChunkManager;
use pocketmine\utils\Random;
use function abs;

class SpruceTree extends Tree{

	public function __construct(){
		$this->trunkBlock = Block::LOG;
		$this->leafBlock = Block::LEAVES;
		$this->type = Wood::SPRUCE;
		$this->treeHeight = 10;
	}

	public function placeObject(ChunkManager $level, int $x, int $y, int $z, Random $random){
		$this->treeHeight = $random->nextBoundedInt(4) + 6;

		$topSize = $this->treeHeight - (1 + $random->nextBoundedInt(2));
		$lRadius = 2 + $random->nextBoundedInt(2);

		$this->placeTrunk($level, $x, $y, $z, $random, $this->treeHeight - $random->nextBoundedInt(3));

		$radius = $random->nextBoundedInt(2);
		$maxR = 1;
		$minR = 0;

		for($yy = 0; $yy <= $topSize; ++$yy){
			$yyy = $y + $this->treeHeight - $yy;

			for($xx = $x - $radius; $xx <= $x + $radius; ++$xx){
				$xOff = abs($xx - $x);
				for($zz = $z - $radius; $zz <= $z + $radius; ++$zz){
					$zOff = abs($zz - $z);
					if($xOff === $radius and $zOff === $radius and $radius > 0){
						continue;
					}

					if(!BlockFactory::$solid[$level->getBlockIdAt($xx, $yyy, $zz)]){
						$level->setBlockIdAt($xx, $yyy, $zz, $this->leafBlock);
						$level->setBlockDataAt($xx, $yyy, $zz, $this->type);
					}
				}
			}

			if($radius >= $maxR){
				$radius = $minR;
				$minR = 1;
				if(++$maxR > $lRadius){
					$maxR = $lRadius;
				}
			}else{
				++$radius;
			}
		}
	}


}

