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


use InvalidArgumentException;
use pocketmine\network\NetworkSession;
use function count;

class PlayerFogPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_FOG_PACKET;

	/** @var string[] */
	public array $fogLayers;

	public function decodePayload(){
		$count = $this->getUnsignedVarInt();
		if($count > 128){
			throw new InvalidArgumentException("Too many fog layers: $count");
		}
		for($i = 0; $i < $count; ++$i){
			$this->fogLayers[] = $this->getString();
		}
	}

	public function encodePayload(){
		$this->putUnsignedVarInt(count($this->fogLayers));
		foreach($this->fogLayers as $fogLayer){
			$this->putString($fogLayer);
		}
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayerFog($this);
	}
}


