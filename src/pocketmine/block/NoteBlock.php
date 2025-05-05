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
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\tile\NoteBlock as TileNoteBlock;
use pocketmine\tile\Tile;

class NoteBlock extends Solid{

	protected $id = self::NOTE_BLOCK;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getHardness() : float{
        return 0.8;
    }

    public function getToolType() : int{
        return BlockToolType::TYPE_AXE;
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
        $this->getLevel()->setBlock($blockReplace, $this, true, true);
        Tile::createTile(Tile::NOTE_BLOCK, $this->getLevel(), TileNoteBlock::createNBT($this, $face, $item, $player));
        return true;
    }

    public function onActivate(Item $item, Player $player = null) : bool{
        $tile = $this->level->getTile($this);
        if($tile instanceof TileNoteBlock){
            $tile->changePitch();

            return $tile->triggerNote();
        }

        return false;
    }

    public function getName() : string{
        return "Noteblock";
    }

    public function getFuelTime() : int{
        return 300;
    }
}


