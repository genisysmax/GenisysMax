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

namespace pocketmine\tile;

use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;

class Bed extends Spawnable{
    public const TAG_COLOR = "color";
    /** @var int */
    private int $color = 14; //default to old red

    public function getColor() : int{
        return $this->color;
    }

    public function setColor(int $color): void{
        $this->color = $color & 0xf;
        $this->onChanged();
    }

    protected function readSaveData(CompoundTag $nbt) : void{
        $this->color = $nbt->getByte(self::TAG_COLOR, 14, true);
    }

    protected function writeSaveData(CompoundTag $nbt) : void{
        $nbt->setByte(self::TAG_COLOR, $this->color);
    }

    protected function addAdditionalSpawnData(CompoundTag $nbt, bool $isBedrock) : void{
        $nbt->setByte(self::TAG_COLOR, $this->color);
    }

    protected static function createAdditionalNBT(CompoundTag $nbt, Vector3 $pos, ?int $face = null, ?Item $item = null, ?Player $player = null) : void{
        if($item !== null){
            $nbt->setByte(self::TAG_COLOR, $item->getDamage());
        }
    }
}

