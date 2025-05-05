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
use pocketmine\tile\Beacon as TileBeacon;
use pocketmine\tile\Tile;

class Beacon extends Transparent
{

    protected $id = self::BEACON;

    public function __construct(int $meta = 0)
    {
        $this->meta = $meta;
    }

    public function getName() : string{
        return "Beacon";
    }

    public function getLightLevel() : int{
        return 15;
    }

    public function getHardness() : float{
        return 3;
    }

    public function getBreakTime(Item $item) : float{
        return 4.5;
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool
    {
        $this->getLevel()->setBlock($this, $this, true, true);

        Tile::createTile(Tile::BEACON, $this->getLevel(), TileBeacon::createNBT($this, $face, $item, $player));
        return true;
    }

    public function onActivate(Item $item, Player $player = null) : bool{
        if ($player instanceof Player) {
            $tile = $this->level->getTile($this);
            if (!$tile instanceof TileBeacon) {
                $tile = Tile::createTile(Tile::BEACON, $this->getLevel(), TileBeacon::createNBT($this, null, $item, $player));
            }
            if ($tile instanceof TileBeacon) {
                $top = $this->getSide(Vector3::SIDE_UP);
                if ($top->isTransparent() !== true) {
                    return true;
                }

                $player->addWindow($tile->getInventory());
            }
        }
        return true;
    }
}

