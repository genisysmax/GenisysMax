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


use pocketmine\math\Vector3;
use pocketmine\network\NetworkSession;

class RespawnPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::RESPAWN_PACKET;

	public const STATE_SEARCHING_FOR_SPAWN = 0;
	public const STATE_READY_TO_SPAWN = 1;
	public const STATE_CLIENT_READY_TO_SPAWN = 2;

	public Vector3 $position;
	public int $respawnState;
	public int $actorRuntimeId;

	public function decodePayload(){
		$this->position = $this->getVector3();
		$this->respawnState = $this->getByte();
		$this->actorRuntimeId = $this->getActorRuntimeId();
	}

	public function encodePayload(){
		$this->putVector3($this->position);
		$this->putByte($this->respawnState);
		$this->putActorRuntimeId($this->actorRuntimeId);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleRespawn($this);
	}
}


