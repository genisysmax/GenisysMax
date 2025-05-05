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

namespace pocketmine;

use pocketmine\permission\ServerOperator;

interface IPlayer extends ServerOperator{

	/**
	 * @return bool
	 */
	public function isOnline() : bool;

	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @return bool
	 */
	public function isBanned() : bool;

	/**
	 * @param bool $banned
	 */
	public function setBanned(bool $banned);

	/**
	 * @return bool
	 */
	public function isWhitelisted() : bool;

	/**
	 * @param bool $value
	 */
	public function setWhitelisted(bool $value);

	/**
	 * @return Player|null
	 */
	public function getPlayer();

	/**
	 * @return int|double
	 */
	public function getFirstPlayed();

	/**
	 * @return int|double
	 */
	public function getLastPlayed();

	/**
	 * @return bool
	 */
	public function hasPlayedBefore() : bool;

}


