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

use pocketmine\entity\object\FallingBlock;
use pocketmine\inventory\AnvilInventory;
use pocketmine\item\Item;
use pocketmine\item\TieredTool;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Player;

class Anvil extends Fallable{

	public const TYPE_NORMAL = 0;
	public const TYPE_SLIGHTLY_DAMAGED = 4;
	public const TYPE_VERY_DAMAGED = 8;

	protected $id = self::ANVIL;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function isTransparent() : bool{
        return true;
    }

    public function getHardness() : float{
        return 5;
    }

    public function getBlastResistance() : float{
        return 6000;
    }

    public function getVariantBitmask() : int{
        return 0x0c;
    }

    public function getName() : string{
        static $names = [
            self::TYPE_NORMAL => "Anvil",
            self::TYPE_SLIGHTLY_DAMAGED => "Slightly Damaged Anvil",
            self::TYPE_VERY_DAMAGED => "Very Damaged Anvil"
        ];
        return $names[$this->getVariant()] ?? "Anvil";
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_PICKAXE;
    }

    public function getToolHarvestLevel() : int{
        return TieredTool::TIER_WOODEN;
    }

    public function recalculateBoundingBox() : ?AxisAlignedBB{
        $inset = 0.125;

        if(($this->meta & 0x01) !== 0){ //east/west
            return new AxisAlignedBB(
                $this->x,
                $this->y,
                $this->z + $inset,
                $this->x + 1,
                $this->y + 1,
                $this->z + 1 - $inset
            );
        }else{
            return new AxisAlignedBB(
                $this->x + $inset,
                $this->y,
                $this->z,
                $this->x + 1 - $inset,
                $this->y + 1,
                $this->z + 1
            );
        }
    }

    public function onActivate(Item $item, Player $player = null) : bool{
        if($player instanceof Player){
            $player->addWindow(new AnvilInventory($this));
        }

        return true;
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        $direction = ($player !== null ? $player->getDirection() : 0) & 0x03;
        $this->meta = $this->getVariant() | $direction;
        return $this->getLevel()->setBlock($blockReplace, $this, true, true);
    }

    public function onEndFalling(FallingBlock $fallingBlock) : Block{
        $fallDistance = ceil($fallingBlock->fallDistance - 1);

        $this->level->broadcastLevelEvent($this, LevelEventPacket::EVENT_SOUND_ANVIL_FALL);

        if($fallingBlock->random->nextFloat() < (0.05 + $fallDistance * 0.05)){
            $direction = $this->meta & 3;
            $type = $this->meta - $direction;

            if($type === Anvil::TYPE_NORMAL){
                $type = Anvil::TYPE_SLIGHTLY_DAMAGED;
            }elseif($type === Anvil::TYPE_SLIGHTLY_DAMAGED){
                $type = Anvil::TYPE_VERY_DAMAGED;
            }else{
                $this->level->broadcastLevelEvent($this, LevelEventPacket::EVENT_SOUND_ANVIL_BREAK);

                return new Air();
            }

            return new Anvil($direction | $type);
        }

        return $this;
    }
}

