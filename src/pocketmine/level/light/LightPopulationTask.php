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

namespace pocketmine\level\light;

use pocketmine\block\BlockFactory;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class LightPopulationTask extends AsyncTask{

	/** @var int */
	public $levelId;
	/** @var string */
	public $chunk;

	public function __construct(Level $level, Chunk $chunk){
		$this->levelId = $level->getId();
		$chunk->setLightPopulated(false);
		$this->chunk = $chunk->fastSerialize();
	}

	public function onRun(){
		if(!BlockFactory::isInit()){
            BlockFactory::init();
		}
		/** @var Chunk $chunk */
		$chunk = Chunk::fastDeserialize($this->chunk);

		$chunk->recalculateHeightMap();
		$chunk->populateSkyLight();
		$chunk->setLightPopulated();

		$this->chunk = $chunk->fastSerialize();
	}

	public function onCompletion(Server $server){
		$level = $server->getLevel($this->levelId);
		if($level !== null){
			/** @var Chunk $chunk */
			$chunk = Chunk::fastDeserialize($this->chunk);
			$level->generateChunkCallback($chunk->getX(), $chunk->getZ(), $chunk);
		}
	}
}


