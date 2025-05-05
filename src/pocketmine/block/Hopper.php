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



namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\item\TieredTool;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\tile\Hopper as TileHopper;
use pocketmine\tile\Tile;

class Hopper extends Transparent{

    protected $id = self::HOPPER_BLOCK;
    protected $itemId = Item::HOPPER;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getHardness() : float{
        return 3;
    }

    public function getBlastResistance() : float{
        return 24;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_PICKAXE;
    }

    public function getToolHarvestLevel() : int{
        return TieredTool::TIER_WOODEN;
    }

    public function getName() : string{
        return "Hopper";
    }
	/**
	 * @param Item $item
	 * @param Player|null $player
	 *
	 * @return bool
	 */
	public function onActivate(Item $item, Player $player = null): bool
    {
        if($player instanceof Player){
            $hopper = $this->getLevel()->getTile($this);
            if($hopper instanceof TileHopper){

                if(!$hopper->canOpenWith($item->getCustomName())){
                    return true;
                }

                $player->addWindow($hopper->getInventory());
            }
        }
		return true;
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool{
        static $faces = [
            0 => Vector3::SIDE_DOWN,
            1 => Vector3::SIDE_DOWN, // Not used
            2 => Vector3::SIDE_SOUTH,
            3 => Vector3::SIDE_NORTH,
            4 => Vector3::SIDE_EAST,
            5 => Vector3::SIDE_WEST
        ];

        $this->meta = $faces[$face];
        $this->getLevel()->setBlock($this, $this, true, true);

        Tile::createTile(Tile::HOPPER, $this->getLevel(), TileHopper::createNBT($this, $face, $item, $player));

        return true;
	}
}


