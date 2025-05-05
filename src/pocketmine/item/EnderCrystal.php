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

use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\object\EnderCrystal as Crystal;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;

class EnderCrystal extends Item{

	/**
	 * EyeOfEnder constructor.
	 *
	 * @param int $meta
	 * @param int $count
	 */
	public function __construct($meta = 0, $count = 1){
		parent::__construct(self::END_CRYSTAL, 0, $count, 'Ender Crystal');
	}

    public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector) : bool{
        $id = $blockClicked->getId();
		if($id !== BlockIds::OBSIDIAN and $id !== BlockIds::BEDROCK){
			return false;
		}

		$level = $player->getLevel();
		assert($level !== null);

		if(!$level->getBlock($blockClicked->asVector3()->add(0, 1, 0))->getId() != 0){
			$pos = $blockClicked;
			$entities = $level->getNearbyEntities(new AxisAlignedBB($pos->getX(), $pos->getY(), $pos->getZ(), $pos->getX() + 1, $pos->getY() + 2, $pos->getZ() + 1));
			if(count($entities) === 0 && $level->getBlock($pos->getSide(Vector3::SIDE_UP)) instanceof Air && $level->getBlock($pos->getSide(Vector3::SIDE_UP, 2)) instanceof Air){
				$npc = new Crystal($player->level, EntityDataHelper::createBaseNBT($blockClicked->add(0.5, 1, 0.5), null, $player->getYaw(), $player->getPitch()));
				$npc->spawnToAll();

                $this->pop();

				return true;
			}
		}

		return false;
	}
}

