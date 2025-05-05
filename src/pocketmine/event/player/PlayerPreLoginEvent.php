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
use pocketmine\Player;

/**
 * Called when the player logs in, before things have been set up
 */
class PlayerPreLoginEvent extends PlayerEvent implements Cancellable{
	public static $handlerList = null;

	/** @var string */
	protected $kickMessage;

	/**
	 * @param Player $player
	 * @param string $kickMessage
	 */
	public function __construct(Player $player, string $kickMessage){
		$this->player = $player;
		$this->kickMessage = $kickMessage;
	}

	/**
	 * @param string $kickMessage
	 */
	public function setKickMessage(string $kickMessage){
		$this->kickMessage = $kickMessage;
	}

	/**
	 * @return string
	 */
	public function getKickMessage() : string{
		return $this->kickMessage;
	}

}

