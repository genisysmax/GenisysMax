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
use pocketmine\event\Event;
use pocketmine\inventory\TransactionQueue;

/**
 * Called when an inventory transaction queue starts execution. 
 */

class InventoryTransactionEvent extends Event implements Cancellable{

	public static $handlerList = null;
	
	/** @var TransactionQueue */
	private $transactionQueue;
	
	/**
	 * @param TransactionQueue $ts
	 */
	public function __construct(TransactionQueue $transactionQueue){
		$this->transactionQueue = $transactionQueue;
	}

	/**
	 * @deprecated
	 * @return TransactionQueue
	 */
	public function getTransaction(){
		return $this->transactionQueue;
	}

	/**
	 * @return TransactionQueue
	 */
	public function getQueue(){
		return $this->transactionQueue;
	}
}

