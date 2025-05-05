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

namespace pocketmine\level\generator;

use pocketmine\level\ChunkManager;
use pocketmine\level\format\Chunk;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;

class VoidGenerator extends Generator{
	/** @var ChunkManager */
	private $level;
	/** @var ?Chunk */
	private $chunk;

	public function getSettings() : array{
		return [];
	}

	public function getName() : string{
		return "void";
	}

	public function __construct(array $options = []){

	}

	public function init(ChunkManager $level, Random $random){
		$this->level = $level;
	}

	public function generateChunk(int $chunkX, int $chunkZ){
		if($this->chunk === null){
			$this->chunk = new Chunk($chunkX, $chunkZ);
			$this->chunk->setGenerated();
		}
		$chunk = clone $this->chunk;
		$chunk->setX($chunkX);
		$chunk->setZ($chunkZ);
		$this->level->setChunk($chunkX, $chunkZ, $chunk);
	}

	public function populateChunk(int $chunkX, int $chunkZ){

	}

	public function getSpawn() : Vector3{
		return new Vector3(0, 128, 0);
	}
}

