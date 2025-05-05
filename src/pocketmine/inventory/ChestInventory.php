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

namespace pocketmine\inventory;

use pocketmine\level\Level;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\tile\Chest;
use function count;

class ChestInventory extends ContainerInventory{

	/** @var bool */
	protected $animated = true;

	public function __construct(Chest $tile){
		parent::__construct($tile, InventoryType::get(InventoryType::CHEST));
	}

	/**
	 * @return Chest
	 */
	public function getHolder(){
		return $this->holder;
	}

	/**
	 * @return bool
	 */
	public function isAnimated() : bool{
		return $this->animated;
	}

	/**
	 * @param bool $animated
	 */
	public function setAnimated(bool $animated = true) : void{
		$this->animated = $animated;
	}

	public function onOpen(Player $who){
		parent::onOpen($who);

		if($this->animated and count($this->getViewers()) === 1 and ($level = $this->getHolder()->getLevel()) instanceof Level){
			$this->broadcastBlockEventPacket(BlockEventPacket::TYPE_CHEST, BlockEventPacket::DATA_CHEST_OPEN);
			$level->broadcastLevelSoundEvent($this->getHolder()->add(0.5, 0.5, 0.5), LevelSoundEventPacket::SOUND_CHEST_OPEN);
		}
	}

	public function onClose(Player $who){
		if($this->animated and count($this->getViewers()) === 1 and ($level = $this->getHolder()->getLevel()) instanceof Level){
			$this->broadcastBlockEventPacket(BlockEventPacket::TYPE_CHEST, BlockEventPacket::DATA_CHEST_CLOSED);
			$level->broadcastLevelSoundEvent($this->getHolder()->add(0.5, 0.5, 0.5), LevelSoundEventPacket::SOUND_CHEST_CLOSED);
		}
		parent::onClose($who);
	}

	protected function broadcastBlockEventPacket(int $eventType, int $eventData){
		$pk = new BlockEventPacket();
		$pk->x = $this->getHolder()->getX();
		$pk->y = $this->getHolder()->getY();
		$pk->z = $this->getHolder()->getZ();
		$pk->eventType = $eventType;
		$pk->eventData = $eventData;
		$this->getHolder()->getLevel()->addChunkPacket($this->getHolder()->getX() >> 4, $this->getHolder()->getZ() >> 4, $pk);
	}
}


