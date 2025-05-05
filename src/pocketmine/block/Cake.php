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

use pocketmine\entity\Effect;
use pocketmine\event\entity\EntityEatBlockEvent;
use pocketmine\item\FoodSource;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Cake extends Transparent implements FoodSource{

    protected $id = self::CAKE_BLOCK;

    protected $itemId = Item::CAKE;

    public function __construct(int $meta = 0){
        $this->meta = $meta;
    }

    public function getHardness() : float{
        return 0.5;
    }

    public function getName() : string{
        return "Cake";
    }

	protected function recalculateBoundingBox() : ?AxisAlignedBB{

		$f = $this->getDamage() * 0.125;

		return new AxisAlignedBB(
			$this->x + 0.0625 + $f,
			$this->y,
			$this->z + 0.0625,
			$this->x + 1 - 0.0625,
			$this->y + 0.5,
			$this->z + 1 - 0.0625
		);
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool{
		$down = $this->getSide(Vector3::SIDE_DOWN);
		if($down->getId() !== self::AIR){
			$this->getLevel()->setBlock($blockReplace, $this, true, true);

			return true;
		}

		return false;
	}

    public function onNearbyBlockChange() : void{
        if($this->getSide(Vector3::SIDE_DOWN)->getId() === self::AIR){ //Replace with common break method
            $this->getLevel()->setBlock($this, Block::get(Block::AIR), true);
        }
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        return [];
    }

    public function isAffectedBySilkTouch() : bool{
        return false;
    }

	public function onActivate(Item $item, Player $player = null): bool
    {
		if($player instanceof Player and $player->getFood() < $player->getMaxFood()){
			$ev = new EntityEatBlockEvent($player, $this);
			$ev->call();

			if(!$ev->isCancelled()){
				$player->addFood($ev->getFoodRestore());
				$player->addSaturation($ev->getSaturationRestore());
				foreach($ev->getAdditionalEffects() as $effect){
					$player->addEffect($effect);
				}

				$this->getLevel()->setBlock($this, $ev->getResidue());
				return true;
			}
		}

		return false;
	}

	public function getFoodRestore() : int{
		return 2;
	}

	public function getSaturationRestore() : float{
		return 0.4;
	}

	public function getResidue(){
		$clone = clone $this;
		$clone->meta++;
		if($clone->meta > 0x06){
			$clone = Block::get(Block::AIR);
		}
		return $clone;
	}

	/**
	 * @return Effect[]
	 */
	public function getAdditionalEffects() : array{
		return [];
	}
}


