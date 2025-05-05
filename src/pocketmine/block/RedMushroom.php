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
use pocketmine\math\Vector3;
use pocketmine\Player;

class RedMushroom extends Flowable{

	protected $id = self::RED_MUSHROOM;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Red Mushroom";
    }

    public function ticksRandomly() : bool{
        return true;
    }

    public function onNearbyBlockChange() : void{
        if($this->getSide(Vector3::SIDE_DOWN)->isTransparent()){
            $this->getLevel()->useBreakOn($this);
        }
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        $down = $this->getSide(Vector3::SIDE_DOWN);
        if(!$down->isTransparent()){
            $this->getLevel()->setBlock($blockReplace, $this, true, true);

            return true;
        }

        return false;
    }
}

