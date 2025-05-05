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

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityInventoryChangeEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\ContainerSetContentPacket;
use pocketmine\network\mcpe\protocol\ContainerSetSlotPacket;
use pocketmine\Player;
use function array_slice;
use function assert;
use function count;
use function max;
use function min;
use function spl_object_id;

abstract class BaseInventory implements Inventory{

	/** @var InventoryType */
	protected $type;
	/** @var int */
	protected $maxStackSize = Inventory::MAX_STACK;
	/** @var int */
	protected $size;
	/** @var string */
	protected $name;
	/** @var string */
	protected $title;
	/** @var Item[] */
	protected $slots = [];
	/** @var Player[] */
	protected $viewers = [];
	protected $holder;
    /** @var InventoryEventProcessor|null */
    protected $eventProcessor;

	/**
	 * @param $holder
	 * @param InventoryType   $type
	 * @param Item[]          $items
	 * @param int             $overrideSize
	 * @param string          $overrideTitle
	 */
	public function __construct($holder, InventoryType $type, array $items = [], $overrideSize = null, $overrideTitle = null){
		$this->holder = $holder;

		$this->type = $type;
		if($overrideSize !== null){
			$this->size = (int) $overrideSize;
		}else{
			$this->size = $this->type->getDefaultSize();
		}

		if($overrideTitle !== null){
			$this->title = $overrideTitle;
		}else{
			$this->title = $this->type->getDefaultTitle();
		}

		$this->name = $this->type->getDefaultTitle();

		$this->setContents($items);
	}

	public function __destruct(){
		$this->holder = null;
		$this->slots = [];
	}

	public function getSize() : int{
		return $this->size;
	}

	public function setSize(int $size){
		$this->size = $size;
	}

