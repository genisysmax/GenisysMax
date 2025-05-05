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


use pocketmine\event\block\BlockGrowEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;
use function mt_rand;

class NetherWartPlant extends Flowable{
	protected $id = Block::NETHER_WART_PLANT;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool{
		$down = $this->getSide(Vector3::SIDE_DOWN);
		if($down->getId() === Block::SOUL_SAND){
			$this->getLevel()->setBlock($blockReplace, $this, false, true);

			return true;
		}

		return false;
	}

    public function onNearbyBlockChange() : void{
        if($this->getSide(Vector3::SIDE_DOWN)->getId() !== Block::SOUL_SAND){
            $this->getLevel()->useBreakOn($this);
        }
    }

    public function ticksRandomly() : bool{
        return true;
    }

    public function onRandomTick() : void{
        if($this->meta < 3 and mt_rand(0, 10) === 0){ //Still growing
            $block = clone $this;
            $block->meta++;
            $ev = new BlockGrowEvent($this, $block);
            $ev->call();
            if(!$ev->isCancelled()){
                $this->getLevel()->setBlock($this, $ev->getNewState(), false, true);
            }
        }
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        return [
            Item::get($this->getItemId(), 0, ($this->getDamage() === 3 ? mt_rand(2, 4) : 1))
        ];
    }

    public function isAffectedBySilkTouch() : bool{
        return false;
    }
}

