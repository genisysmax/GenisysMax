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

namespace pocketmine\tile;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;

trait ContainerTrait{

    /** @var string|null */
    private ?string $lock = null;

    /**
     * @return Inventory
     */
    abstract public function getRealInventory();

    protected function loadItems(CompoundTag $tag) : void{
        if($tag->hasTag(Container::TAG_ITEMS, ListTag::class)){
            $inventoryTag = $tag->getListTag(Container::TAG_ITEMS);

            $inventory = $this->getRealInventory();
            /** @var CompoundTag $itemNBT */
            foreach($inventoryTag as $itemNBT){
                $inventory->setItem($itemNBT->getByte("Slot"), Item::nbtDeserialize($itemNBT));
            }
        }

        if($tag->hasTag(Container::TAG_LOCK, StringTag::class)){
            $this->lock = $tag->getString(Container::TAG_LOCK);
        }
    }

    protected function saveItems(CompoundTag $tag) : void{
        $items = [];
        foreach($this->getRealInventory()->getContents() as $slot => $item){
            $items[] = $item->nbtSerialize($slot);
        }

        $tag->setTag(Container::TAG_ITEMS, new ListTag($items, NBT::TAG_Compound));

        if($this->lock !== null){
            $tag->setString(Container::TAG_LOCK, $this->lock);
        }
    }

    /**
     * @see Container::canOpenWith()
     */
    public function canOpenWith(string $key) : bool{
        return $this->lock === null or $this->lock === $key;
    }
}

