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
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;
use function max;
use function min;

class Vine extends Flowable{
	public const FLAG_SOUTH = 0x01;
	public const FLAG_WEST = 0x02;
	public const FLAG_NORTH = 0x04;
	public const FLAG_EAST = 0x08;

	protected $id = self::VINE;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Vines";
    }

    public function getHardness() : float{
        return 0.2;
    }

    public function canPassThrough() : bool{
        return true;
    }

    public function hasEntityCollision() : bool{
        return true;
    }

    public function canClimb() : bool{
        return true;
    }

    public function canBeReplaced() : bool{
        return true;
    }

    public function onEntityCollide(Entity $entity) : void{
        $entity->resetFallDistance();
    }

    protected function recalculateBoundingBox() : ?AxisAlignedBB{

        $minX = 1;
        $minY = 1;
        $minZ = 1;
        $maxX = 0;
        $maxY = 0;
        $maxZ = 0;

        $flag = $this->meta > 0;

        if(($this->meta & self::FLAG_WEST) > 0){
            $maxX = max($maxX, 0.0625);
            $minX = 0;
            $minY = 0;
            $maxY = 1;
            $minZ = 0;
            $maxZ = 1;
            $flag = true;
        }

        if(($this->meta & self::FLAG_EAST) > 0){
            $minX = min($minX, 0.9375);
            $maxX = 1;
            $minY = 0;
            $maxY = 1;
            $minZ = 0;
            $maxZ = 1;
            $flag = true;
        }

        if(($this->meta & self::FLAG_SOUTH) > 0){
            $minZ = min($minZ, 0.9375);
            $maxZ = 1;
            $minX = 0;
            $maxX = 1;
            $minY = 0;
            $maxY = 1;
            $flag = true;
        }

        //TODO: Missing NORTH check

        if(!$flag and $this->getSide(Vector3::SIDE_UP)->isSolid()){
            $minY = min($minY, 0.9375);
            $maxY = 1;
            $minX = 0;
            $maxX = 1;
            $minZ = 0;
            $maxZ = 1;
        }

        return new AxisAlignedBB(
            $this->x + $minX,
            $this->y + $minY,
            $this->z + $minZ,
            $this->x + $maxX,
            $this->y + $maxY,
            $this->z + $maxZ
        );
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        if(!$blockClicked->isSolid() or $face === Vector3::SIDE_UP or $face === Vector3::SIDE_DOWN){
            return false;
        }

        $faces = [
            Vector3::SIDE_NORTH => self::FLAG_SOUTH,
            Vector3::SIDE_SOUTH => self::FLAG_NORTH,
            Vector3::SIDE_WEST => self::FLAG_EAST,
            Vector3::SIDE_EAST => self::FLAG_WEST
        ];

        $this->meta = $faces[$face] ?? 0;
        if($blockReplace->getId() === $this->getId()){
            $this->meta |= $blockReplace->meta;
        }

        $this->getLevel()->setBlock($blockReplace, $this, true, true);
        return true;
    }

    public function onNearbyBlockChange() : void{
        $sides = [
            self::FLAG_SOUTH => Vector3::SIDE_SOUTH,
            self::FLAG_WEST => Vector3::SIDE_WEST,
            self::FLAG_NORTH => Vector3::SIDE_NORTH,
            self::FLAG_EAST => Vector3::SIDE_EAST
        ];

        $meta = $this->meta;

        foreach($sides as $flag => $side){
            if(($meta & $flag) === 0){
                continue;
            }

            if(!$this->getSide($side)->isSolid()){
                $meta &= ~$flag;
            }
        }

        if($meta !== $this->meta){
            if($meta === 0){
                $this->level->useBreakOn($this);
            }else{
                $this->meta = $meta;
                $this->level->setBlock($this, $this);
            }
        }
    }

    public function ticksRandomly() : bool{
        return true;
    }

    public function onRandomTick() : void{
        //TODO: vine growth
    }

    public function getVariantBitmask() : int{
        return 0;
    }

    public function getDrops(Item $item) : array{
        if(($item->getBlockToolType() & BlockToolType::TYPE_SHEARS) !== 0){
            return $this->getDropsForCompatibleTool($item);
        }

        return [];
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_AXE;
    }

    public function getFlameEncouragement() : int{
        return 15;
    }

    public function getFlammability() : int{
        return 100;
    }
}

