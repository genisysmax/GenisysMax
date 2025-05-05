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

namespace pocketmine\network\bedrock\adapter\v630\protocol;

class LevelChunkPacket extends \pocketmine\network\bedrock\protocol\LevelChunkPacket {

	public function decodePayload(){
		$this->chunkX = $this->getVarInt();
		$this->chunkZ = $this->getVarInt();

		$subChunkCountButNotReally = $this->getUnsignedVarInt();
		if($subChunkCountButNotReally === self::CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT){
			$this->clientSubChunkRequestsEnabled = true;
			$this->subChunkCount = PHP_INT_MAX;
		}elseif($subChunkCountButNotReally === self::CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT){
			$this->clientSubChunkRequestsEnabled = true;
			$this->subChunkCount = $this->getLShort();
		}else{
			$this->clientSubChunkRequestsEnabled = false;
			$this->subChunkCount = $subChunkCountButNotReally;
		}

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

		if($this->clientSubChunkRequestsEnabled){
			if($this->subChunkCount === PHP_INT_MAX){
				$this->putUnsignedVarInt(self::CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT);
			}else{
				$this->putUnsignedVarInt(self::CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT);
				$this->putLShort($this->subChunkCount);
			}
		}else{
			$this->putUnsignedVarInt($this->subChunkCount);
		}

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


