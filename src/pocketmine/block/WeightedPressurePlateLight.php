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

use pocketmine\item\TieredTool;

class WeightedPressurePlateLight extends Transparent{

	protected $id = self::LIGHT_WEIGHTED_PRESSURE_PLATE;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "Weighted Pressure Plate Light";
    }

    public function isSolid() : bool{
        return false;
    }

    public function getHardness() : float{
        return 0.5;
    }

    public function getVariantBitmask() : int{
        return 0;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_PICKAXE;
    }

    public function getToolHarvestLevel() : int{
        return TieredTool::TIER_WOODEN;
    }
}


