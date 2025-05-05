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



namespace pocketmine\event\inventory;

use pocketmine\event\Cancellable;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\Player;

class InventoryClickEvent extends InventoryEvent implements Cancellable{
	public static $handlerList = null;

	/** @var Player */
	private $who;
	private $slot;
	/** @var Item */
	private $item;

	/**
	 * @param Inventory $inventory
	 * @param Player    $who
	 * @param int       $slot
	 * @param Item      $item
	 */
	public function __construct(Inventory $inventory, Player $who, int $slot, Item $item){
		$this->who = $who;
		$this->slot = $slot;
		$this->item = $item;
		parent::__construct($inventory);
	}

	/**
	 * @return Player
	 */
	public function getWhoClicked(): Player{
		return $this->who;
	}

	/**
	 * @return Player
	 */
	public function getPlayer(): Player{
		return $this->who;
	}

	/**
	 * @return int
	 */
	public function getSlot(): int{
		return $this->slot;
	}

	/**
	 * @return Item
	 */
	public function getItem(): Item{
		return $this->item;
	}
}

