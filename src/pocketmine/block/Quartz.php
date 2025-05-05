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

use pocketmine\block\utils\PillarRotationHelper;
use pocketmine\item\Item;
use pocketmine\item\TieredTool;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Quartz extends Solid{

    public const NORMAL = 0;
    public const CHISELED = 1;
    public const PILLAR = 2;

    protected $id = self::QUARTZ_BLOCK;

    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }

    public function getHardness() : float{
        return 0.8;
    }

    public function getName() : string{
        static $names = [
            self::NORMAL => "Quartz Block",
            self::CHISELED => "Chiseled Quartz Block",
            self::PILLAR => "Quartz Pillar"
        ];
        return $names[$this->getVariant()] ?? "Unknown";
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        if($this->getVariant() !== self::NORMAL){
            $this->meta = PillarRotationHelper::getMetaFromFace($this->meta, $face);
        }
        return $this->getLevel()->setBlock($blockReplace, $this, true, true);
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_PICKAXE;
    }

    public function getToolHarvestLevel() : int{
        return TieredTool::TIER_WOODEN;
    }

    public function getVariantBitmask() : int{
        return 0x03;
    }
}

