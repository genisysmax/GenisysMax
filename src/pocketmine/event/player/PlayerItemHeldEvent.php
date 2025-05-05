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

namespace pocketmine\event\player;

use pocketmine\event\Cancellable;
use pocketmine\item\Item;
use pocketmine\Player;

class PlayerItemHeldEvent extends PlayerEvent implements Cancellable{
	public static $handlerList = null;

	/** @var Item */
	private $item;
	/** @var int */
	private $hotbarSlot;
	/** @var int */
	private $inventorySlot;

	public function __construct(Player $player, Item $item, int $inventorySlot, int $hotbarSlot){
		$this->player = $player;
		$this->item = $item;
		$this->inventorySlot = $inventorySlot;
		$this->hotbarSlot = $hotbarSlot;
	}

	/**
	 * Returns the hotbar slot the player is attempting to hold.
	 * @return int
	 */
	public function getSlot() : int{
		return $this->hotbarSlot;
	}

	/**
	 * @return int
	 */
	public function getInventorySlot() : int{
		return $this->inventorySlot;
	}

	public function getItem() : Item{
		return $this->item;
	}

}

