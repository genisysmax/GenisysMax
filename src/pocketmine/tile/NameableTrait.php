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
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;

/**
 * This trait implements most methods in the {@link Nameable} interface. It should only be used by Tiles.
 */
trait NameableTrait
{
    /** @var string|null */
    private ?string $customName = null;

    abstract public function getDefaultName(): string;

    public function getName(): string
    {
        return $this->customName ?? $this->getDefaultName();
    }

    public function setName(string $name): void
    {
        if ($name === "") {
            $this->customName = null;
        } else {
            $this->customName = $name;
        }
    }

    public function hasName(): bool
    {
        return $this->customName !== null;
    }

    protected static function createAdditionalNBT(CompoundTag $nbt, Vector3 $pos, ?int $face = null, ?Item $item = null, ?Player $player = null): void
    {
        if ($item !== null and $item->hasCustomName()) {
            $nbt->setString(Nameable::TAG_CUSTOM_NAME, $item->getCustomName());
        }
    }

    protected function addAdditionalSpawnData(CompoundTag $nbt, bool $isBedrock): void
    {
        if ($this->customName !== null) {
            $nbt->setString(Nameable::TAG_CUSTOM_NAME, $this->customName);
        }
    }

    protected function loadName(CompoundTag $tag): void
    {
        if ($tag->hasTag(Nameable::TAG_CUSTOM_NAME, StringTag::class)) {
            $this->customName = $tag->getString(Nameable::TAG_CUSTOM_NAME);
        }
    }

    protected function saveName(CompoundTag $tag): void
    {
        if ($this->customName !== null) {
            $tag->setString(Nameable::TAG_CUSTOM_NAME, $this->customName);
        }
    }
}

