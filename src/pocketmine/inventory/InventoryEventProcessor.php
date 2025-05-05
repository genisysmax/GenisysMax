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

use pocketmine\item\Item;

/**
 * This interface can be used to listen for events on a specific Inventory.
 *
 * If you want to listen to changes on an inventory, create a class implementing this interface and implement its
 * methods, then register it onto the inventory or inventories that you want to receive events for.
 */
interface InventoryEventProcessor{

	/**
	 * Called prior to a slot in the given inventory changing. This is called by inventories that this listener is
	 * attached to.
	 *
	 * @return Item|null that should be used in place of $newItem, or null if the slot change should not proceed.
	 */
	public function onSlotChange(Inventory $inventory, int $slot, Item $oldItem, Item $newItem) : ?Item;
}


