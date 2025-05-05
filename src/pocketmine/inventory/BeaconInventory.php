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

namespace pocketmine\inventory;

use pocketmine\tile\Beacon;

class BeaconInventory extends ContainerInventory{

	/**
	 * BeaconInventory constructor.
	 *
	 * @param Beacon $tile
	 */
	public function __construct(Beacon $tile){
		parent::__construct($tile, InventoryType::get(InventoryType::BEACON));
	}

	/**
	 * @return InventoryHolder
	 */
	public function getHolder(){
		return $this->holder;
	}
}

