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

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>


use pocketmine\network\NetworkSession;

class MoveEntityPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::MOVE_ENTITY_PACKET;

	public $entityRuntimeId;
	public $x;
	public $y;
	public $z;
	public $yaw;
	public $headYaw;
	public $pitch;
	public $onGround = false;
	public $teleported = false;

	public function decodePayload(){
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->getVector3f($this->x, $this->y, $this->z);
		$this->pitch = $this->getByteRotation();
		$this->headYaw = $this->getByteRotation();
		$this->yaw = $this->getByteRotation();
		$this->onGround = $this->getBool();
		$this->teleported = $this->getBool();
	}

	public function encodePayload(){
		$this->putEntityRuntimeId($this->entityRuntimeId);
		$this->putVector3f($this->x, $this->y, $this->z);
		$this->putByteRotation($this->pitch);
		$this->putByteRotation($this->headYaw);
		$this->putByteRotation($this->yaw);
		$this->putBool($this->onGround);
		$this->putBool($this->teleported);
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleMoveEntity($this);
	}

}


