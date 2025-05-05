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


namespace pocketmine\event\player\cheat;

use pocketmine\event\Cancellable;
use pocketmine\math\Vector3;
use pocketmine\Player;

/**
 * Called when a player attempts to perform movement cheats such as clipping through blocks.
 */
class PlayerIllegalMoveEvent extends PlayerCheatEvent implements Cancellable{
	public static $handlerList = null;

	/** @var Vector3 */
	private $attemptedPosition;

	/**
	 * @param Player  $player
	 * @param Vector3 $attemptedPosition
	 */
	public function __construct(Player $player, Vector3 $attemptedPosition){
		$this->attemptedPosition = $attemptedPosition;
		$this->player = $player;
	}

	/**
	 * Returns the position the player attempted to move to.
	 * @return Vector3
	 */
	public function getAttemptedPosition() : Vector3{
		return $this->attemptedPosition;
	}

}

