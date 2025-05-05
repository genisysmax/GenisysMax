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
use function mt_rand;

class PumpkinStem extends Crops{

	protected $id = self::PUMPKIN_STEM;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Pumpkin Stem";
    }

    public function onRandomTick() : void{
        if(mt_rand(0, 2) === 1){
            if($this->meta < 0x07){
                $block = clone $this;
                ++$block->meta;
                $ev = new BlockGrowEvent($this, $block);
                $ev->call();
                if(!$ev->isCancelled()){
                    $this->getLevel()->setBlock($this, $ev->getNewState(), true);
                }
            }else{
                for($side = 2; $side <= 5; ++$side){
                    $b = $this->getSide($side);
                    if($b->getId() === self::PUMPKIN){
                        return;
                    }
                }
                $side = $this->getSide(mt_rand(2, 5));
                $d = $side->getSide(Vector3::SIDE_DOWN);
                if($side->getId() === self::AIR and ($d->getId() === self::FARMLAND or $d->getId() === self::GRASS or $d->getId() === self::DIRT)){
                    $ev = new BlockGrowEvent($side, Block::get(Block::PUMPKIN));
                    $ev->call();
                    if(!$ev->isCancelled()){
                        $this->getLevel()->setBlock($side, $ev->getNewState(), true);
                    }
                }
            }
        }
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        return [
            Item::get(Item::PUMPKIN_SEEDS, 0, mt_rand(0, 2))
        ];
    }

    public function getPickedItem() : Item{
        return Item::get(Item::PUMPKIN_SEEDS);
    }
}

