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

use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;
use function mt_rand;

class Leaves extends Transparent{
	public const OAK = 0;
	public const SPRUCE = 1;
	public const BIRCH = 2;
	public const JUNGLE = 3;
	public const ACACIA = 0;
	public const DARK_OAK = 1;

	protected $id = self::LEAVES;
	protected $woodType = self::WOOD;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getHardness() : float{
        return 0.2;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_SHEARS;
    }

    public function getName() : string{
        static $names = [
            self::OAK => "Oak Leaves",
            self::SPRUCE => "Spruce Leaves",
            self::BIRCH => "Birch Leaves",
            self::JUNGLE => "Jungle Leaves"
        ];
        return $names[$this->getVariant()];
    }

    public function diffusesSkyLight() : bool{
        return true;
    }

    protected function findLog(Block $pos, array &$visited, int $distance, ?int $fromSide = null) : bool{
        $index = $pos->x . "." . $pos->y . "." . $pos->z;
        if(isset($visited[$index])){
            return false;
        }
        if($pos->getId() === $this->woodType){
            return true;
        }elseif($pos->getId() === $this->id and $distance < 3){
            $visited[$index] = true;
            $down = $pos->getSide(Vector3::SIDE_DOWN)->getId();
            if($down === $this->woodType){
                return true;
            }
            if($fromSide === null){
                for($side = 2; $side <= 5; ++$side){
                    if($this->findLog($pos->getSide($side), $visited, $distance + 1, $side)){
                        return true;
                    }
                }
            }else{ //No more loops
                switch($fromSide){
                    case 2:
                        if($this->findLog($pos->getSide(Vector3::SIDE_NORTH), $visited, $distance + 1, $fromSide)){
                            return true;
                        }elseif($this->findLog($pos->getSide(Vector3::SIDE_WEST), $visited, $distance + 1, $fromSide)){
                            return true;
                        }elseif($this->findLog($pos->getSide(Vector3::SIDE_EAST), $visited, $distance + 1, $fromSide)){
                            return true;
                        }
                        break;
                    case 3:
                        if($this->findLog($pos->getSide(Vector3::SIDE_SOUTH), $visited, $distance + 1, $fromSide)){
                            return true;
                        }elseif($this->findLog($pos->getSide(Vector3::SIDE_WEST), $visited, $distance + 1, $fromSide)){
                            return true;
                        }elseif($this->findLog($pos->getSide(Vector3::SIDE_EAST), $visited, $distance + 1, $fromSide)){
                            return true;
                        }
                        break;
                    case 4:
                        if($this->findLog($pos->getSide(Vector3::SIDE_NORTH), $visited, $distance + 1, $fromSide)){
                            return true;
                        }elseif($this->findLog($pos->getSide(Vector3::SIDE_SOUTH), $visited, $distance + 1, $fromSide)){
                            return true;
                        }elseif($this->findLog($pos->getSide(Vector3::SIDE_WEST), $visited, $distance + 1, $fromSide)){
                            return true;
                        }
                        break;
                    case 5:
                        if($this->findLog($pos->getSide(Vector3::SIDE_NORTH), $visited, $distance + 1, $fromSide)){
                            return true;
                        }elseif($this->findLog($pos->getSide(Vector3::SIDE_SOUTH), $visited, $distance + 1, $fromSide)){
                            return true;
                        }elseif($this->findLog($pos->getSide(Vector3::SIDE_EAST), $visited, $distance + 1, $fromSide)){
                            return true;
                        }
                        break;
                }
            }
        }

        return false;
    }

    public function onNearbyBlockChange() : void{
        if(($this->meta & 0b00001100) === 0){
            $this->meta |= 0x08;
            $this->getLevel()->setBlock($this, $this, true, false);
        }
    }

    public function ticksRandomly() : bool{
        return true;
    }

    public function onRandomTick() : void{
        if(($this->meta & 0b00001100) === 0x08){
            $this->meta &= 0x03;
            $visited = [];

            $ev = new LeavesDecayEvent($this);
            $ev->call();
            if($ev->isCancelled() or $this->findLog($this, $visited, 0)){
                $this->getLevel()->setBlock($this, $this, false, false);
            }else{
                $this->getLevel()->useBreakOn($this);
            }
        }
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        $this->meta |= 0x04;
        return $this->getLevel()->setBlock($this, $this, true);
    }

    public function getVariantBitmask() : int{
        return 0x03;
    }

    public function getDrops(Item $item) : array{
        if(($item->getBlockToolType() & BlockToolType::TYPE_SHEARS) !== 0){
            return $this->getDropsForCompatibleTool($item);
        }

        $drops = [];
        if(mt_rand(1, 20) === 1){ //Saplings
            $drops[] = $this->getSaplingItem();
        }
        if($this->canDropApples() and mt_rand(1, 200) === 1){ //Apples
            $drops[] = Item::get(Item::APPLE);
        }

        return $drops;
    }

    public function getSaplingItem() : Item{
        return Item::get(Item::SAPLING, $this->getVariant());
    }

    public function canDropApples() : bool{
        return $this->getVariant() === self::OAK;
    }

    public function getFlameEncouragement() : int{
        return 30;
    }

    public function getFlammability() : int{
        return 60;
    }
}

