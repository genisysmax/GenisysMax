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
use pocketmine\item\TieredTool;
use pocketmine\math\Vector3;
use pocketmine\Player;

class SnowLayer extends Flowable{

	protected $id = self::SNOW_LAYER;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Snow Layer";
    }

    public function canBeReplaced() : bool{
        return $this->meta < 7; //8 snow layers
    }

    public function getHardness() : float{
        return 0.1;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_SHOVEL;
    }

    public function getToolHarvestLevel() : int{
        return TieredTool::TIER_WOODEN;
    }

    private function canBeSupportedBy(Block $b) : bool{
        return $b->isSolid() or ($b->getId() === $this->getId() and $b->getDamage() === 7);
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        if($blockReplace->getId() === $this->getId() and $blockReplace->getDamage() < 7){
            $this->setDamage($blockReplace->getDamage() + 1);
        }
        if($this->canBeSupportedBy($blockReplace->getSide(Vector3::SIDE_DOWN))){
            $this->getLevel()->setBlock($blockReplace, $this, true);

            return true;
        }

        return false;
    }

    public function onNearbyBlockChange() : void{
        $vec3 = $this->asVector3();
        if(!$this->canBeSupportedBy($this->getSide(Vector3::SIDE_DOWN))){
            $this->getLevel()->setBlock($this, Block::get(Block::AIR), false, false);
            $nbt = Entity::createBaseNBT($vec3->add(0.5, 0, 0.5));
            $nbt->setInt("TileID", $this->getId());
            $nbt->setByte("Data", $this->getDamage());

            $fall = Entity::createEntity("FallingSand", $this->getLevel(), $nbt);

            if($fall !== null){
                $fall->spawnToAll();
            }
        }
    }

    public function ticksRandomly() : bool{
        return true;
    }

    public function onRandomTick() : void{
        if($this->level->getBlockLightAt($this->x, $this->y, $this->z) >= 12){
            $this->getLevel()->setBlock($this, Block::get(Block::AIR), false, false);
        }
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        return [
            Item::get(Item::SNOWBALL) //TODO: check layer count
        ];
    }

    public function isAffectedBySilkTouch() : bool{
        return false;
    }
}

