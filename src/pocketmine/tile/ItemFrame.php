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

use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;

class ItemFrame extends Spawnable
{
    public const TAG_ITEM_ROTATION = "ItemRotation";
    public const TAG_ITEM_DROP_CHANCE = "ItemDropChance";
    public const TAG_ITEM = "Item";

    /** @var Item */
    private Item $item;
    /** @var int */
    private int $itemRotation;
    /** @var float */
    private float $itemDropChance;

    protected function readSaveData(CompoundTag $nbt): void
    {
        if (($itemTag = $nbt->getCompoundTag(self::TAG_ITEM)) !== null) {
            $this->item = Item::nbtDeserialize($itemTag);
        } else {
            $this->item = Item::get(Item::AIR, 0, 0);
        }
        $this->itemRotation = $nbt->getByte(self::TAG_ITEM_ROTATION, 0, true);
        $this->itemDropChance = $nbt->getFloat(self::TAG_ITEM_DROP_CHANCE, 1.0, true);
    }

    protected function writeSaveData(CompoundTag $nbt): void
    {
        $nbt->setFloat(self::TAG_ITEM_DROP_CHANCE, $this->itemDropChance);
        $nbt->setByte(self::TAG_ITEM_ROTATION, $this->itemRotation);
        $nbt->setTag(self::TAG_ITEM, $this->item->nbtSerialize(-1));
    }

    public function hasItem(): bool
    {
        return !$this->item->isNull();
    }

    public function getItem(): Item
    {
        return clone $this->item;
    }

    public function setItem(Item $item = null): void
    {
        if ($item !== null and !$item->isNull()) {
            $this->item = clone $item;
        } else {
            $this->item = Item::get(Item::AIR, 0, 0);
        }
        $this->onChanged();
    }

    public function getItemRotation(): int
    {
        return $this->itemRotation;
    }

    public function setItemRotation(int $rotation): void
    {
        $this->itemRotation = $rotation;
        $this->onChanged();
    }

    public function getItemDropChance(): float
    {
        return $this->itemDropChance;
    }

    public function setItemDropChance(float $chance): void
    {
        $this->itemDropChance = $chance;
        $this->onChanged();
    }

    protected function addAdditionalSpawnData(CompoundTag $nbt, bool $isBedrock): void
    {
        $nbt->setFloat(self::TAG_ITEM_DROP_CHANCE, $this->itemDropChance);
        $nbt->setByte(self::TAG_ITEM_ROTATION, $this->itemRotation);
        $nbt->setTag(self::TAG_ITEM, $this->item->nbtSerialize(-1, $isBedrock));
    }
}

