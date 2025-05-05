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

use BadMethodCallException;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityOffHandChangeEvent;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\ContainerSetContentPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\Player;

class PlayerOffHandInventory extends BaseInventory{

	/** @var Player|Human */
	protected $holder;

	public function __construct(Human $holder, $item = null){
		parent::__construct($holder, InventoryType::get(InventoryType::PLAYER), [], 1);

        if($item !== null) {
            $this->setItem(0, Item::nbtDeserialize($item), false);
        }
	}

    public function getHolder() : Human{
        return $this->holder;
    }

    public function setSize(int $size){
        throw new BadMethodCallException("OffHand can only carry one item at a time");
    }

	public function getItemInOffhand() : Item{
		return $this->getItem(0);
	}

	public function setItemInOffhand(Item $item) : void{
		$this->setItem(0, $item);
	}

    public function setItem(int $index, Item $item, $send = true) : bool
    {
        if ($index < 0 or $index >= $this->size) {
            return false;
        } elseif ($item->getId() === 0 or $item->getCount() <= 0) {
            return $this->clear($index, $send);
        }

        $ev = new EntityOffHandChangeEvent($this->holder, $this->getItemInOffhand(), $item);
        $ev->call();
        if ($ev->isCancelled()) {
            $this->sendContents($this->getViewers());
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
            $ev = new EntityOffHandChangeEvent($this->holder, $old, $item);
            $ev->call();
            if ($ev->isCancelled()) {
                $this->sendContents($this->getViewers());
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

	public function onSlotChange($index, $before, $send){
		if($send){
			if($this->holder instanceof Player){
				$this->sendSlot($index, $this->holder);
			}

			$this->sendSlot($index, $this->getViewers());
			$this->sendSlot($index, $this->holder->getViewers());
		}
	}

	public function sendContents($target){
		if($target instanceof Player){
			$target = [$target];
		}

		$pk = new MobEquipmentPacket();
		$pk->entityRuntimeId = $this->holder->getId();
		$pk->item = $this->getItemInOffhand();
		$pk->inventorySlot = 0;
		$pk->hotbarSlot = $this->holder->getInventory()->getHeldItemSlot();
		$pk->windowId = ContainerIds::OFFHAND;

		$pk->encode();

		foreach($target as $player){
			if($player === $this->holder){
				$packet = new ContainerSetContentPacket();
				$packet->targetEid = $player->getId();
				$packet->windowId = ContainerIds::OFFHAND;
				$packet->slots = [$this->getItemInOffhand()];
				$player->sendDataPacket($packet);
			}else{
				$player->sendDataPacket($pk);
			}
		}
	}

	public function sendSlot($index, $target){
		if($target instanceof Player){
			$target = [$target];
		}

		$this->sendContents(array_merge($target, $this->holder->getViewers()));
	}

    /**
     * @return Player[]
     */
    public function getViewers() : array{
        return array_merge(parent::getViewers(), $this->holder->getViewers());
    }
}

