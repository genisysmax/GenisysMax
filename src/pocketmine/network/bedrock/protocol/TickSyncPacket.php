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

use pocketmine\network\NetworkSession;

class TickSyncPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::TICK_SYNC_PACKET;

	public int $requestTimeStamp;
	public int $responseTimeStamp;

	public function decodePayload(){
		$this->requestTimeStamp = $this->getLLong();
		$this->responseTimeStamp = $this->getLLong();
	}

	public function encodePayload(){
		$this->putLLong($this->requestTimeStamp);
		$this->putLLong($this->requestTimeStamp);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleTickSync($this);
	}
}

