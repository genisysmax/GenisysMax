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
use pocketmine\math\AxisAlignedBB;

class Air extends Transparent{

	protected $id = self::AIR;
	protected $meta = 0;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Air";
    }

    public function canPassThrough() : bool{
        return true;
    }

    public function isBreakable(Item $item) : bool{
        return false;
    }

    public function canBeFlowedInto() : bool{
        return true;
    }

    public function canBeReplaced() : bool{
        return true;
    }

    public function canBePlaced() : bool{
        return false;
    }

    public function isSolid() : bool{
        return false;
    }

    public function getBoundingBox() : ?AxisAlignedBB{
        return null;
    }

    public function getCollisionBoxes() : array{
        return [];
    }

    public function getHardness() : float{
        return -1;
    }

    public function getBlastResistance() : float{
        return 0;
    }
}

