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



namespace pocketmine\inventory;

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\tile\ShulkerBox;

class ShulkerBoxInventory extends ContainerInventory{
	protected $holder;

	/**
	 * ShulkerBoxInventory constructor.
	 * @param ShulkerBox $tile
	 */
	public function __construct(ShulkerBox $tile){
		parent::__construct($tile, InventoryType::get(InventoryType::SHULKER_BOX));
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Shulker Box";
	}

	/**
	 * @return int
	 */
	public function getSize() : int{
		return 27;
	}

	/**
	 * @return ShulkerBox
	 */
	public function getHolder(){
		return $this->holder;
	}

	/**
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function canAddItem(Item $item): bool
    {
		if($item->getId() === Block::SHULKER_BOX){
			return false;
		}
		return parent::canAddItem($item);
	}

	public function onOpen(Player $who){
		parent::onOpen($who);
		if(count($this->getViewers()) === 1){
			$pk = new BlockEventPacket();
			$pk->x = $this->getHolder()->getFloorX();
			$pk->y = $this->getHolder()->getFloorY();
			$pk->z = $this->getHolder()->getFloorZ();
			$pk->eventType = 1;
			$pk->eventData = 2;
			if(($level = $this->getHolder()->getLevel()) instanceof Level){
				$level->broadcastLevelSoundEvent($this->getHolder(), LevelSoundEventPacket::SOUND_SHULKERBOX_OPEN);
				$level->addChunkPacket($this->getHolder()->getFloorX() >> 4, $this->getHolder()->getFloorZ() >> 4, $pk);
			}
		}
	}

	public function onClose(Player $who){
		if(count($this->getViewers()) === 1){
			$pk = new BlockEventPacket();
			$pk->x = $this->getHolder()->getFloorX();
			$pk->y = $this->getHolder()->getFloorY();
			$pk->z = $this->getHolder()->getFloorZ();
			$pk->eventType = 1;
			$pk->eventData = 0;
			if(($level = $this->getHolder()->getLevel()) instanceof Level){
				$level->broadcastLevelSoundEvent($this->getHolder(), LevelSoundEventPacket::SOUND_SHULKERBOX_CLOSED);
				$level->addChunkPacket($this->getHolder()->getFloorX() >> 4, $this->getHolder()->getFloorZ() >> 4, $pk);
			}
		}
		$this->getHolder()->saveNBT();
		parent::onClose($who);
	}
}

