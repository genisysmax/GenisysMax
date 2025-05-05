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
use pocketmine\item\TieredTool;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\tile\Furnace as TileFurnace;
use pocketmine\tile\Tile;

class BurningFurnace extends Solid{

    protected $id = self::BURNING_FURNACE;

    protected $itemId = self::FURNACE;

    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }

    public function getName() : string{
        return "Burning Furnace";
    }

    public function getHardness() : float{
        return 3.5;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_PICKAXE;
    }

    public function getToolHarvestLevel() : int{
        return TieredTool::TIER_WOODEN;
    }

    public function getLightLevel() : int{
        return 13;
    }

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool{
        $faces = [
            0 => 4,
            1 => 2,
            2 => 5,
            3 => 3
        ];
        $this->meta = $faces[$player instanceof Player ? $player->getDirection() : 0];
        $this->getLevel()->setBlock($blockReplace, $this, true, true);

        Tile::createTile(Tile::FURNACE, $this->getLevel(), TileFurnace::createNBT($this, $face, $item, $player));

        return true;
	}

	public function onActivate(Item $item, Player $player = null): bool{
        if($player instanceof Player){
            $furnace = $this->getLevel()->getTile($this);
            if(!($furnace instanceof TileFurnace)){
                $furnace = Tile::createTile(Tile::FURNACE, $this->getLevel(), TileFurnace::createNBT($this));
                if(!($furnace instanceof TileFurnace)){
                    return true;
                }
            }

            if(!$furnace->canOpenWith($item->getCustomName())){
                return true;
            }

            $player->addWindow($furnace->getInventory());
        }

        return true;
	}

    public function getVariantBitmask() : int{
        return 0;
    }
}

