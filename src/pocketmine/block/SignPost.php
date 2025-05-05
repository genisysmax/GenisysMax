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

use pocketmine\BedrockPlayer;
use pocketmine\block\utils\DyeColor;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\item\Dye;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\network\bedrock\protocol\OpenSignPacket;
use pocketmine\Player;
use pocketmine\tile\Sign as TileSign;
use pocketmine\tile\Tile;
use function floor;

class SignPost extends Transparent{

	protected $id = self::SIGN_POST;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getHardness() : float{
        return 1;
    }

    public function isSolid() : bool{
        return false;
    }

    public function getName() : string{
        return "Sign Post";
    }

    protected function recalculateBoundingBox() : ?AxisAlignedBB{
        return null;
    }

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool{
        if($face !== Vector3::SIDE_DOWN){

            if($face === Vector3::SIDE_UP){
                $this->meta = $player !== null ? (floor((($player->yaw + 180) * 16 / 360) + 0.5) & 0x0f) : 0;
                $this->getLevel()->setBlock($blockReplace, $this, true);
            }else{
                $this->meta = $face;
                $this->getLevel()->setBlock($blockReplace, Block::get(Block::WALL_SIGN, $this->meta), true);
            }

            Tile::createTile(Tile::SIGN, $this->getLevel(), TileSign::createNBT($this, $face, $item, $player));
            if($player instanceof BedrockPlayer){
                $pk = new OpenSignPacket();
                [$pk->x, $pk->y, $pk->z] = [$this->x, $this->y, $this->z];
                $pk->front = true;
                $player->sendDataPacket($pk);
            }
            return true;
        }

		return false;
	}

    public function onActivate(Item $item, Player $player = null): bool
    {
        $tile = $this->level->getTileAt($this->x, $this->y, $this->z);
        if (!($tile instanceof TileSign)) {
            $tile = Tile::createTile(Tile::SIGN, $this->getLevel(), TileSign::createNBT($this, null, $item, $player));
        }
        $color = $item instanceof Dye ? $item->getColorFromMeta() : null;
        if($color !== null){
            $ev = new SignChangeEvent($this, $player, $tile->getText());
            $ev->call();
            if(
                $color->toARGB() !== $tile->getTextColor()->toARGB() &&
                !$ev->isCancelled()
            ){
                $tile->setTextColor($color);
                $item->pop();
                return true;
            }
        }
        if ($player instanceof BedrockPlayer) {
            $pk = new OpenSignPacket();
            [$pk->x, $pk->y, $pk->z] = [$this->x, $this->y, $this->z];
            $pk->front = true;
            $player->sendDataPacket($pk);
        }
        return true;
    }

    public function onNearbyBlockChange() : void{
        if($this->getSide(Vector3::SIDE_DOWN)->getId() === self::AIR){
            $this->getLevel()->useBreakOn($this);
        }
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_AXE;
    }

    public function getVariantBitmask() : int{
        return 0;
    }

    public function getDropsForCompatibleTool(Item $item) : array{
        return [
            Item::get(Item::SIGN)
        ];
    }
}

