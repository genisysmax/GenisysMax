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

use pocketmine\block\utils\ColorBlockMetaHelper;

class ConcretePowder extends Fallable{

	protected $id = self::CONCRETE_POWDER;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return ColorBlockMetaHelper::getColorFromMeta($this->getVariant()) . " Concrete Powder";
    }

    public function getHardness() : float{
        return 0.5;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_SHOVEL;
    }

    public function onNearbyBlockChange() : void{
        if(($block = $this->checkAdjacentWater()) !== null){
            $this->level->setBlock($this, $block);
        }else{
            parent::onNearbyBlockChange();
        }
    }

    public function tickFalling() : ?Block{
        return $this->checkAdjacentWater();
    }

    private function checkAdjacentWater() : ?Block{
        for($i = 1; $i < 6; ++$i){ //Do not check underneath
            if($this->getSide($i) instanceof Water){
                return Block::get(Block::CONCRETE, $this->meta);
            }
        }

        return null;
    }
}


