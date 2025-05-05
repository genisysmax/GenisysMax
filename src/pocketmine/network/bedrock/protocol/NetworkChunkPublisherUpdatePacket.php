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

namespace pocketmine\network\bedrock\protocol;

#include <rules/DataPacket.h>

use pocketmine\level\Level;
use pocketmine\network\NetworkSession;

class NetworkChunkPublisherUpdatePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::NETWORK_CHUNK_PUBLISHER_UPDATE_PACKET;

	/** @var int */
	public $x;
	/** @var int */
	public $y;
	/** @var int */
	public $z;
	/** @var int */
	public $radius;
	/** @var int[] */
	public $savedChunks = [];

	public function decodePayload(){
		$this->getSignedBlockPosition($this->x, $this->y, $this->z);
		$this->radius = $this->getUnsignedVarInt();

		for($i = 0, $this->savedChunks = [], $count = $this->getLInt(); $i < $count; $i++){
			$x = $this->getVarInt();
			$z = $this->getVarInt();

			$this->savedChunks[] = Level::chunkHash($x, $z);
		}
	}

	public function encodePayload(){
		$this->putSignedBlockPosition($this->x, $this->y, $this->z);
		$this->putUnsignedVarInt($this->radius);

		$this->putLInt(count($this->savedChunks));
		foreach($this->savedChunks as $chunkHash){
			Level::getXZ($chunkHash, $x, $z);

			$this->putVarInt($x);
			$this->putVarInt($z);
		}
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleNetworkChunkPublisherUpdate($this);
	}
}


