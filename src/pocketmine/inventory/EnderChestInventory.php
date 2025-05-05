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

use pocketmine\entity\Human;
use pocketmine\level\Level;
use pocketmine\network\bedrock\protocol\LevelSoundEventPacket as BedrockLevelSoundEventPacket;
use pocketmine\network\bedrock\utils\BedrockUtils;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket as McpeLevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\tile\EnderChest;
use function count;

class EnderChestInventory extends ChestInventory{

	/** @var FakeBlockMenu */
	protected $holder;

	public function __construct(Human $owner){
		ContainerInventory::__construct(new FakeBlockMenu($this, $owner->getPosition()), InventoryType::get(InventoryType::ENDER_CHEST));
	}

	/**
	 * Set the holder's position to that of a tile
	 *
	 * @param EnderChest $enderChest
	 */
	public function setHolderPosition(EnderChest $enderChest){
		$this->holder->setComponents($enderChest->getX(), $enderChest->getY(), $enderChest->getZ());
		$this->holder->setLevel($enderChest->getLevel());
	}

	/**
	 * This override is here for documentation and code completion purposes only.
	 *
	 * @return FakeBlockMenu
	 */
	public function getHolder(){
		return $this->holder;
	}

	public function onOpen(Player $who){
		ContainerInventory::onOpen($who);
		$this->broadcastState(true);
	}

	public function onClose(Player $who){
		$this->broadcastState(false);
		ContainerInventory::onClose($who);
	}

	protected function broadcastState(bool $opened) : void{
		if($this->animated and count($this->getViewers()) === 1 and ($level = $this->getHolder()->getLevel()) instanceof Level){
			$this->broadcastBlockEventPacket(BlockEventPacket::TYPE_CHEST, $opened ? BlockEventPacket::DATA_CHEST_OPEN : BlockEventPacket::DATA_CHEST_CLOSED);

			BedrockUtils::splitPlayers($level->getChunkPlayers($this->getHolder()->getX() >> 4, $this->getHolder()->getZ() >> 4), $pw10Players, $bedrockPlayers);

			if(!empty($pw10Players)){
				$pk = new McpeLevelSoundEventPacket();
				$pk->sound = $opened ? McpeLevelSoundEventPacket::SOUND_CHEST_OPEN : McpeLevelSoundEventPacket::SOUND_CHEST_CLOSED;
				list($pk->x, $pk->y, $pk->z) = [$this->getHolder()->getX() + 0.5, $this->getHolder()->getY() + 0.5, $this->getHolder()->getZ() + 0.5];
				$level->getServer()->broadcastPacket($pw10Players, $pk);
			}

			if(!empty($bedrockPlayers)){
				$bk = new BedrockLevelSoundEventPacket();
				$bk->sound = $opened ? BedrockLevelSoundEventPacket::SOUND_ENDERCHEST_OPEN : BedrockLevelSoundEventPacket::SOUND_ENDERCHEST_CLOSED;
				$bk->position = $this->getHolder()->add(0.5, 0.5, 0.5);
				$level->getServer()->broadcastPacket($bedrockPlayers, $bk);
			}
		}
	}
}

