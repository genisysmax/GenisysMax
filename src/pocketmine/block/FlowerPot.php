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
use pocketmine\tile\FlowerPot as TileFlowerPot;
use pocketmine\tile\Tile;

class FlowerPot extends Flowable{

    public const STATE_EMPTY = 0;
    public const STATE_FULL = 1;

    protected $id = self::FLOWER_POT_BLOCK;
    protected $itemId = Item::FLOWER_POT;

    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }

    public function getName() : string{
        return "Flower Pot";
    }

    protected function recalculateBoundingBox() : ?AxisAlignedBB{
        return new AxisAlignedBB(
            $this->x + 0.3125,
            $this->y,
            $this->z + 0.3125,
            $this->x + 0.6875,
            $this->y + 0.375,
            $this->z + 0.6875
        );
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        if(($block = $this->getSide(Vector3::SIDE_DOWN))->isTransparent()){
            if(!($block instanceof Slab and ($block->getDamage() & 0x08) > 0)){
                return false;
            }
        }

        $this->getLevel()->setBlock($blockReplace, $this, true, true);
        Tile::createTile(Tile::FLOWER_POT, $this->getLevel(), TileFlowerPot::createNBT($this, $face, $item, $player));
        return true;
    }

    public function onNearbyBlockChange() : void
    {
        if (($block = $this->getSide(Vector3::SIDE_DOWN))->isTransparent()) {
            if (!($block instanceof Slab and ($block->getDamage() & 0x08) > 0)) {
                $this->getLevel()->useBreakOn($this);
            }
        }
    }

    public function onActivate(Item $item, Player $player = null) : bool{
        $pot = $this->getLevel()->getTile($this);
        if(!($pot instanceof TileFlowerPot)){
            return false;
        }
        if(!$pot->canAddItem($item)){
            return true;
        }

        $this->setDamage(self::STATE_FULL); //specific damage value is unnecessary, it just needs to be non-zero to show an item.
        $this->getLevel()->setBlock($this, $this, true, false);
        $pot->setItem($item->pop());

        return true;
    }

    public function getVariantBitmask() : int{
        return 0;
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        $items = parent::getDropsForCompatibleTool($item);

        $tile = $this->getLevel()->getTile($this);
        if($tile instanceof TileFlowerPot){
            $item = $tile->getItem();
            if($item->getId() !== Item::AIR){
                $items[] = $item;
            }
        }

        return $items;
    }

    public function isAffectedBySilkTouch() : bool{
        return false;
    }
}

