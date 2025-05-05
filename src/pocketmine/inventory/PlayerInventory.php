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

use pocketmine\BedrockPlayer;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityInventoryChangeEvent;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\bedrock\adapter\v390\Protocol390Adapter;
use pocketmine\network\bedrock\protocol\CreativeContentPacket;
use pocketmine\network\bedrock\protocol\InventoryContentPacket;
use pocketmine\network\bedrock\protocol\types\inventory\CreativeItem;
use pocketmine\network\bedrock\protocol\types\inventory\ItemInstance;
use pocketmine\network\mcpe\protocol\ContainerSetContentPacket;
use pocketmine\network\mcpe\protocol\ContainerSetSlotPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\Player;
use function in_array;
use function is_array;
use function range;

class PlayerInventory extends BaseInventory{

	public const int HOTBAR_SIZE = 9;

	protected int $itemInHandSlot = 0;

    /** @var Human */
    protected $holder;

	public function __construct(Human $player, $contents = null)
    {
        parent::__construct($player, InventoryType::get(InventoryType::PLAYER));

        if ($contents !== null) {
            foreach ($contents as $item) {
                /** @var CompoundTag $item */
                $slot = $item->getByte("Slot");
                $this->setItem($slot, Item::nbtDeserialize($item), false);
            }
        }
    }

	public function setSize(int $size){
		//Do not change
	}

	/**
	 * Returns same index.
	 * 
	 * @deprecated
	 * 
	 * @param int $index
	 *
	 * @return int
	 */
	public function getHotbarSlotIndex($index){
		return $index;
	}

	/**
	 * Links to PlayerInventory::getItem()
	 * 
	 * @deprecated
	 * 
	 * @param int $hotbarSlotIndex
	 *
	 * @return Item
	 */
	public function getHotbarSlotItem(int $hotbarSlotIndex) : Item{
		return $this->getItem($hotbarSlotIndex);
	}

	/**
	 * Returns the slot number the holder is currently holding.
	 * @return int
	 */
	public function getHeldItemSlot(){
		return $this->itemInHandSlot;
	}

	/**
	 * Links to PlayerInventory::getHeldItemSlot
	 * 
	 * @deprecated
	 * 
	 * @return int
	 */
	public function getHeldItemIndex(){
		return $this->itemInHandSlot;
	}

	/**
	 * @param int  $hotbarSlotIndex
	 * @param bool $sendToHolder
	 *
	 * Sets which hotbar slot the player is currently holding.
	 * Allows slot remapping as specified by a MobEquipmentPacket. DO NOT CHANGE SLOT MAPPING IN PLUGINS!
	 * This new implementation is fully compatible with older APIs.
	 */
	public function setHeldItemSlot($hotbarSlotIndex, $sendToHolder = true){
		if(0 <= $hotbarSlotIndex and $hotbarSlotIndex < self::HOTBAR_SIZE){
			$this->itemInHandSlot = $hotbarSlotIndex;
			$item = $this->getItem($hotbarSlotIndex);
			$this->sendHeldItem($this->getHolder()->getViewers());
			if($sendToHolder){
				$this->sendHeldItem($this->getHolder());
			}
		}
	}

	/**
	 * Returns the currently-held item.
	 *
	 * @return Item
	 */
	public function getItemInHand(){
		return $this->getItem($this->itemInHandSlot);
	}

	/**
	 * Sets the item in the currently-held slot to the specified item.
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function setItemInHand(Item $item, $send = true){
		return $this->setItem($this->getHeldItemSlot(), $item, $send);
	}

	/**
	 * Sends the currently-held item to specified targets.
	 * @param Player|Player[] $target
	 */
	public function sendHeldItem($target){
		$item = $this->getItemInHand();

		$pk = new MobEquipmentPacket();
		$pk->entityRuntimeId = $this->getHolder()->getId();
		$pk->item = $item;
		$pk->inventorySlot = $pk->hotbarSlot = $this->itemInHandSlot;
		$pk->windowId = ContainerIds::INVENTORY;
		$pk->encode();

		if(!is_array($target)){
			$target->sendDataPacket($pk);
			if($target === $this->getHolder()){
				$this->sendSlot($this->itemInHandSlot, $target);
			}
		}else{
			$this->getHolder()->getLevel()->getServer()->broadcastPacket($target, $pk);
			if(in_array($this->getHolder(), $target, true)){
				$this->sendSlot($this->itemInHandSlot, $this->getHolder());
			}
		}
	}

	/**
	 * @param int  $index
	 * @param Item $before
	 * @param bool $send
	 */
	public function onSlotChange($index, $before, $send){
		if($send){
			$holder = $this->getHolder();
			if(!($holder instanceof Player) or !$holder->spawned){
				return;
			}
			parent::onSlotChange($index, $before, $send);
		}
		if($index === $this->itemInHandSlot){
			$this->sendHeldItem($this->getHolder()->getViewers());
			if($send){
				$this->sendHeldItem($this->holder);
			}
		}
	}

