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

namespace pocketmine\block;

use pocketmine\block\utils\ColorBlockMetaHelper;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\tile\Container;
use pocketmine\tile\ShulkerBox as TileShulkerBox;
use pocketmine\tile\Tile;

class ShulkerBox extends Transparent
{
    protected $id = self::SHULKER_BOX;

    public function __construct(int $meta = 0)
    {
        $this->meta = $meta;
    }

    public function getHardness(): float
    {
        return 6;
    }

    public function getName(): string
    {
        return ColorBlockMetaHelper::getColorFromMeta($this->getVariant()) . " Shulker Box";
    }

    public function getToolType(): int
    {
        return BlockToolType::TYPE_PICKAXE;
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool
    {
        if (parent::place($item, $blockReplace, $blockClicked, $face, $clickVector, $player)) {
            $nbt = TileShulkerBox::createNBT($this, $face, $item, $player);
            $tag = $item->getNamedTag();
            if ($tag->hasTag(Container::TAG_ITEMS)) {
                $nbt->setTag(Container::TAG_ITEMS, $tag->getListTag(Container::TAG_ITEMS));
            }
            Tile::createTile(Tile::SHULKER_BOX, $this->getLevel(), $nbt);
            return true;
        }
        return false;
    }

    public function onActivate(Item $item, Player $player = null): bool
    {
        if ($player instanceof Player) {
            $tile = $this->getLevel()->getTile($this);
            if ($tile instanceof TileShulkerBox) {
                $player->addWindow($tile->getInventory());
            }
        }

        return true;
    }

    public function isAffectedBySilkTouch(): bool
    {
        return false;
    }

    public function getDropsForCompatibleTool(Item $item): array
    {
        $t = $this->getLevel()->getTile($this);
        if ($t instanceof TileShulkerBox) {
            $item = Item::get(Item::SHULKER_BOX, $this->getDamage(), 1);

            $blockData = new CompoundTag();
            $t->writeBlockData($blockData);

            $item->setCustomBlockData($blockData);

            return [$item];
        }

        return [];
    }
}

