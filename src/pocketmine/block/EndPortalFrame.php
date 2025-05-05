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

class EndPortalFrame extends Solid{

	protected $id = self::END_PORTAL_FRAME;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getLightLevel() : int{
        return 1;
    }

    public function getName() : string{
        return "End Portal Frame";
    }

    public function getHardness() : float{
        return -1;
    }

    public function getBlastResistance() : float{
        return 18000000;
    }

    public function isBreakable(Item $item) : bool{
        return false;
    }

    protected function recalculateBoundingBox() : ?AxisAlignedBB{

        return new AxisAlignedBB(
            $this->x,
            $this->y,
            $this->z,
            $this->x + 1,
            $this->y + (($this->getDamage() & 0x04) > 0 ? 1 : 0.8125),
            $this->z + 1
        );
    }
}

