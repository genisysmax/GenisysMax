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

use pocketmine\BedrockPlayer;
use pocketmine\item\Item;
use pocketmine\network\bedrock\protocol\ContainerOpenPacket;
use pocketmine\network\bedrock\protocol\types\inventory\WindowTypes;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\Player;

class CraftingTable extends Solid{

	protected $id = self::CRAFTING_TABLE;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getHardness() : float{
        return 2.5;
    }

    public function getName() : string{
        return "Crafting Table";
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_AXE;
    }

    public function onActivate(Item $item, Player $player = null) : bool{
		if($player instanceof Player){
			$player->craftingType = Player::CRAFTING_BIG;

			if($player instanceof BedrockPlayer and $player->newInventoryOpen(ContainerIds::INVENTORY)){
				$pk = new ContainerOpenPacket();
				$pk->windowId = ContainerIds::INVENTORY;
				$pk->type = WindowTypes::WORKBENCH;
				[$pk->x, $pk->y, $pk->z] = [$this->x, $this->y, $this->z];
				$player->sendDataPacket($pk);

                $player->setCurrentWindowType(WindowTypes::WORKBENCH);
			}
		}

		return true;
	}

    public function getFuelTime() : int{
        return 300;
    }
}


