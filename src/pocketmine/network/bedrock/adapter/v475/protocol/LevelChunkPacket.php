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

namespace pocketmine\network\bedrock\adapter\v475\protocol;

#include <rules/DataPacket.h>


use function count;

class LevelChunkPacket extends \pocketmine\network\bedrock\protocol\LevelChunkPacket{
	public const NETWORK_ID = ProtocolInfo::LEVEL_CHUNK_PACKET;

	public function decodePayload(){
		$this->chunkX = $this->getVarInt();
		$this->chunkZ = $this->getVarInt();
		$this->subChunkCount = $this->getUnsignedVarInt();

		$this->cacheEnabled = $this->getBool();
		if($this->cacheEnabled){
			for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
				$this->blobIds[] = $this->getLLong();
			}
		}

		$this->data = $this->getString();
	}

	public function encodePayload(){
		$this->putVarInt($this->chunkX);
		$this->putVarInt($this->chunkZ);
		$this->putUnsignedVarInt($this->subChunkCount);

		$this->putBool($this->cacheEnabled);
		if($this->cacheEnabled){
			$this->putUnsignedVarInt(count($this->blobIds));
			foreach($this->blobIds as $blobId){
				$this->putLLong($blobId);
			}
		}

		$this->putString($this->data);
	}
}


