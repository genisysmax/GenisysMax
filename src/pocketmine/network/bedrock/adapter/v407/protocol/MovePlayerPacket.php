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

namespace pocketmine\network\bedrock\adapter\v407\protocol;

#include <rules/DataPacket.h>


class MovePlayerPacket extends \pocketmine\network\bedrock\protocol\MovePlayerPacket{
	use PacketTrait;

	public function decodePayload(){
		$this->actorRuntimeId = $this->getActorRuntimeId();
		$this->position = $this->getVector3();
		$this->pitch = $this->getLFloat();
		$this->yaw = $this->getLFloat();
		$this->headYaw = $this->getLFloat();
		$this->mode = $this->getByte();
		$this->onGround = $this->getBool();
		$this->ridingEid = $this->getActorRuntimeId();
		if($this->mode === self::MODE_TELEPORT){
			$this->teleportCause = $this->getLInt();
			$this->teleportItem = $this->getLInt();
		}
	}

	public function encodePayload(){
		$this->putActorRuntimeId($this->actorRuntimeId);
		$this->putVector3($this->position);
		$this->putLFloat($this->pitch);
		$this->putLFloat($this->yaw);
		$this->putLFloat($this->headYaw); //TODO
		$this->putByte($this->mode);
		$this->putBool($this->onGround);
		$this->putActorRuntimeId($this->ridingEid);
		if($this->mode === self::MODE_TELEPORT){
			$this->putLInt($this->teleportCause);
			$this->putLInt($this->teleportItem);
		}
	}
}

