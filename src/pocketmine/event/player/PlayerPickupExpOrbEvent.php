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



namespace pocketmine\event\player;

use pocketmine\event\Cancellable;
use pocketmine\Player;

class PlayerPickupExpOrbEvent extends PlayerEvent implements Cancellable{
	public static $handlerList = null;

	private $amount;

	/**
	 * PlayerPickupExpOrbEvent constructor.
	 *
	 * @param Player $p
	 * @param int $amount
	 */
	public function __construct(Player $p, int $amount = 0){
		$this->player = $p;
		$this->amount = $amount;
	}

	/**
	 * @return int
	 */
	public function getAmount() : int{
		return $this->amount;
	}

	/**
	 * @param int $amount
	 */
	public function setAmount(int $amount){
		$this->amount = $amount;
	}
}

