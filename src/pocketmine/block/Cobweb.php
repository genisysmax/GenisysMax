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

use pocketmine\entity\Entity;
use pocketmine\item\Item;

class Cobweb extends Flowable{

	protected $id = self::COBWEB;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function hasEntityCollision() : bool{
        return true;
    }

    public function getName() : string{
        return "Cobweb";
    }

    public function getHardness() : float{
        return 4;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_SWORD | BlockToolType::TYPE_SHEARS;
    }

    public function getToolHarvestLevel() : int{
        return 1;
    }

    public function onEntityCollide(Entity $entity) : void{
        $entity->resetFallDistance();
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        return [
            Item::get(Item::STRING)
        ];
    }

    public function diffusesSkyLight() : bool{
        return true;
    }
}

