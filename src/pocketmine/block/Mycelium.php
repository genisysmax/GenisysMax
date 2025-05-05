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

use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use function mt_rand;

class Mycelium extends Solid{

	protected $id = self::MYCELIUM;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Mycelium";
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_SHOVEL;
    }

    public function getHardness() : float{
        return 0.6;
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        return [
            Item::get(Item::DIRT)
        ];
    }

    public function ticksRandomly() : bool{
        return true;
    }

    public function onRandomTick() : void{
        //TODO: light levels
        $x = mt_rand($this->x - 1, $this->x + 1);
        $y = mt_rand($this->y - 2, $this->y + 2);
        $z = mt_rand($this->z - 1, $this->z + 1);
        $block = $this->getLevel()->getBlockAt($x, $y, $z);
        if($block->getId() === Block::DIRT){
            if($block->getSide(Vector3::SIDE_UP) instanceof Transparent){
                $ev = new BlockSpreadEvent($block, $this, Block::get(Block::MYCELIUM));
                $ev->call();
                if(!$ev->isCancelled()){
                    $this->getLevel()->setBlock($block, $ev->getNewState());
                }
            }
        }
    }
}


