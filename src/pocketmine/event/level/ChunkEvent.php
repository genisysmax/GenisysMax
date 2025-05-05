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


namespace pocketmine\event\level;

use pocketmine\level\format\Chunk;
use pocketmine\level\Level;

/**
 * Chunk-related events
 */
abstract class ChunkEvent extends LevelEvent{
	/** @var Chunk */
	private $chunk;

	/**
	 * @param Level $level
	 * @param Chunk $chunk
	 */
	public function __construct(Level $level, Chunk $chunk){
		parent::__construct($level);
		$this->chunk = $chunk;
	}

	/**
	 * @return Chunk
	 */
	public function getChunk() : Chunk{
		return $this->chunk;
	}
}

