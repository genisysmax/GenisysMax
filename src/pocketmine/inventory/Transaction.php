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
use pocketmine\Player;

interface Transaction{

	//Transaction type constants
	public const TYPE_NORMAL = 0;
	public const TYPE_SWAP = 1;
	public const TYPE_HOTBAR = 2; //swap, but with hotbar resend
	public const TYPE_DROP_ITEM = 3;

	/**
	 * @return Inventory
	 */
	public function getInventory();

	/**
	 * @return int
	 */
	public function getSlot() : int;

	/**
	 * @return Item
	 */
	public function getTargetItem() : Item;

	/**
	 * @return float
	 */
	public function getCreationTime() : float;

	/**
	 * @param Player $source
	 * @return bool
	 */
	public function execute(Player $source): bool;

}

