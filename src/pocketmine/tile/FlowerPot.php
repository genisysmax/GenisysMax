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

class FlowerPot extends Spawnable
{
    public const TAG_ITEM = "item";
    public const TAG_ITEM_DATA = "mData";

    /** @var Item */
    private Item $item;

    protected function readSaveData(CompoundTag $nbt): void
    {
        $this->item = Item::get($nbt->getShort(self::TAG_ITEM, 0), $nbt->getInt(self::TAG_ITEM_DATA, 0), 1);
    }

    protected function writeSaveData(CompoundTag $nbt): void
    {
        $nbt->setShort(self::TAG_ITEM, $this->item->getId());
        $nbt->setInt(self::TAG_ITEM_DATA, $this->item->getDamage());
    }

    public function canAddItem(Item $item): bool
    {
        if (!$this->isEmpty()) {
            return false;
        }
        switch ($item->getId()) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case Item::TALL_GRASS:
                if ($item->getDamage() === 1) {
                    return false;
                }
            case Item::SAPLING:
            case Item::DEAD_BUSH:
            case Item::DANDELION:
            case Item::RED_FLOWER:
            case Item::BROWN_MUSHROOM:
            case Item::RED_MUSHROOM:
            case Item::CACTUS:
                return true;
            default:
                return false;
        }
    }

    public function getItem(): Item
    {
        return clone $this->item;
    }

    public function setItem(Item $item): void
    {
        $this->item = clone $item;
        $this->onChanged();
    }

    public function removeItem(): void
    {
        $this->setItem(Item::get(Item::AIR, 0, 0));
    }

    public function isEmpty(): bool
    {
        return $this->getItem()->isNull();
    }

    protected function addAdditionalSpawnData(CompoundTag $nbt, bool $isBedrock): void
    {
        $nbt->setShort(self::TAG_ITEM, $this->item->getId());
        $nbt->setInt(self::TAG_ITEM_DATA, $this->item->getDamage());
    }
}

