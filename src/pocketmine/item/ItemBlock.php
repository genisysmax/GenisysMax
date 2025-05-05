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

namespace pocketmine\item;

use pocketmine\block\Block;

/**
 * Class used for Items that can be Blocks
 */
class ItemBlock extends Item{
    /** @var int */
    protected int $blockId = 0;

    /**
     * @param int $meta usually 0-15 (placed blocks may only have meta values 0-15)
     */
    public function __construct(int $blockId, int $meta = 0, int $itemId = null){
        $this->blockId = $blockId;
        parent::__construct($itemId ?? $blockId, $meta, 1, $this->getBlock()->getName());
    }

    public function getBlock() : Block{
        return Block::get($this->blockId, $this->meta === -1 ? 0 : $this->meta & 0xf);
    }

    public function getVanillaName() : string{
        return $this->getBlock()->getName();
    }

    public function getFuelTime() : int{
        return $this->getBlock()->getFuelTime();
    }
}

