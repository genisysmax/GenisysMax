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

use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;
use pocketmine\tile\BrewingStand as TileBrewingStand;
use pocketmine\tile\Tile;

class BrewingStand extends Transparent
{

    protected $id = self::BREWING_STAND_BLOCK;
    protected $itemId = Item::BREWING_STAND;

    public function __construct(int $meta = 0)
    {
        $this->meta = $meta;
    }

    public function getName(): string
    {
        return "Brewing Stand";
    }

    public function getHardness(): float
    {
        return 0.5;
    }

    public function getBlastResistance(): float
    {
        return 2.5;
    }

    public function getLightLevel(): int
    {
        return 1;
    }

    public function getToolType(): int
    {
        return Tool::TYPE_PICKAXE;
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool
    {
        if ($blockReplace->getSide(Vector3::SIDE_DOWN)->isTransparent() === false) {
            $this->getLevel()->setBlock($blockReplace, $this, true, true);

            $nbt = CompoundTag::create()
                ->setTag("Items", new ListTag([], NBT::TAG_Compound))
                ->setString("id", Tile::BREWING_STAND)
                ->setInt("x", $this->x)
                ->setInt("y", $this->y)
                ->setInt("z", $this->z);

            if ($item->hasCustomName()) {
                $nbt->setString("CustomName", $item->getCustomName());
            }

            if ($item->hasCustomBlockData()) {
                foreach ($item->getCustomBlockData() as $k => $tag) {
                    $nbt->setTag($k, $tag);
                }
            }

            Tile::createTile(Tile::BREWING_STAND, $this->getLevel(), $nbt);
            return true;
        }
        return false;
    }

    public function onActivate(Item $item, Player $player = null): bool
    {
        if ($player instanceof Player) {
            $t = $this->getLevel()->getTile($this);
            if ($t instanceof TileBrewingStand) {
                $brewingStand = $t;
            } else {
                $nbt = CompoundTag::create()
                    ->setTag("Items", new ListTag([], NBT::TAG_Compound))
                    ->setString("id", Tile::BREWING_STAND)
                    ->setInt("x", $this->x)
                    ->setInt("y", $this->y)
                    ->setInt("z", $this->z);
                $brewingStand = Tile::createTile(Tile::BREWING_STAND, $this->getLevel(), $nbt);
            }
            $player->addWindow($brewingStand->getInventory());
        }
        return true;
    }

    public function getDropsForCompatibleTool(Item $item): array
    {
        return [
            Item::get(Item::BREWING_STAND)
        ];
    }

    public function isAffectedBySilkTouch(): bool
    {
        return false;
    }
}

