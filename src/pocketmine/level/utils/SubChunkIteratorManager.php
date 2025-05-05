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

namespace pocketmine\level\utils;

use pocketmine\level\ChunkManager;
use pocketmine\level\format\Chunk;
use pocketmine\level\format\EmptySubChunk;
use pocketmine\level\format\SubChunkInterface;

class SubChunkIteratorManager{
	/** @var ChunkManager */
	public $level;

	/** @var Chunk|null */
	public $currentChunk;
	/** @var SubChunkInterface|null */
	public $currentSubChunk;

	/** @var int */
	protected $currentX;
	/** @var int */
	protected $currentY;
	/** @var int */
	protected $currentZ;
	/** @var bool */
	protected $allocateEmptySubs = true;

	public function __construct(ChunkManager $level, bool $allocateEmptySubs = true){
		$this->level = $level;
		$this->allocateEmptySubs = $allocateEmptySubs;
	}

	public function moveTo(int $x, int $y, int $z) : bool{
		if($this->currentChunk === null or $this->currentX !== ($x >> 4) or $this->currentZ !== ($z >> 4)){
			$this->currentX = $x >> 4;
			$this->currentZ = $z >> 4;
			$this->currentSubChunk = null;

			$this->currentChunk = $this->level->getChunk($this->currentX, $this->currentZ);
			if($this->currentChunk === null){
				return false;
			}
		}

		if($this->currentSubChunk === null or $this->currentY !== ($y >> 4)){
			$this->currentY = $y >> 4;

			$this->currentSubChunk = $this->currentChunk->getSubChunk($y >> 4, $this->allocateEmptySubs);
			if($this->currentSubChunk instanceof EmptySubChunk){
				return false;
			}
		}

		return true;
	}

}

