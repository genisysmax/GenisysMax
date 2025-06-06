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
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\tile\Skull as TileSkull;
use pocketmine\tile\Tile;

class Skull extends Flowable{

	protected $id = self::SKULL_BLOCK;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getHardness() : float{
        return 1;
    }

    public function getName() : string{
        return "Mob Head";
    }

    protected function recalculateBoundingBox() : ?AxisAlignedBB{
        //TODO: different bounds depending on attached face (meta)
        return new AxisAlignedBB(
            $this->x + 0.25,
            $this->y,
            $this->z + 0.25,
            $this->x + 0.75,
            $this->y + 0.5,
            $this->z + 0.75
        );
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        if($face === Vector3::SIDE_DOWN){
            return false;
        }

        $this->meta = $face;
        $this->getLevel()->setBlock($blockReplace, $this, true);
        Tile::createTile(Tile::SKULL, $this->getLevel(), TileSkull::createNBT($this, $face, $item, $player));

        return true;
    }

    private function getItem() : Item{
        $tile = $this->level->getTile($this);
        return Item::get(Item::SKULL, $tile instanceof TileSkull ? $tile->getType() : 0);
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        return [$this->getItem()];
    }

    public function isAffectedBySilkTouch() : bool{
        return false;
    }

    public function getPickedItem() : Item{
        return $this->getItem();
    }
}

