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

use pocketmine\level\Position;
use pocketmine\Player;

/**
 * Called when a player is respawned (or first time spawned)
 */
class PlayerRespawnEvent extends PlayerEvent{
	public static $handlerList = null;

	/** @var Position */
	protected $position;

	/**
	 * @param Player   $player
	 * @param Position $position
	 */
	public function __construct(Player $player, Position $position){
		$this->player = $player;
		$this->position = $position;
	}

	/**
	 * @return Position
	 */
	public function getRespawnPosition() : Position{
		return $this->position;
	}

	/**
	 * @param Position $position
	 */
	public function setRespawnPosition(Position $position){
		$this->position = $position;
	}
}

