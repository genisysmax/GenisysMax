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

use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\SimpleChunkManager;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class PopulationTask extends AsyncTask{

	public $state;
	public $levelId;
	public $chunk;

	public $chunk0;
	public $chunk1;
	public $chunk2;
	public $chunk3;
	//center chunk
	public $chunk5;
	public $chunk6;
	public $chunk7;
	public $chunk8;

	public function __construct(Level $level, Chunk $chunk){
		$this->state = true;
		$this->levelId = $level->getId();
		$this->chunk = $chunk->fastSerialize();

		foreach($level->getAdjacentChunks($chunk->getX(), $chunk->getZ()) as $i => $c){
			$this->{"chunk$i"} = $c !== null ? $c->fastSerialize() : null;
		}
	}

	public function onRun(){
		/** @var SimpleChunkManager $manager */
		$manager = $this->getFromThreadStore("generation.level{$this->levelId}.manager");
		/** @var Generator $generator */
		$generator = $this->getFromThreadStore("generation.level{$this->levelId}.generator");
		if($manager === null or $generator === null){
			$this->state = false;
			return;
		}

		/** @var Chunk[] $chunks */
		$chunks = [];

		$chunk = Chunk::fastDeserialize($this->chunk);

		for($i = 0; $i < 9; ++$i){
			if($i === 4){
				continue;
			}
			$xx = -1 + $i % 3;
			$zz = -1 + (int) ($i / 3);
			$ck = $this->{"chunk$i"};
			if($ck === null){
				$chunks[$i] = Chunk::getEmptyChunk($chunk->getX() + $xx, $chunk->getZ() + $zz);
			}else{
				$chunks[$i] = Chunk::fastDeserialize($ck);
			}
		}

		$manager->setChunk($chunk->getX(), $chunk->getZ(), $chunk);
		if(!$chunk->isGenerated()){
			$generator->generateChunk($chunk->getX(), $chunk->getZ());
			$chunk->setGenerated();
		}

		foreach($chunks as $c){
			if($c !== null){
				$manager->setChunk($c->getX(), $c->getZ(), $c);
				if(!$c->isGenerated()){
					$generator->generateChunk($c->getX(), $c->getZ());
					$c = $manager->getChunk($c->getX(), $c->getZ());
					$c->setGenerated();
				}
			}
		}

		$generator->populateChunk($chunk->getX(), $chunk->getZ());

		$chunk = $manager->getChunk($chunk->getX(), $chunk->getZ());
		$chunk->recalculateHeightMap();
		$chunk->populateSkyLight();
		$chunk->setLightPopulated();
		$chunk->setPopulated();
		$this->chunk = $chunk->fastSerialize();

		$manager->setChunk($chunk->getX(), $chunk->getZ(), null);

		foreach($chunks as $i => $c){
			if($c !== null){
				$c = $chunks[$i] = $manager->getChunk($c->getX(), $c->getZ());
				if(!$c->hasChanged()){
					$chunks[$i] = null;
				}
			}else{
				//This way non-changed chunks are not set
				$chunks[$i] = null;
			}
		}

		$manager->cleanChunks();

		for($i = 0; $i < 9; ++$i){
			if($i === 4){
				continue;
			}

			$this->{"chunk$i"} = $chunks[$i] !== null ? $chunks[$i]->fastSerialize() : null;
		}
	}

	public function onCompletion(Server $server){
		$level = $server->getLevel($this->levelId);
		if($level !== null){
			if($this->state === false){
				$level->registerGeneratorToWorker($this->workerId);
			}

			$chunk = Chunk::fastDeserialize($this->chunk);

			for($i = 0; $i < 9; ++$i){
				if($i === 4){
					continue;
				}
				$c = $this->{"chunk$i"};
				if($c !== null){
					$c = Chunk::fastDeserialize($c);
					$level->generateChunkCallback($c->getX(), $c->getZ(), $this->state ? $c : null);
				}
			}

			$level->generateChunkCallback($chunk->getX(), $chunk->getZ(), $this->state ? $chunk : null);
		}
	}
}

