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

class UseItemTransactionData extends TransactionData{
	public const ACTION_CLICK_BLOCK = 0;
	public const ACTION_CLICK_AIR = 1;
	public const ACTION_BREAK_BLOCK = 2;

	/** @var int */
	protected $actionType;
	/** @var Vector3 */
    protected $blockPos;
	/** @var int */
    protected $face;
	/** @var int */
    protected $hotbarSlot;
	/** @var ItemInstance */
    protected $itemInHand;
	/** @var Vector3 */
    protected $playerPos;
	/** @var Vector3 */
    protected $clickPos;
	/** @var int */
    protected $blockRuntimeId;

	/**
	 * @return int
	 */
	public function getActionType() : int{
		return $this->actionType;
	}

	/**
	 * @return Vector3
	 */
	public function getBlockPos() : Vector3{
		return $this->blockPos;
	}

	/**
	 * @return int
	 */
	public function getFace() : int{
		return $this->face;
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

	/**
	 * @return int
	 */
	public function getBlockRuntimeId() : int{
		return $this->blockRuntimeId;
	}

	public function getTypeId() : int{
		return InventoryTransactionPacket::TYPE_USE_ITEM;
	}

	protected function decodeData(DataPacket $stream) : void{
		$this->actionType = $stream->getUnsignedVarInt();
		$this->blockPos = new Vector3();
		$stream->getBlockPosition($this->blockPos->x, $this->blockPos->y, $this->blockPos->z);
		$this->face = $stream->getVarInt();
		$this->hotbarSlot = $stream->getVarInt();
		$this->itemInHand = $stream->getItemInstance();
		$this->playerPos = $stream->getVector3();
		$this->clickPos = $stream->getVector3();
		$this->blockRuntimeId = $stream->getUnsignedVarInt();
	}

	protected function encodeData(DataPacket $stream) : void{
		$stream->putUnsignedVarInt($this->actionType);
		$stream->putBlockPosition($this->blockPos->x, $this->blockPos->y, $this->blockPos->z);
		$stream->putVarInt($this->face);
		$stream->putVarInt($this->hotbarSlot);
		$stream->putItemInstance($this->itemInHand);
		$stream->putVector3($this->playerPos);
		$stream->putVector3($this->clickPos);
		$stream->putUnsignedVarInt($this->blockRuntimeId);
	}

	public static function new(array $actions, int $actionType, Vector3 $blockPos, int $face, int $hotbarSlot, Item $itemInHand, Vector3 $playerPos, Vector3 $clickPos, int $blockRuntimeId) : self{
		$result = new self;
		$result->actions = $actions;
		$result->actionType = $actionType;
		$result->blockPos = $blockPos;
		$result->face = $face;
		$result->hotbarSlot = $hotbarSlot;
		$result->itemInHand = $itemInHand;
		$result->playerPos = $playerPos;
		$result->clickPos = $clickPos;
		$result->blockRuntimeId = $blockRuntimeId;
		return $result;
	}
}


