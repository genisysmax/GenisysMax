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

class Stone extends Solid{
    public const NORMAL = 0;
    public const GRANITE = 1;
    public const POLISHED_GRANITE = 2;
    public const DIORITE = 3;
    public const POLISHED_DIORITE = 4;
    public const ANDESITE = 5;
    public const POLISHED_ANDESITE = 6;

    protected $id = self::STONE;

    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }

    public function getHardness() : float{
        return 1.5;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_PICKAXE;
    }

    public function getToolHarvestLevel() : int{
        return TieredTool::TIER_WOODEN;
    }

    public function getName() : string{
        static $names = [
            self::NORMAL => "Stone",
            self::GRANITE => "Granite",
            self::POLISHED_GRANITE => "Polished Granite",
            self::DIORITE => "Diorite",
            self::POLISHED_DIORITE => "Polished Diorite",
            self::ANDESITE => "Andesite",
            self::POLISHED_ANDESITE => "Polished Andesite"
        ];
        return $names[$this->getVariant()] ?? "Unknown";
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        if($this->getDamage() === self::NORMAL){
            return [
                Item::get(Item::COBBLESTONE, $this->getDamage())
            ];
        }

        return parent::getDropsForCompatibleTool($item);
    }
}

