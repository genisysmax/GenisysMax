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
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\object\FallingBlock;
use pocketmine\math\Vector3;

abstract class Fallable extends Solid{

    public function onNearbyBlockChange() : void{
        $down = $this->getSide(Vector3::SIDE_DOWN);
        if($down->getId() === self::AIR or $down instanceof Liquid or $down instanceof Fire){
            $this->level->setBlock($this, Block::get(Block::AIR), true);

            $nbt = EntityDataHelper::createBaseNBT($this->add(0.5, 0, 0.5));
            $nbt->setInt("TileID", $this->getId());
            $nbt->setByte("Data", $this->getDamage());

            $fall = Entity::createEntity("FallingSand", $this->getLevel(), $nbt);

            if($fall instanceof FallingBlock){
                $fall->spawnToAll();

                $this->onStartFalling($fall);
            }
        }
    }

    public function tickFalling() : ?Block{
        return null;
    }

    public function onEndFalling(FallingBlock $fallingBlock) : Block{
        return $this;
    }

    public function onStartFalling(FallingBlock $fallingBlock) : void{
        // NOOP
    }
}

