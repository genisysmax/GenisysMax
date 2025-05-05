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

use pocketmine\block\BlockIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\NetworkNbtSerializer;
use pocketmine\network\mcpe\protocol\BlockEntityDataPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Player;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;

#[\AllowDynamicProperties]
class WindowInventory extends CustomInventory{

	public const TYPE_CHEST   = 0; //27 slots
	public const TYPE_HOPPER  = 1; //5 slots
	public const TYPE_DROPPER = 2; //9 slots in grid

	/** @var int */
	protected $blockId;
	/** @var string */
	protected $tileId;
	/** @var string */
	protected $customName = "";

	public function __construct($type = self::TYPE_CHEST, string $customName = ""){
		if($type instanceof Player){ //TODO
			$type = self::TYPE_CHEST;
		}
		switch($type){
			case self::TYPE_CHEST:
				$inventoryType = InventoryType::CHEST;
				$this->blockId = BlockIds::CHEST;
				$this->tileId = Tile::CHEST;
				break;
			case self::TYPE_HOPPER:
				$inventoryType = InventoryType::HOPPER;
				$this->blockId = BlockIds::HOPPER_BLOCK;
				$this->tileId = Tile::HOPPER;
				break;
			case self::TYPE_DROPPER:
				$inventoryType = InventoryType::DROPPER;
				$this->blockId = BlockIds::DROPPER;
				$this->tileId = Tile::DROPPER;
				break;
			default:
				throw new \InvalidArgumentException("Unknown window inventory type");
		}
		$this->customName = $customName;
		parent::__construct(new WindowHolder($this), InventoryType::get($inventoryType));
	}

	/**
	 * @param Player $who
	 */
	public function onOpen(Player $who){
		$this->holder->setComponents((int) floor($who->x), (int) floor($who->y) - 3, (int) floor($who->z));

		$pk = new UpdateBlockPacket();
		$pk->x = $this->holder->x;
		$pk->y = $this->holder->y;
		$pk->z = $this->holder->z;
		$pk->blockId = $this->blockId;
		$pk->blockData = 0;
		$pk->flags = UpdateBlockPacket::FLAG_ALL;
		$who->sendDataPacket($pk);

		$tag = CompoundTag::create()
			->setString("id", $this->tileId)
			->setInt("x", (int) $this->holder->x)
			->setInt("y", (int) $this->holder->y)
			->setInt("z", (int) $this->holder->z);
		if($this->customName !== ""){
			$tag->setString("CustomName", TextFormat::RESET . $this->customName);
		}
		$pk = new BlockEntityDataPacket();
		$pk->x = $this->holder->x;
		$pk->y = $this->holder->y;
		$pk->z = $this->holder->z;
		$pk->namedtag = (new NetworkNbtSerializer())->write(new TreeRoot($tag));
		$who->sendDataPacket($pk);

		parent::onOpen($who);
	}

	/**
	 * @param Player $who
	 */
	public function onClose(Player $who){
		$holder = $this->holder;

		$pk = new UpdateBlockPacket();
		$pk->x = $holder->x;
		$pk->y = $holder->y;
		$pk->z = $holder->z;
		$pk->blockId = $who->level->getBlockIdAt($holder->x, $holder->y, $holder->z);
		$pk->blockData = $who->level->getBlockDataAt($holder->x, $holder->y, $holder->z);
		$pk->flags = UpdateBlockPacket::FLAG_ALL_PRIORITY;
		
		$who->sendDataPacket($pk);
	}

	/**
	 * @return string
	 */
	public function getCustomName() : string{
		return $this->customName;
	}

	/**
	 * @param string $customName
	 */
	public function setCustomName(string $customName = "") : void{
		$this->customName = $customName;
	}
}


