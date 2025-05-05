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
use pocketmine\item\Tool;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\tile\EnderChest as TileEnderChest;
use pocketmine\tile\Tile;

class EnderChest extends Chest{

	protected $id = self::ENDER_CHEST;

	public function getHardness() : float{
		return 22.5;
	}

	public function getBlastResistance() : float{
		return 3000;
	}

	public function getLightLevel() : int{
		return 7;
	}

	public function getName() : string{
		return "Ender Chest";
	}

	public function getToolType() : int{
		return Tool::TYPE_PICKAXE;
	}

	public function getToolHarvestLevel() : int{
		return TieredTool::TIER_WOODEN;
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
        Tile::createTile(Tile::ENDER_CHEST, $this->getLevel(), TileEnderChest::createNBT($this, $face, $item, $player));

		return true;
	}

	public function onBreak(Item $item, Player $player = null) : bool{
		return Block::onBreak($item);
	}

	public function onActivate(Item $item, Player $player = null): bool{
        if($player instanceof Player){

            $t = $this->getLevel()->getTile($this);
            $enderChest = null;
            if($t instanceof TileEnderChest){
                $enderChest = $t;
            }else{
                $enderChest = Tile::createTile(Tile::ENDER_CHEST, $this->getLevel(), TileEnderChest::createNBT($this));
                if(!($enderChest instanceof TileEnderChest)){
                    return true;
                }
            }

            if(!$this->getSide(Vector3::SIDE_UP)->isTransparent()){
                return true;
            }

            $player->getEnderChestInventory()->setHolderPosition($enderChest);
            $player->addWindow($player->getEnderChestInventory());
        }

        return true;
	}

    public function getDropsForCompatibleTool(Item $item) : array{
        return [
            Item::get(Item::OBSIDIAN, 0, 8)
        ];
    }

    public function getFuelTime() : int{
        return 0;
    }
}

