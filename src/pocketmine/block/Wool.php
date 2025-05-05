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
use pocketmine\item\Item;
use pocketmine\item\Tool;

class Wool extends Solid{

	protected $id = self::WOOL;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getHardness() : float{
		return 0.8;
	}

	public function getToolType() : int{
		return Tool::TYPE_SHEARS;
	}

    public function getName() : string{
        return ColorBlockMetaHelper::getColorFromMeta($this->getVariant()) . " Wool";
    }

	public function getBreakTime(Item $item) : float{
		$time = parent::getBreakTime($item);
		if($item->getBlockToolType() === Tool::TYPE_SHEARS){
			$time *= 3; //shears break compatible blocks 15x faster, but wool 5x
		}

		return $time;
	}

    public function getFlameEncouragement() : int{
        return 30;
    }

    public function getFlammability() : int{
        return 60;
    }
}

