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
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Carpet extends Flowable{

	protected $id = self::CARPET;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getHardness() : float{
        return 0.1;
    }

    public function isSolid() : bool{
        return true;
    }

    public function getName() : string{
        return ColorBlockMetaHelper::getColorFromMeta($this->getVariant()) . " Carpet";
    }

    protected function recalculateBoundingBox() : ?AxisAlignedBB{

        return new AxisAlignedBB(
            $this->x,
            $this->y,
            $this->z,
            $this->x + 1,
            $this->y + 0.0625,
            $this->z + 1
        );
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        $down = $this->getSide(Vector3::SIDE_DOWN);
        if($down->getId() !== self::AIR){
            $this->getLevel()->setBlock($blockReplace, $this, true, true);

            return true;
        }

        return false;
    }

    public function onNearbyBlockChange() : void{
        if($this->getSide(Vector3::SIDE_DOWN)->getId() === self::AIR){
            $this->getLevel()->useBreakOn($this);
        }
    }

    public function getFlameEncouragement() : int{
        return 30;
    }

    public function getFlammability() : int{
        return 20;
    }
}