	public function setItem(int $index, Item $item, $send = true) : bool
    {
        if ($index < 0 or $index >= $this->size) {
            return false;
        } elseif ($item->getId() === 0 or $item->getCount() <= 0) {
            return $this->clear($index, $send);
        }

        $ev = new EntityInventoryChangeEvent($this->getHolder(), $this->getItem($index), $item, $index);
        $ev->call();
        if ($ev->isCancelled()) {
            $this->sendSlot($index, $this->getViewers());
            return false;
        }

        $item = $ev->getNewItem();

        $old = $this->getItem($index);
        $this->slots[$index] = clone $item;
        $this->onSlotChange($index, $old, $send);
        return true;
    }

	public function clear(int $index, $send = true) : bool
    {
        if (isset($this->slots[$index])) {
            $item = Item::get(Item::AIR, 0, 0);
            $old = $this->slots[$index];
            $ev = new EntityInventoryChangeEvent($this->getHolder(), $old, $item, $index);
            $ev->call();
            if ($ev->isCancelled()) {
                $this->sendSlot($index, $this->getViewers());
                return false;
            }
            $item = $ev->getNewItem();
            if ($item->getId() !== Item::AIR) {
                $this->slots[$index] = clone $item;
            } else {
                unset($this->slots[$index]);
            }

            $this->onSlotChange($index, $old, $send);
        }

        return true;
    }

	public function clearAll($send = true) : void{
		$limit = $this->getSize();
		for($index = 0; $index < $limit; ++$index){
			$this->clear($index, $send);
		}
		$this->sendContents($this->getViewers());
	}

	/**
	 * @param Player|Player[] $target
	 */
	public function sendContents($target){
		if($target instanceof Player){
			$target = [$target];
		}

		$pk = new ContainerSetContentPacket();
		$pk->slots = [];

		for($i = 0; $i < $this->getSize(); ++$i){
			$pk->slots[$i] = $this->getItem($i);
		}

		//Because PE is stupid and shows 9 less slots than you send it, give it 9 dummy slots so it shows all the REAL slots.
		for($i = $this->getSize(); $i < $this->getSize() + self::HOTBAR_SIZE; ++$i){
			$pk->slots[$i] = Item::get(Item::AIR, 0, 0);
		}

		$pk->hotbar = range(self::HOTBAR_SIZE, self::HOTBAR_SIZE * 2, 1);

		foreach($target as $player){
			if(($id = $player->getWindowId($this)) === -1 or $player->loginProcessed !== true){
				$this->close($player);
				continue;
			}
			$pk->windowId = $id;
			$pk->targetEid = $player->getId(); //TODO: check if this is correct
			$player->sendDataPacket(clone $pk);
			$this->sendHeldItem($player);
		}
	}

	public function sendCreativeContents(){
		$player = $this->getHolder();

		if($player instanceof BedrockPlayer){
            if($player->getProtocolVersion() <= Protocol390Adapter::PROTOCOL_VERSION){
                $pk = new InventoryContentPacket();
                $pk->windowId = ContainerIds::CREATIVE;

                if(!$player->isSpectator()){ //fill it for all gamemodes except spectator
                    foreach(Item::getCreativeItems($player->getProtocolVersion(), true) as $i => $item){
                        $pk->items[$i] = ItemInstance::legacy(clone $item);
                    }
                }
            }else{
                $pk = new CreativeContentPacket();
                if(!$player->isSpectator()){
                    foreach(Item::getCreativeItems($player->getProtocolVersion(), true) as $i => $item){
                        $pk->items[$i] = new CreativeItem($i, $item);
                    }
                }
            }
        }else{
			$pk = new ContainerSetContentPacket();
			$pk->windowId = ContainerIds::CREATIVE;
			if($player->getGamemode() === Player::CREATIVE){
				foreach(Item::getCreativeItems($player->getProtocolVersion(), false) as $i => $item){
					$pk->slots[$i] = clone $item;
				}
			}
			$pk->targetEid = $this->getHolder()->getId();
        }
        $player->sendDataPacket($pk);
    }

	/**
	 * @param int             $index
	 * @param Player|Player[] $target
	 */
	public function sendSlot($index, $target)
    {
        if ($target instanceof Player) {
            $target = [$target];
        }

        $pk = new ContainerSetSlotPacket();
        $pk->slot = $index;
        $pk->item = clone $this->getItem($index);

        foreach ($target as $player) {
            if ($player === $this->getHolder()) {
                /** @var Player $player */
                $pk->windowId = 0;
                $player->sendDataPacket(clone $pk);
            } else {
                if (($id = $player->getWindowId($this)) === -1) {
                    $this->close($player);
                    continue;
                }
                $pk->windowId = $id;
                $player->sendDataPacket(clone $pk);
            }
        }
    }

	public function getHolder():Human{
		return $this->holder;
	}
}


