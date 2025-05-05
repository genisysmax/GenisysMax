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

namespace pocketmine\network;

use pocketmine\Player;

interface NetworkChunkCache{

	/**
	 * Requests asynchronous preparation of the chunk at the given coordinates.
	 *
	 * @param Player $player
	 * @param int    $chunkX
	 * @param int    $chunkZ
	 */
	public function request(Player $player, int $chunkX, int $chunkZ) : void;

	/**
	 * @param Player $player
	 * @param int    $chunkX
	 * @param int    $chunkZ
	 */
	public function unregister(Player $player, int $chunkX, int $chunkZ) : void;

	/**
	 * Returns the number of bytes occupied by the cache data in this cache.
	 *
	 * @return int
	 */
	public function calculateCacheSize() : int;
}

