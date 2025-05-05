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

/**
 * Inventory related events
 */
namespace pocketmine\event\inventory;

use pocketmine\entity\Human;
use pocketmine\event\Event;
use pocketmine\inventory\Inventory;

abstract class InventoryEvent extends Event{

	/** @var Inventory */
	protected $inventory;

	public function __construct(Inventory $inventory){
		$this->inventory = $inventory;
	}

	/**
	 * @return Inventory
	 */
	public function getInventory() : Inventory{
		return $this->inventory;
	}

	/**
	 * @return Human[]
	 */
	public function getViewers() : array{
		return $this->inventory->getViewers();
	}
}

