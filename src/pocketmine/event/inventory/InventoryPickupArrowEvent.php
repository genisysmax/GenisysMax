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

namespace pocketmine\event\inventory;

use pocketmine\entity\projectile\Arrow;
use pocketmine\event\Cancellable;
use pocketmine\inventory\Inventory;

class InventoryPickupArrowEvent extends InventoryEvent implements Cancellable{
	public static $handlerList = null;

	/** @var Arrow */
	private $arrow;

	/**
	 * @param Inventory $inventory
	 * @param Arrow     $arrow
	 */
	public function __construct(Inventory $inventory, Arrow $arrow){
		$this->arrow = $arrow;
		parent::__construct($inventory);
	}

	/**
	 * @return Arrow
	 */
	public function getArrow() : Arrow{
		return $this->arrow;
	}

}

