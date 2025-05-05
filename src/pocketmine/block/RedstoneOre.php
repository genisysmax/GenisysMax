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
use pocketmine\item\TieredTool;
use pocketmine\math\Vector3;
use pocketmine\Player;
use function mt_rand;

class RedstoneOre extends Solid{

	protected $id = self::REDSTONE_ORE;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Redstone Ore";
    }

    public function getHardness() : float{
        return 3;
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        return $this->getLevel()->setBlock($this, $this, true, false);
    }

    public function onActivate(Item $item, Player $player = null) : bool{
        $this->getLevel()->setBlock($this, Block::get(Block::GLOWING_REDSTONE_ORE, $this->meta));
        return false; //this shouldn't prevent block placement
    }

    public function onNearbyBlockChange() : void{
        $this->getLevel()->setBlock($this, Block::get(Block::GLOWING_REDSTONE_ORE, $this->meta));
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_PICKAXE;
    }

    public function getToolHarvestLevel() : int{
        return TieredTool::TIER_IRON;
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        return [
            Item::get(Item::REDSTONE_DUST, 0, mt_rand(4, 5))
        ];
    }

    protected function getXpDropAmount() : int{
        return mt_rand(1, 5);
    }
}

