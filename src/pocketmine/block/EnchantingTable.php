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

use pocketmine\inventory\EnchantInventory;
use pocketmine\item\Item;
use pocketmine\item\TieredTool;
use pocketmine\item\Tool;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\tile\EnchantTable as TileEnchantTable;
use pocketmine\tile\Tile;

class EnchantingTable extends Transparent{

	protected $id = self::ENCHANTING_TABLE;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool{
        $this->getLevel()->setBlock($blockReplace, $this, true, true);

        Tile::createTile(Tile::ENCHANT_TABLE, $this->getLevel(), TileEnchantTable::createNBT($this, $face, $item, $player));

		return true;
	}

    public function getHardness() : float{
        return 5;
    }

	public function getBlastResistance() : float{
		return 6000;
	}

    public function getName() : string{
        return "Enchanting Table";
    }

	public function getToolType(): int{
		return Tool::TYPE_PICKAXE;
	}

	public function getToolHarvestLevel() : int{
		return TieredTool::TIER_WOODEN;
	}

	public function onActivate(Item $item, Player $player = null): bool{
		if($player instanceof Player){
			$player->addWindow(new EnchantInventory($this));
			$player->craftingType = Player::CRAFTING_ENCHANT;
		}

		return true;
	}
}

