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

use pocketmine\event\TextContainer;
use pocketmine\Player;

/**
 * Called when a player joins the server, after sending all the spawn packets
 */
class PlayerJoinEvent extends PlayerEvent{
	public static $handlerList = null;

	/** @var string|TextContainer */
	protected $joinMessage;

	/**
	 * PlayerJoinEvent constructor.
	 *
	 * @param Player               $player
	 * @param TextContainer|string $joinMessage
	 */
	public function __construct(Player $player, $joinMessage){
		$this->player = $player;
		$this->joinMessage = $joinMessage;
	}

	/**
	 * @param string|TextContainer $joinMessage
	 */
	public function setJoinMessage($joinMessage){
		$this->joinMessage = $joinMessage;
	}

	/**
	 * @return string|TextContainer
	 */
	public function getJoinMessage(){
		return $this->joinMessage;
	}

}

