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

class BlockLightUpdate extends LightUpdate{

	public function getLight(int $x, int $y, int $z) : int{
		return $this->subChunkHandler->currentSubChunk->getBlockLight($x & 0x0f, $y & 0x0f, $z & 0x0f);
	}

	public function setLight(int $x, int $y, int $z, int $level){
		$this->subChunkHandler->currentSubChunk->setBlockLight($x & 0x0f, $y & 0x0f, $z & 0x0f, $level);
	}
}

