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

use pocketmine\entity\Living;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ContainerSetContentPacket;
use pocketmine\network\mcpe\protocol\ContainerSetSlotPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\Player;
use function array_merge;

class ArmorInventory extends BaseInventory{
	public const int SLOT_HEAD = 0;
	public const int SLOT_CHEST = 1;
	public const int SLOT_LEGS = 2;
	public const int SLOT_FEET = 3;

	/** @var Living */
	protected $holder;

	public function __construct($holder, $contents = null){
		parent::__construct($holder, InventoryType::get(InventoryType::PLAYER), [], 4);

        if ($contents !== null) {
            foreach ($contents as $item) {
                /** @var CompoundTag $item */
                $slot = $item->getByte("Slot");
                $this->setItem($slot, Item::nbtDeserialize($item), false);
            }
        }
	}

    /** @return Item[] */
    public function getContents() : array{
        $armor = [];
        for($i = 0; $i < 4; ++$i){
            $armor[$i] = $this->getItem($i);
        }
        return $armor;
    }

	public function getHolder() : Living{
		return $this->holder;
	}

	public function getHelmet() : Item{
		return $this->getItem(self::SLOT_HEAD);
	}

	public function getChestplate() : Item{
		return $this->getItem(self::SLOT_CHEST);
	}

	public function getLeggings() : Item{
		return $this->getItem(self::SLOT_LEGS);
	}

	public function getBoots() : Item{
		return $this->getItem(self::SLOT_FEET);
	}

	public function setHelmet(Item $helmet) : bool{
		return $this->setItem(self::SLOT_HEAD, $helmet);
	}

	public function setChestplate(Item $chestplate) : bool{
		return $this->setItem(self::SLOT_CHEST, $chestplate);
	}

	public function setLeggings(Item $leggings) : bool{
		return $this->setItem(self::SLOT_LEGS, $leggings);
	}

	public function setBoots(Item $boots) : bool{
		return $this->setItem(self::SLOT_FEET, $boots);
	}

	public function sendSlot($index, $target) : void{
		if($target instanceof Player){
			$target = [$target];
		}

		$pk = new MobArmorEquipmentPacket();
		$pk->entityRuntimeId = $this->holder->getId();
		$pk->head = $this->getHelmet();
		$pk->chest = $this->getChestplate();
		$pk->legs = $this->getLeggings();
		$pk->feet = $this->getBoots();
		$pk->encode();

		foreach($target as $player){
			if($player === $this->holder){
				/** @var Player $player */

                $pk2 = new ContainerSetSlotPacket();
                $pk2->slot = $index;
                $pk2->item = $this->getItem($index);
                $pk2->windowId = $player->getWindowId($this);
                $player->sendDataPacket($pk2);
			}else{
				$player->sendDataPacket($pk);
			}
		}
	}

	public function sendContents($target) : void{
		if($target instanceof Player){
			$target = [$target];
		}

		$pk = new MobArmorEquipmentPacket();
		$pk->entityRuntimeId = $this->holder->getId();
		$pk->head = $this->getHelmet();
		$pk->chest = $this->getChestplate();
		$pk->legs = $this->getLeggings();
		$pk->feet = $this->getBoots();
		$pk->encode();

		foreach($target as $player){
			if($player === $this->holder){
                $pk2 = new ContainerSetContentPacket();
                $pk2->windowId = $player->getWindowId($this);
                $pk2->targetEid = $player->getId();
                $pk2->slots = $this->getContents();
                $player->sendDataPacket($pk2);
			}else{
				$player->sendDataPacket($pk);
			}
		}
	}

	/**
	 * @return Player[]
	 */
	public function getViewers() : array{
		return array_merge(parent::getViewers(), $this->holder->getViewers());
	}
}


