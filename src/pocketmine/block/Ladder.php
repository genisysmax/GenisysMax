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

use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Ladder extends Transparent{

	protected $id = self::LADDER;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Ladder";
    }

    public function hasEntityCollision() : bool{
        return true;
    }

    public function isSolid() : bool{
        return false;
    }

    public function getHardness() : float{
        return 0.4;
    }

    public function canClimb() : bool{
        return true;
    }

    public function onEntityCollide(Entity $entity) : void{
        if($entity instanceof Living and $entity->asVector3()->floor()->distanceSquared($this) < 1){ //entity coordinates must be inside block
            $entity->resetFallDistance();
            $entity->onGround = true;
        }
    }

    protected function recalculateBoundingBox() : ?AxisAlignedBB{
        $f = 0.1875;

        $minX = $minZ = 0;
        $maxX = $maxZ = 1;

        if($this->meta === 2){
            $minZ = 1 - $f;
        }elseif($this->meta === 3){
            $maxZ = $f;
        }elseif($this->meta === 4){
            $minX = 1 - $f;
        }elseif($this->meta === 5){
            $maxX = $f;
        }

        return new AxisAlignedBB(
            $this->x + $minX,
            $this->y,
            $this->z + $minZ,
            $this->x + $maxX,
            $this->y + 1,
            $this->z + $maxZ
        );
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        if(!$blockClicked->isTransparent()){
            $faces = [
                2 => 2,
                3 => 3,
                4 => 4,
                5 => 5
            ];
            if(isset($faces[$face])){
                $this->meta = $faces[$face];
                $this->getLevel()->setBlock($blockReplace, $this, true, true);

                return true;
            }
        }

        return false;
    }

    public function onNearbyBlockChange() : void{
        if(!$this->getSide($this->meta ^ 0x01)->isSolid()){ //Replace with common break method
            $this->level->useBreakOn($this);
        }
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_AXE;
    }

    public function getVariantBitmask() : int{
        return 0;
    }
}

