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

use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;

class Fence extends Transparent{
    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }

    public function getThickness() : float{
        return 0.25;
    }

    protected function recalculateBoundingBox() : ?AxisAlignedBB{
        $width = 0.5 - $this->getThickness() / 2;

        return new AxisAlignedBB(
            $this->x + ($this->canConnect($this->getSide(Vector3::SIDE_WEST)) ? 0 : $width),
            $this->y,
            $this->z + ($this->canConnect($this->getSide(Vector3::SIDE_NORTH)) ? 0 : $width),
            $this->x + 1 - ($this->canConnect($this->getSide(Vector3::SIDE_EAST)) ? 0 : $width),
            $this->y + 1.5,
            $this->z + 1 - ($this->canConnect($this->getSide(Vector3::SIDE_SOUTH)) ? 0 : $width)
        );
    }

    protected function recalculateCollisionBoxes() : array{
        $inset = 0.5 - $this->getThickness() / 2;

        /** @var AxisAlignedBB[] $bbs */
        $bbs = [];

        $connectWest = $this->canConnect($this->getSide(Vector3::SIDE_WEST));
        $connectEast = $this->canConnect($this->getSide(Vector3::SIDE_EAST));

        if($connectWest or $connectEast){
            //X axis (west/east)
            $bbs[] = new AxisAlignedBB(
                $this->x + ($connectWest ? 0 : $inset),
                $this->y,
                $this->z + $inset,
                $this->x + 1 - ($connectEast ? 0 : $inset),
                $this->y + 1.5,
                $this->z + 1 - $inset
            );
        }

        $connectNorth = $this->canConnect($this->getSide(Vector3::SIDE_NORTH));
        $connectSouth = $this->canConnect($this->getSide(Vector3::SIDE_SOUTH));

        if($connectNorth or $connectSouth){
            //Z axis (north/south)
            $bbs[] = new AxisAlignedBB(
                $this->x + $inset,
                $this->y,
                $this->z + ($connectNorth ? 0 : $inset),
                $this->x + 1 - $inset,
                $this->y + 1.5,
                $this->z + 1 - ($connectSouth ? 0 : $inset)
            );
        }

        if(count($bbs) === 0){
            //centre post AABB (only needed if not connected on any axis - other BBs overlapping will do this if any connections are made)
            return [
                new AxisAlignedBB(
                    $this->x + $inset,
                    $this->y,
                    $this->z + $inset,
                    $this->x + 1 - $inset,
                    $this->y + 1.5,
                    $this->z + 1 - $inset
                )
            ];
        }

        return $bbs;
    }

    /**
     * @return bool
     */
    public function canConnect(Block $block){
        return $block instanceof static or $block instanceof FenceGate or ($block->isSolid() and !$block->isTransparent());
    }
}


