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

class Skull extends Spawnable
{
    public const TYPE_SKELETON = 0;
    public const TYPE_WITHER = 1;
    public const TYPE_ZOMBIE = 2;
    public const TYPE_HUMAN = 3;
    public const TYPE_CREEPER = 4;
    public const TYPE_DRAGON = 5;

    public const TAG_SKULL_TYPE = "SkullType"; //TAG_Byte
    public const TAG_ROT = "Rot"; //TAG_Byte
    public const TAG_MOUTH_MOVING = "MouthMoving"; //TAG_Byte
    public const TAG_MOUTH_TICK_COUNT = "MouthTickCount"; //TAG_Int

    /** @var int */
    private int $skullType;
    /** @var int */
    private int $skullRotation;

    protected function readSaveData(CompoundTag $nbt): void
    {
        $this->skullType = $nbt->getByte(self::TAG_SKULL_TYPE, self::TYPE_SKELETON, true);
        $this->skullRotation = $nbt->getByte(self::TAG_ROT, 0, true);
    }

    protected function writeSaveData(CompoundTag $nbt): void
    {
        $nbt->setByte(self::TAG_SKULL_TYPE, $this->skullType);
        $nbt->setByte(self::TAG_ROT, $this->skullRotation);
    }

    public function setType(int $type): void
    {
        $this->skullType = $type;
        $this->onChanged();
    }

    public function getType(): int
    {
        return $this->skullType;
    }

    protected function addAdditionalSpawnData(CompoundTag $nbt, bool $isBedrock): void
    {
        $nbt->setByte(self::TAG_SKULL_TYPE, $this->skullType);
        $nbt->setByte(self::TAG_ROT, $this->skullRotation);
    }

    protected static function createAdditionalNBT(CompoundTag $nbt, Vector3 $pos, ?int $face = null, ?Item $item = null, ?Player $player = null): void
    {
        $nbt->setByte(self::TAG_SKULL_TYPE, $item !== null ? $item->getDamage() : self::TYPE_SKELETON);

        $rot = 0;
        if ($face === Vector3::SIDE_UP and $player !== null) {
            $rot = floor(($player->yaw * 16 / 360) + 0.5) & 0x0F;
        }
        $nbt->setByte(self::TAG_ROT, $rot);
    }
}

