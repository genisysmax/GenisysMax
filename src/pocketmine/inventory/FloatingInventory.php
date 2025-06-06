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



namespace pocketmine\inventory;

/**
 * The in-between inventory where items involved in transactions are stored temporarily
 */
class FloatingInventory extends BaseInventory{

	/**
	 * @param InventoryHolder $holder
	 * @param InventoryType   $inventoryType
	 */
	public function __construct(InventoryHolder $holder){
		parent::__construct($holder, InventoryType::get(InventoryType::PLAYER_FLOATING));
	}
}

