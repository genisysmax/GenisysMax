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

use pocketmine\block\utils\PillarRotationHelper;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Wood extends Solid{
	public const OAK = 0;
	public const SPRUCE = 1;
	public const BIRCH = 2;
	public const JUNGLE = 3;

	protected $id = self::WOOD;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getHardness() : float{
        return 2;
    }

    public function getName() : string{
        static $names = [
            self::OAK => "Oak Wood",
            self::SPRUCE => "Spruce Wood",
            self::BIRCH => "Birch Wood",
            self::JUNGLE => "Jungle Wood"
        ];
        return $names[$this->getVariant()] ?? "Unknown";
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        $this->meta = PillarRotationHelper::getMetaFromFace($this->meta, $face);
        return $this->getLevel()->setBlock($blockReplace, $this, true, true);
    }

    public function getVariantBitmask() : int{
        return 0x03;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_AXE;
    }

    public function getFuelTime() : int{
        return 300;
    }

    public function getFlameEncouragement() : int{
        return 5;
    }

    public function getFlammability() : int{
        return 5;
    }
}

