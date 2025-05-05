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
use pocketmine\math\Vector3;
use pocketmine\Player;
use function mt_rand;

class DoublePlant extends Flowable{
    public const BITFLAG_TOP = 0x08;

    protected $id = self::DOUBLE_PLANT;

    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }

    public function canBeReplaced() : bool{
        return $this->getVariant() === 2 or $this->getVariant() === 3; //grass or fern
    }

    public function getName() : string{
        static $names = [
            0 => "Sunflower",
            1 => "Lilac",
            2 => "Double Tallgrass",
            3 => "Large Fern",
            4 => "Rose Bush",
            5 => "Peony"
        ];
        return $names[$this->getVariant()] ?? "";
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        $id = $blockReplace->getSide(Vector3::SIDE_DOWN)->getId();
        if(($id === Block::GRASS or $id === Block::DIRT) and $blockReplace->getSide(Vector3::SIDE_UP)->canBeReplaced()){
            $this->getLevel()->setBlock($blockReplace, $this, false, false);
            $this->getLevel()->setBlock($blockReplace->getSide(Vector3::SIDE_UP), Block::get($this->id, $this->meta | self::BITFLAG_TOP), false, false);

            return true;
        }

        return false;
    }

    /**
     * Returns whether this double-plant has a corresponding other half.
     */
    public function isValidHalfPlant() : bool{
        if(($this->meta & self::BITFLAG_TOP) !== 0){
            $other = $this->getSide(Vector3::SIDE_DOWN);
        }else{
            $other = $this->getSide(Vector3::SIDE_UP);
        }

        return (
            $other->getId() === $this->getId() and
            $other->getVariant() === $this->getVariant() and
            ($other->getDamage() & self::BITFLAG_TOP) !== ($this->getDamage() & self::BITFLAG_TOP)
        );
    }

    public function onNearbyBlockChange() : void{
        if(!$this->isValidHalfPlant() or (($this->meta & self::BITFLAG_TOP) === 0 and $this->getSide(Vector3::SIDE_DOWN)->isTransparent())){
            $this->getLevel()->useBreakOn($this);
        }
    }

    public function getVariantBitmask() : int{
        return 0x07;
    }

    public function getToolType() : int{
        return ($this->getVariant() === 2 or $this->getVariant() === 3) ? BlockToolType::TYPE_SHEARS : BlockToolType::TYPE_NONE;
    }

    public function getToolHarvestLevel() : int{
        return ($this->getVariant() === 2 or $this->getVariant() === 3) ? 1 : 0; //only grass or fern require shears
    }

    public function getDrops(Item $item) : array{
        if(($this->meta & self::BITFLAG_TOP) !== 0){
            if($this->isCompatibleWithTool($item)){
                return parent::getDrops($item);
            }

            if(mt_rand(0, 24) === 0){
                return [
                    Item::get(Item::SEEDS)
                ];
            }
        }

        return [];
    }

    public function getAffectedBlocks() : array{
        if($this->isValidHalfPlant()){
            return [$this, $this->getSide(($this->meta & self::BITFLAG_TOP) !== 0 ? Vector3::SIDE_DOWN : Vector3::SIDE_UP)];
        }

        return parent::getAffectedBlocks();
    }

    public function getFlameEncouragement() : int{
        return 60;
    }

    public function getFlammability() : int{
        return 100;
    }
}

