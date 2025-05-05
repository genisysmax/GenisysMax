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

use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\types\inventory\ItemInstance;
use pocketmine\network\mcpe\NetworkBinaryStream;

class NetworkInventoryAction{
	public const SOURCE_CONTAINER = 0;

	public const SOURCE_WORLD = 2; //drop/pickup item actor
	public const SOURCE_CREATIVE = 3;
	public const SOURCE_TODO = 99999;

	/**
	 * Fake window IDs for the SOURCE_TODO type (99999)
	 *
	 * These identifiers are used for inventory source types which are not currently implemented server-side in MCPE.
	 * As a general rule of thumb, anything that doesn't have a permanent inventory is client-side. These types are
	 * to allow servers to track what is going on in client-side windows.
	 *
	 * Expect these to change in the future.
	 */
	public const SOURCE_TYPE_CRAFTING_RESULT = -4;
	public const SOURCE_TYPE_CRAFTING_USE_INGREDIENT = -5;

	public const SOURCE_TYPE_ANVIL_RESULT = -12;
	public const SOURCE_TYPE_ANVIL_OUTPUT = -13;

	public const SOURCE_TYPE_ENCHANT_OUTPUT = -17;

	public const SOURCE_TYPE_TRADING_INPUT_1 = -20;
	public const SOURCE_TYPE_TRADING_INPUT_2 = -21;
	public const SOURCE_TYPE_TRADING_USE_INPUTS = -22;
	public const SOURCE_TYPE_TRADING_OUTPUT = -23;

	public const SOURCE_TYPE_BEACON = -24;

	public const ACTION_MAGIC_SLOT_CREATIVE_DELETE_ITEM = 0;
	public const ACTION_MAGIC_SLOT_CREATIVE_CREATE_ITEM = 1;

	public const ACTION_MAGIC_SLOT_DROP_ITEM = 0;
	public const ACTION_MAGIC_SLOT_PICKUP_ITEM = 1;

	/** @var int */
	public $sourceType;
	/** @var int */
	public $windowId;
	/** @var int */
	public $sourceFlags = 0;
	/** @var int */
	public $inventorySlot;
	/** @var ItemInstance */
	public $oldItem;
	/** @var ItemInstance */
	public $newItem;

	/** @var bool */
	protected $isCraftingPart = false;
	/** @var bool */
	protected $isFinalCraftingPart = false;

	/**
	 * @param NetworkBinaryStream $packet
	 *
	 * @return $this
	 * @throws \UnexpectedValueException
	 * @throws \OutOfBoundsException
	 */
	public function read(NetworkBinaryStream $packet) : NetworkInventoryAction{
		/** @var DataPacket $packet */
		$this->sourceType = $packet->getUnsignedVarInt();

		switch($this->sourceType){
			case self::SOURCE_CONTAINER:
				$this->windowId = $packet->getVarInt();
				break;
			case self::SOURCE_WORLD:
				$this->sourceFlags = $packet->getUnsignedVarInt();
				break;
			case self::SOURCE_CREATIVE:
				break;
			case self::SOURCE_TODO:
				$this->windowId = $packet->getVarInt();
				break;
			default:
				throw new \UnexpectedValueException("Unknown inventory action source type $this->sourceType");
		}

		$this->inventorySlot = $packet->getUnsignedVarInt();
		$this->oldItem = $packet->getItemInstance();
		$this->newItem = $packet->getItemInstance();

		if(
			$this->sourceType === self::SOURCE_TODO and (
				$this->windowId === self::SOURCE_TYPE_CRAFTING_RESULT or
				$this->windowId === self::SOURCE_TYPE_CRAFTING_USE_INGREDIENT
			)
		){
			$this->isCraftingPart = true;
			if(!$this->oldItem->stack->isNull() and $this->newItem->stack->isNull()){
				$this->isFinalCraftingPart = true;
			}
		}

		return $this;
	}

	/**
	 * @param NetworkBinaryStream $packet
	 *
	 * @throws \InvalidArgumentException
	 */
	public function write(NetworkBinaryStream $packet) : void{
		/** @var DataPacket $packet */

		$packet->putUnsignedVarInt($this->sourceType);

		switch($this->sourceType){
			case self::SOURCE_CONTAINER:
				$packet->putVarInt($this->windowId);
				break;
			case self::SOURCE_WORLD:
				$packet->putUnsignedVarInt($this->sourceFlags);
				break;
			case self::SOURCE_CREATIVE:
				break;
			case self::SOURCE_TODO:
				$packet->putVarInt($this->windowId);
				break;
			default:
				throw new \InvalidArgumentException("Unknown inventory action source type $this->sourceType");
		}

		$packet->putUnsignedVarInt($this->inventorySlot);
		$packet->putItemInstance($this->oldItem);
		$packet->putItemInstance($this->newItem);
	}

	public function isCraftingPart() : bool{
		return $this->isCraftingPart;
	}

	public function isFinalCraftingPart() : bool{
		return $this->isFinalCraftingPart;
	}
}


