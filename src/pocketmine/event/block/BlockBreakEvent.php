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

namespace pocketmine\event\block;

use InvalidArgumentException;
use pocketmine\block\Block;
use pocketmine\event\Cancellable;
use pocketmine\item\Item;
use pocketmine\Player;

class BlockBreakEvent extends BlockEvent implements Cancellable{
	public static $handlerList = null;

	/** @var Player */
	protected $player;

	/** @var Item */
	protected $item;

	/** @var bool */
	protected $instaBreak = false;
	protected $blockDrops = [];
    protected $xpDrops = 0;

	/**
	 * @param Player $player
	 * @param Block  $block
	 * @param Item   $item
	 * @param bool   $instaBreak
	 * @param Item[] $drops
	 */
	public function __construct(Player $player, Block $block, Item $item, bool $instaBreak, array $drops, int $xpDrops){
		$this->block = $block;
		$this->item = $item;
		$this->player = $player;
		$this->instaBreak = (bool) $instaBreak;
		$this->blockDrops = $drops;
        $this->xpDrops = $xpDrops;
	}

	public function getPlayer(){
		return $this->player;
	}

	public function getItem(){
		return $this->item;
	}

	public function getInstaBreak(){
		return $this->instaBreak;
	}

	/**
	 * @return Item[]
	 */
	public function getDrops(){
		return $this->blockDrops;
	}

	/**
	 * @param Item[] $drops
	 */
	public function setDrops(array $drops){
		$this->blockDrops = $drops;
	}

	/**
	 * @param bool $instaBreak
	 */
	public function setInstaBreak($instaBreak){
		$this->instaBreak = (bool) $instaBreak;
	}

    /**
     * Returns how much XP will be dropped by breaking this block.
     */
    public function getXpDropAmount() : int{
        return $this->xpDrops;
    }

    /**
     * Sets how much XP will be dropped by breaking this block.
     */
    public function setXpDropAmount(int $amount) : void{
        if($amount < 0){
            throw new InvalidArgumentException("Amount must be at least zero");
        }
        $this->xpDrops = $amount;
    }
}

