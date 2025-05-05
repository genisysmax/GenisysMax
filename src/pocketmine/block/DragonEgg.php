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



namespace pocketmine\block;

use pocketmine\event\block\BlockDragonEggTeleportEvent;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Player;

class DragonEgg extends Fallable
{

	protected $id = self::DRAGON_EGG;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Dragon Egg";
    }

    public function getHardness() : float{
        return 3;
    }

    public function getBlastResistance() : float{
        return 45;
    }

    public function getLightLevel() : int{
        return 1;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_PICKAXE;
    }

    public function onActivate(Item $item, Player $player = null): bool{
        return $this->randomTeleport();
    }

    public function randomTeleport() : bool
    {
        for ($i = 0; $i < 1000; ++$i) {
            $to = $this->level->getBlock($this->add(mt_rand(-15, 15), mt_rand(-7, 7), mt_rand(-15, 15)));
            if ($to instanceof Air) {
                $ev = new BlockDragonEggTeleportEvent($this, $to);
                $ev->call();
                if($ev->isCancelled()){
                    return false;
                }
                $to = $ev->getTo();
                $diffX = $this->getFloorX() - $to->getFloorX();
                $diffY = $this->getFloorY() - $to->getFloorY();
                $diffZ = $this->getFloorZ() - $to->getFloorZ();
                $pk = new LevelEventPacket();
                $pk->evid = LevelEventPacket::EVENT_PARTICLE_DRAGON_EGG_TELEPORT;
                $pk->data = (((((abs($diffX) << 16) | (abs($diffY) << 8)) | abs($diffZ)) | (($diffX < 0 ? 1 : 0) << 24)) | (($diffY < 0 ? 1 : 0) << 25)) | (($diffZ < 0 ? 1 : 0) << 26);
                $pk->x = $this->getFloorX();
                $pk->y = $this->getFloorY();
                $pk->z = $this->getFloorZ();
                $this->level->addChunkPacket($this->getFloorX() >> 4, $this->getFloorZ() >> 4, $pk);
                $this->level->setBlock($this, Block::get(0), true);
                $this->level->setBlock($to, $this, true);
                return true;
            }
        }
        return false;
    }

	public function isBreakable(Item $item): bool{
		return false;
	}
}


