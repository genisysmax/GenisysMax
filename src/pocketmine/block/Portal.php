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
use pocketmine\Player;

class Portal extends Transparent {

	protected $id = self::PORTAL;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Nether Portal";
    }

    public function getHardness() : float{
        return -1;
    }

    public function getBlastResistance() : float{
        return 0;
    }

    public function getLightLevel() : int{
        return 11;
    }

    public function isBreakable(Item $item) : bool{
        return false;
    }

    public function onBreak(Item $item, Player $player = null) : bool{
        $result = parent::onBreak($item, $player);

        foreach($this->getHorizontalSides() as $side){
            if($side instanceof Portal){
                $side->onBreak($item, $player);
            }
        }

        return $result;
    }
}

