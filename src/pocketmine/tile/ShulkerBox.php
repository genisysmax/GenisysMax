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

use InvalidArgumentException;
use pocketmine\block\Block;
use pocketmine\inventory\InventoryHolder;
use pocketmine\inventory\ShulkerBoxInventory;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;

class ShulkerBox extends Spawnable implements InventoryHolder, Container, Nameable
{
    use NameableTrait {
        addAdditionalSpawnData as addNameSpawnData;
    }
    use ContainerTrait;

    public const TAG_FACING = "facing";
    public const TAG_UNDYED = "isUndyed";

    /** @var int */
    protected int $facing = Vector3::SIDE_UP;
    /** @var bool */
    protected bool $isUndyed = true;

    /** @var ShulkerBoxInventory */
    protected ?ShulkerBoxInventory $inventory = null;

    /**
     * @param int $facing
     */
    public function setFacing(int $facing): void
    {
        if ($facing < 0 or $facing > 5) {
            throw new InvalidArgumentException("Invalid shulkerbox facing: $facing");
        }

        $this->facing = $facing;
        $this->onChanged();
    }

    /**
     * @return int
     */
    public function getFacing(): int
    {
        return $this->facing;
    }

    /**
     * @return string
     */
    public function getDefaultName(): string
    {
        return "Shulker Box";
    }

    /**
     * @return ShulkerBoxInventory
     */
    public function getInventory()
    {
        return $this->inventory;
    }

    /**
     * @return ShulkerBoxInventory
     */
    public function getRealInventory()
    {
        return $this->inventory;
    }

    protected function readSaveData(CompoundTag $nbt): void
    {
        $this->facing = $nbt->getByte(self::TAG_FACING, Vector3::SIDE_DOWN);
        $this->isUndyed = $nbt->getByte(self::TAG_UNDYED, 1) == 1;

        $this->inventory = new ShulkerBoxInventory($this);

        $this->loadName($nbt);
        $this->loadItems($nbt);
    }

    protected function writeSaveData(CompoundTag $nbt): void
    {
        $nbt->setByte(self::TAG_FACING, $this->facing);
        $nbt->setByte(self::TAG_FACING, $this->isUndyed ? 1 : 0);

        $this->saveName($nbt);
        $this->saveItems($nbt);
    }

    public function writeBlockData(CompoundTag $nbt): void
    {
        $this->saveName($nbt);
        $this->saveItems($nbt);
    }

    protected function addAdditionalSpawnData(CompoundTag $nbt, bool $isBedrock): void
    {
        $nbt->setByte(self::TAG_FACING, $this->facing);
        $nbt->setByte(self::TAG_UNDYED, $this->isUndyed ? 1 : 0);

        $this->addNameSpawnData($nbt, $isBedrock);
    }

    /**
     * @param CompoundTag $nbt
     * @param Vector3 $pos
     * @param int|null $face
     * @param Item|null $item
     * @param Player|null $player
     * @return void
     */
    protected static function createAdditionalNBT(CompoundTag $nbt, Vector3 $pos, ?int $face = null, ?Item $item = null, ?Player $player = null): void
    {
        parent::createAdditionalNBT($nbt, $pos, $face, $item, $player);

        $nbt->setByte(self::TAG_FACING, $face ?? Vector3::SIDE_DOWN);
        if ($item !== null) {
            $nbt->setByte(self::TAG_UNDYED, $item->getId() == Block::UNDYED_SHULKER_BOX ? 1 : 0);
        }
    }

    public function close(): void
    {
        if (!$this->closed) {
            foreach ($this->getInventory()->getViewers() as $player) {
                $player->removeWindow($this->getInventory());
            }
            $this->inventory = null;

            parent::close();
        }
    }
}

