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

use pocketmine\block\Liquid;
use pocketmine\entity\Entity;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\math\Vector3;
use function min;
use function mt_rand;

class ChorusFruit extends Food{

	public function __construct(int $meta = 0){
		parent::__construct(self::CHORUS_FRUIT, $meta, 1, "Chorus Fruit");
	}

	public function getFoodRestore() : int{
		return 4;
	}

	public function getSaturationRestore() : float{
		return 2.4;
	}

	public function onConsume(Entity $entity): void{
		$level = $entity->getLevel();

		$minX = $entity->getFloorX() - 8;
		$minY = min($entity->getFloorY(), $entity->getLevel()->getWorldHeight()) - 8;
		$minZ = $entity->getFloorZ() - 8;

		$maxX = $minX + 16;
		$maxY = $minY + 16;
		$maxZ = $minZ + 16;

		for($attempts = 0; $attempts < 16; ++$attempts){
			$x = mt_rand($minX, $maxX);
			$y = mt_rand($minY, $maxY);
			$z = mt_rand($minZ, $maxZ);

			while($y >= 0 and !$level->getBlockAt($x, $y, $z)->isSolid()){
				$y--;
			}
			if($y < 0){
				continue;
			}

			$blockUp = $level->getBlockAt($x, $y + 1, $z);
			$blockUp2 = $level->getBlockAt($x, $y + 2, $z);
			if($blockUp->isSolid() or $blockUp instanceof Liquid or $blockUp2->isSolid() or $blockUp2 instanceof Liquid){
				continue;
			}

			//Sounds are broadcasted at both source and destination
			$level->addSound(new EndermanTeleportSound($entity->asVector3()));
            $entity->teleport(new Vector3($x + 0.5, $y + 1, $z + 0.5));
			$level->addSound(new EndermanTeleportSound($entity->asVector3()));

			break;
		}
	}

	public function getCooldownTicks() : int{
		return 20;
	}
}


