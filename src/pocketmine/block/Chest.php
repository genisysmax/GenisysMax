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
use pocketmine\tile\Chest as TileChest;
use pocketmine\tile\Tile;

class Chest extends Transparent{

	protected $id = self::CHEST;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getHardness() : float{
        return 2.5;
    }

    public function getName() : string{
        return "Chest";
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_AXE;
    }

	protected function recalculateBoundingBox() : ?AxisAlignedBB{
		//these are slightly bigger than in PC
		return new AxisAlignedBB(
			$this->x + 0.025,
			$this->y,
			$this->z + 0.025,
			$this->x + 0.975,
			$this->y + 0.95,
			$this->z + 0.975
		);
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool{
        $faces = [
            0 => 4,
            1 => 2,
            2 => 5,
            3 => 3
        ];

        $chest = null;
        $this->meta = $faces[$player instanceof Player ? $player->getDirection() : 0];

        for($side = 2; $side <= 5; ++$side) {
            if (($this->meta === 4 or $this->meta === 5) and ($side === 4 or $side === 5)) {
                continue;
            } elseif (($this->meta === 3 or $this->meta === 2) and ($side === 2 or $side === 3)) {
                continue;
            }
            $c = $this->getSide($side);
            if ($c->getId() === $this->id and $c->getDamage() === $this->meta) {

                $tile = $this->getLevel()->getTile($c);
                if ($tile instanceof TileChest and !$tile->isPaired()) {
                    $chest = $tile;
                    break;
                }
            }
        }

        $this->getLevel()->setBlock($blockReplace, $this, true, true);
        $tile = Tile::createTile(Tile::CHEST, $this->getLevel(), TileChest::createNBT($this, $face, $item, $player));

        if($chest instanceof TileChest and $tile instanceof TileChest){
            $chest->pairWith($tile);
            $tile->pairWith($chest);
        }

		return true;
	}

	public function onBreak(Item $item, Player $player = null): bool{
		$t = $this->getLevel()->getTile($this);
		if($t instanceof TileChest){
			$t->unpair();
		}
		$this->getLevel()->setBlock($this, Block::get(Block::AIR), true, true);
		return true;
	}

    public function onActivate(Item $item, Player $player = null) : bool{
        if($player instanceof Player){

            $t = $this->getLevel()->getTile($this);
            $chest = null;
            if($t instanceof TileChest){
                $chest = $t;
            }else{
                $chest = Tile::createTile(Tile::CHEST, $this->getLevel(), TileChest::createNBT($this));
                if(!($chest instanceof TileChest)){
                    return true;
                }
            }

            if(
                !$this->getSide(Vector3::SIDE_UP)->isTransparent() or
                (($pair = $chest->getPair()) !== null and !$pair->getBlock()->getSide(Vector3::SIDE_UP)->isTransparent()) or
                !$chest->canOpenWith($item->getCustomName())
            ){
                return true;
            }

            $player->addWindow($chest->getInventory());
        }

		return true;
	}

    public function getVariantBitmask() : int{
        return 0;
    }

    public function getFuelTime() : int{
        return 300;
    }
}

