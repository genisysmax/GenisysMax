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

use pocketmine\block\Block;
use pocketmine\event\Cancellable;
use pocketmine\item\Item;
use pocketmine\Player;

/**
 * Called when a player places a block
 */
class BlockPlaceEvent extends BlockEvent implements Cancellable{
	public static $handlerList = null;

	/** @var Player */
	protected $player;

	/** @var Item */
	protected $item;


	protected $blockReplace;
	protected $blockAgainst;

	public function __construct(Player $player, Block $blockPlace, Block $blockReplace, Block $blockAgainst, Item $item){
		$this->block = $blockPlace;
		$this->blockReplace = $blockReplace;
		$this->blockAgainst = $blockAgainst;
		$this->item = $item;
		$this->player = $player;
	}

	public function getPlayer(){
		return $this->player;
	}

	/**
	 * Gets the item in hand
	 *
	 * @return mixed
	 */
	public function getItem(){
		return $this->item;
	}

	public function getBlockReplaced(){
		return $this->blockReplace;
	}

	public function getBlockAgainst(){
		return $this->blockAgainst;
	}
}

