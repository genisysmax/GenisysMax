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

namespace pocketmine\network\bedrock\protocol\types;

use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\InventoryTransactionPacket;
use pocketmine\network\bedrock\protocol\types\inventory\ItemInstance;

class UseItemOnActorTransactionData extends TransactionData{
	public const ACTION_INTERACT = 0;
	public const ACTION_ATTACK = 1;

	/** @var int */
	private $actorRuntimeId;
	/** @var int */
	private $actionType;
	/** @var int */
	private $hotbarSlot;
	/** @var ItemInstance */
	private $itemInHand;
	/** @var Vector3 */
	private $playerPos;
	/** @var Vector3 */
	private $clickPos;

	/**
	 * @return int
	 */
	public function getActorRuntimeId() : int{
		return $this->actorRuntimeId;
	}

	/**
	 * @return int
	 */
	public function getActionType() : int{
		return $this->actionType;
	}

	/**
	 * @return int
	 */
	public function getHotbarSlot() : int{
		return $this->hotbarSlot;
	}

	/**
	 * @return ItemInstance
	 */
	public function getItemInHand() : ItemInstance{
		return $this->itemInHand;
	}

	/**
	 * @return Vector3
	 */
	public function getPlayerPos() : Vector3{
		return $this->playerPos;
	}

	/**
	 * @return Vector3
	 */
	public function getClickPos() : Vector3{
		return $this->clickPos;
	}

	public function getTypeId() : int{
		return InventoryTransactionPacket::TYPE_USE_ITEM_ON_ACTOR;
	}

	protected function decodeData(DataPacket $stream) : void{
		$this->actorRuntimeId = $stream->getEntityRuntimeId();
		$this->actionType = $stream->getUnsignedVarInt();
		$this->hotbarSlot = $stream->getVarInt();
		$this->itemInHand = $stream->getItemInstance();
		$this->playerPos = $stream->getVector3();
		$this->clickPos = $stream->getVector3();
	}

	protected function encodeData(DataPacket $stream) : void{
		$stream->putEntityRuntimeId($this->actorRuntimeId);
		$stream->putUnsignedVarInt($this->actionType);
		$stream->putVarInt($this->hotbarSlot);
		$stream->putItemInstance($this->itemInHand);
		$stream->putVector3($this->playerPos);
		$stream->putVector3($this->clickPos);
	}

	public static function new(array $actions, int $actorRuntimeId, int $actionType, int $hotbarSlot, Item $itemInHand, Vector3 $playerPos, Vector3 $clickPos) : self{
		$result = new self;
		$result->actions = $actions;
		$result->actorRuntimeId = $actorRuntimeId;
		$result->actionType = $actionType;
		$result->hotbarSlot = $hotbarSlot;
		$result->itemInHand = $itemInHand;
		$result->playerPos = $playerPos;
		$result->clickPos = $clickPos;
		return $result;
	}
}