	public function getMaxStackSize() : int{
		return $this->maxStackSize;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getTitle() : string{
		return $this->title;
	}

	public function getItem(int $index) : Item{
		assert($index >= 0, "Inventory slot should not be negative");
		return isset($this->slots[$index]) ? clone $this->slots[$index] : Item::get(Item::AIR, 0, 0);
	}

    public function getContents() : array{
        return $this->slots;
    }

	/**
	 * @param Item[] $items
	 * @param bool $send
	 */
	public function setContents(array $items, $send = true){
		if(count($items) > $this->size){
			$items = array_slice($items, 0, $this->size, true);
		}

		for($i = 0; $i < $this->size; ++$i){
			if(!isset($items[$i])){
				if(isset($this->slots[$i])){
					$this->clear($i, $send);
				}
			}else{
				if(!$this->setItem($i, $items[$i], $send)){
					$this->clear($i, $send);
				}
			}
		}
	}

	public function setItem(int $index, Item $item, $send = true) : bool{
		$item = clone $item;
		if($index < 0 or $index >= $this->size){
			return false;
		}elseif($item->getId() === 0 or $item->getCount() <= 0){
			return $this->clear($index, $send);
		}

		$holder = $this->getHolder();
		if($holder instanceof Entity){
			$ev = new EntityInventoryChangeEvent($holder, $this->getItem($index), $item, $index);
			$ev->call();
			if($ev->isCancelled()){
				$this->sendSlot($index, $this->getViewers());
				return false;
			}
			$item = $ev->getNewItem();
		}

        $oldItem = $this->getItem($index);
        if($this->eventProcessor !== null){
            $newItem = $this->eventProcessor->onSlotChange($this, $index, $oldItem, $item);
            if($newItem === null){
                return false;
            }
        }else{
            $newItem = $item;
        }

		$this->slots[$index] = $newItem->isNull() ? null : $newItem;
		$this->onSlotChange($index, $oldItem, $send);

		return true;
	}

	public function contains(Item $item) : bool{
		$count = max(1, $item->getCount());
		$checkDamage = !$item->hasAnyDamageValue();
		$checkTags = $item->hasCompoundTag();
		foreach($this->getContents() as $i){
			if($item->equals($i, $checkDamage, $checkTags)){
				$count -= $i->getCount();
				if($count <= 0){
					return true;
				}
			}
		}

		return false;
	}

	public function slotContains($slot, Item $item, $matchCount = false){
 		if($matchCount){
 			return $this->getItem($slot)->equals($item, true, true, true);
 		}else{
 			return $this->getItem($slot)->equals($item) and $this->getItem($slot)->getCount() >= $item->getCount();
 		}
 	}
 

	public function all(Item $item) : array{
		$slots = [];
		$checkDamage = !$item->hasAnyDamageValue();
		$checkTags = $item->hasCompoundTag();
		foreach($this->getContents() as $index => $i){
			if($item->equals($i, $checkDamage, $checkTags)){
				$slots[$index] = $i;
			}
		}

		return $slots;
	}

	public function remove(Item $item, $send = true){
		$checkDamage = !$item->hasAnyDamageValue();
		$checkTags = $item->hasCompoundTag();

		foreach($this->getContents() as $index => $i){
			if($item->equals($i, $checkDamage, $checkTags)){
				$this->clear($index, $send);
			}
		}
	}

	public function first(Item $item) : int{
		$count = max(1, $item->getCount());
		$checkDamage = !$item->hasAnyDamageValue();
		$checkTags = $item->hasCompoundTag();

		foreach($this->getContents() as $index => $i){
			if($item->equals($i, $checkDamage, $checkTags) and $i->getCount() >= $count){
				return $index;
			}
		}

		return -1;
	}

	public function firstEmpty() : int{
		for($i = 0; $i < $this->size; ++$i){
			if($this->getItem($i)->getId() === Item::AIR){
				return $i;
			}
		}

		return -1;
	}

    public function firstOccupied(): int{
        for($i = 0; $i < $this->size; $i++){
            if(($item = $this->getItem($i))->getId() !== Item::AIR and $item->getCount() > 0){
                return $i;
            }
        }
        return -1;
    }

	public function canAddItem(Item $item) : bool{
		$item = clone $item;
		$checkDamage = !$item->hasAnyDamageValue();
		$checkTags = $item->hasCompoundTag();
		for($i = 0; $i < $this->getSize(); ++$i){
			$slot = $this->getItem($i);
			if($item->equals($slot, $checkDamage, $checkTags)){
				if(($diff = $slot->getMaxStackSize() - $slot->getCount()) > 0){
					$item->setCount($item->getCount() - $diff);
				}
			}elseif($slot->getId() === Item::AIR){
				$item->setCount($item->getCount() - $this->getMaxStackSize());
			}

			if($item->getCount() <= 0){
				return true;
			}
		}

		return false;
	}

	public function addItem(Item ...$slots) : array{
		/** @var Item[] $itemSlots */
		/** @var Item[] $slots */
		$itemSlots = [];
		foreach($slots as $slot){
			if($slot->getId() !== 0 and $slot->getCount() > 0){
				$itemSlots[] = clone $slot;
			}
		}

		$emptySlots = [];

		for($i = 0; $i < $this->getSize(); ++$i){
			$item = $this->getItem($i);
			if($item->getId() === Item::AIR or $item->getCount() <= 0){
				$emptySlots[] = $i;
                continue;
			}

			foreach($itemSlots as $index => $slot){
				if($slot->equals($item) and $item->getCount() < $item->getMaxStackSize()){
					$amount = min($item->getMaxStackSize() - $item->getCount(), $slot->getCount(), $this->getMaxStackSize());
					if($amount > 0){
						$slot->setCount($slot->getCount() - $amount);
						$item->setCount($item->getCount() + $amount);
						$this->setItem($i, $item);
						if($slot->getCount() <= 0){
							unset($itemSlots[$index]);
						}
					}
				}
			}

			if(count($itemSlots) === 0){
				break;
			}
		}

		if(count($itemSlots) > 0 and count($emptySlots) > 0){
			foreach($emptySlots as $slotIndex){
				//This loop only gets the first item, then goes to the next empty slot
				foreach($itemSlots as $index => $slot){
					$amount = min($slot->getMaxStackSize(), $slot->getCount(), $this->getMaxStackSize());
					$slot->setCount($slot->getCount() - $amount);
					$item = clone $slot;
					$item->setCount($amount);
					$this->setItem($slotIndex, $item);
					if($slot->getCount() <= 0){
						unset($itemSlots[$index]);
					}
					break;
				}
			}
		}

		return $itemSlots;
	}

	public function removeItem(Item ...$slots) : array{
		/** @var Item[] $itemSlots */
		/** @var Item[] $slots */
		$itemSlots = [];
		foreach($slots as $slot){
			if($slot->getId() !== 0 and $slot->getCount() > 0){
				$itemSlots[] = clone $slot;
			}
		}

		for($i = 0; $i < $this->getSize(); ++$i){
			$item = $this->getItem($i);
			if($item->getId() === Item::AIR or $item->getCount() <= 0){
				continue;
			}

			foreach($itemSlots as $index => $slot){
				if($slot->equals($item, !$slot->hasAnyDamageValue(), $slot->hasCompoundTag())){
					$amount = min($item->getCount(), $slot->getCount());
					$slot->setCount($slot->getCount() - $amount);
					$item->setCount($item->getCount() - $amount);
					$this->setItem($i, $item);
					if($slot->getCount() <= 0){
						unset($itemSlots[$index]);
					}
				}
			}

			if(count($itemSlots) === 0){
				break;
			}
		}

		return $itemSlots;
	}

	public function clear(int $index, $send = true) : bool{
		if(isset($this->slots[$index])){
			$item = Item::get(Item::AIR, 0, 0);
			$old = $this->slots[$index];
			$holder = $this->getHolder();
			if($holder instanceof Entity){
				$ev = new EntityInventoryChangeEvent($holder, $old, $item, $index);
				$ev->call();
				if($ev->isCancelled()){
					$this->sendSlot($index, $this->getViewers());
					return false;
				}
				$item = $ev->getNewItem();
			}
			if($item->getId() !== Item::AIR){
				$this->slots[$index] = clone $item;
			}else{
				unset($this->slots[$index]);
			}

			$this->onSlotChange($index, $old, $send);
		}

		return true;
	}

	public function clearAll($send = true){
		foreach($this->getContents() as $index => $i){
			$this->clear($index, $send);
		}
	}

	/**
	 * @return Player[]
	 */
	public function getViewers() : array{
		return $this->viewers;
	}

    /**
     * Removes the inventory window from all players currently viewing it.
     *
     * @param bool $force Force removal of permanent windows such as the player's own inventory. Used internally.
     */
    public function removeAllViewers(bool $force = false) : void{
        foreach($this->viewers as $hash => $viewer){
            $viewer->removeWindow($this, $force);
            unset($this->viewers[$hash]);
        }
    }

	public function getHolder(){
		return $this->holder;
	}

	public function setMaxStackSize(int $size){
		$this->maxStackSize = $size;
	}

	public function open(Player $who) : bool{
		$ev = new InventoryOpenEvent($this, $who);
		$ev->call();
		if($ev->isCancelled()){
			return false;
		}
		$this->onOpen($who);

		return true;
	}

	public function close(Player $who){
		$this->onClose($who);
	}

	public function onOpen(Player $who){
		$this->viewers[spl_object_id($who)] = $who;
	}

	public function onClose(Player $who){
		unset($this->viewers[spl_object_id($who)]);
	}

	public function onSlotChange($index, $before, $send){
 		if($send){
 			$this->sendSlot($index, $this->getViewers());
 		}
 	}
 
 	public function processSlotChange(Transaction $transaction): bool{
 		return true;
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

		foreach($target as $player){
			if(($id = $player->getWindowId($this)) === -1 or $player->spawned !== true){
				$this->close($player);
				continue;
			}
			$pk->windowId = $id;
			$pk->targetEid = $player->getId();
			$player->sendDataPacket($pk);
		}
	}

	/**
	 * @param int             $index
	 * @param Player|Player[] $target
	 */
	public function sendSlot($index, $target){
		if($target instanceof Player){
			$target = [$target];
		}

		$pk = new ContainerSetSlotPacket();
		$pk->slot = $index;
		$pk->item = clone $this->getItem($index);

		foreach($target as $player){
			if(($id = $player->getWindowId($this)) === -1){
				$this->close($player);
				continue;
			}
			$pk->windowId = $id;
			$player->sendDataPacket($pk);
		}
	}

    public function slotExists(int $slot) : bool{
        return $slot >= 0 && $slot < $this->getSize();
    }

	public function getType() : InventoryType{
		return $this->type;
	}

    public function getEventProcessor() : ?InventoryEventProcessor{
        return $this->eventProcessor;
    }

    public function setEventProcessor(?InventoryEventProcessor $eventProcessor) : void{
        $this->eventProcessor = $eventProcessor;
    }

}


