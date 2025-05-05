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

namespace pocketmine\network\mcpe\chunk;

use pocketmine\level\format\Chunk;
use pocketmine\tile\Spawnable;
use pocketmine\utils\BinaryStream;
use function pack;

final class MCPEChunkSerializer{

	private function __construct(){
		//NOOP
	}

	public static function serialize(Chunk $chunk) : string{
		$result = new BinaryStream();
		$subChunkCount = $chunk->getSubChunkSendCount();
		$result->putByte($subChunkCount);
		for($y = 0; $y < $subChunkCount; ++$y){
			$subChunk = $chunk->getSubChunk($y);

			$result->putByte(0); //storage version
			$result->put($subChunk->getBlockIdArray());
			$result->put($subChunk->getBlockDataArray());
			$result->put($subChunk->getBlockSkyLightArray());
			$result->put($subChunk->getBlockLightArray());
		}
		$result->put(pack("v*", ...$chunk->getHeightMapArray()));
		$result->put($chunk->getBiomeIdArray());
		$result->putByte(0); //border block array count
		//Border block entry format: 1 byte (4 bits X, 4 bits Z). These are however useless since they crash the regular client.

		$result->putVarInt(count($chunk->getBlockExtraDataArray())); //WHY, Mojang, WHY
		foreach($chunk->getBlockExtraDataArray() as $key => $value){
			$result->putVarInt($key);
			$result->putLShort($value);
		}

		foreach($chunk->getTiles() as $tile){
			if($tile instanceof Spawnable){
				$result->put($tile->getSerializedSpawnCompound(false));
			}
		}

		return $result->buffer;
	}
}

