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

abstract class Slab extends Transparent
{

    public function __construct(int $meta = 0)
    {
        $this->meta = $meta;
    }

    abstract public function getDoubleSlabId(): int;

    public function canBePlacedAt(Block $blockReplace, Vector3 $clickVector, int $face, bool $isClickedBlock): bool
    {
        if (parent::canBePlacedAt($blockReplace, $clickVector, $face, $isClickedBlock)) {
            return true;
        }

        if ($blockReplace->getId() === $this->getId() and $blockReplace->getVariant() === $this->getVariant()) {
            if (($blockReplace->getDamage() & 0x08) !== 0) { //Trying to combine with top slab
                return $clickVector->y <= 0.5 or (!$isClickedBlock and $face === Vector3::SIDE_UP);
            } else {
                return $clickVector->y >= 0.5 or (!$isClickedBlock and $face === Vector3::SIDE_DOWN);
            }
        }

        return false;
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool
    {
        $this->meta &= 0x07;
        if ($face === Vector3::SIDE_DOWN) {
            if ($blockClicked->getId() === $this->id and ($blockClicked->getDamage() & 0x08) === 0x08 and $blockClicked->getVariant() === $this->getVariant()) {
                $this->getLevel()->setBlock($blockClicked, Block::get($this->getDoubleSlabId(), $this->getVariant()), true);

                return true;
            } elseif ($blockReplace->getId() === $this->id and $blockReplace->getVariant() === $this->getVariant()) {
                $this->getLevel()->setBlock($blockReplace, Block::get($this->getDoubleSlabId(), $this->getVariant()), true);

                return true;
            } else {
                $this->meta |= 0x08;
            }
        } elseif ($face === Vector3::SIDE_UP) {
            if ($blockClicked->getId() === $this->id and ($blockClicked->getDamage() & 0x08) === 0 and $blockClicked->getVariant() === $this->getVariant()) {
                $this->getLevel()->setBlock($blockClicked, Block::get($this->getDoubleSlabId(), $this->getVariant()), true);

                return true;
            } elseif ($blockReplace->getId() === $this->id and $blockReplace->getVariant() === $this->getVariant()) {
                $this->getLevel()->setBlock($blockReplace, Block::get($this->getDoubleSlabId(), $this->getVariant()), true);

                return true;
            }
        } else { //TODO: collision
            if ($blockReplace->getId() === $this->id) {
                if ($blockReplace->getVariant() === $this->getVariant()) {
                    $this->getLevel()->setBlock($blockReplace, Block::get($this->getDoubleSlabId(), $this->getVariant()), true);

                    return true;
                }

                return false;
            } else {
                if ($clickVector->y > 0.5) {
                    $this->meta |= 0x08;
                }
            }
        }

        if ($blockReplace->getId() === $this->id and $blockClicked->getVariant() !== $this->getVariant()) {
            return false;
        }
        $this->getLevel()->setBlock($blockReplace, $this, true, true);

        return true;
    }

    public function getVariantBitmask(): int
    {
        return 0x07;
    }

    protected function recalculateBoundingBox(): ?AxisAlignedBB
    {

        if (($this->meta & 0x08) > 0) {
            return new AxisAlignedBB(
                $this->x,
                $this->y + 0.5,
                $this->z,
                $this->x + 1,
                $this->y + 1,
                $this->z + 1
            );
        } else {
            return new AxisAlignedBB(
                $this->x,
                $this->y,
                $this->z,
                $this->x + 1,
                $this->y + 0.5,
                $this->z + 1
            );
        }
    }
}

