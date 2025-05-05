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

class MoveActorDeltaPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::MOVE_ACTOR_DELTA_PACKET;

	public const FLAG_HAS_X = 0x01;
	public const FLAG_HAS_Y = 0x02;
	public const FLAG_HAS_Z = 0x04;
	public const FLAG_HAS_ROT_X = 0x08;
	public const FLAG_HAS_ROT_Y = 0x10;
	public const FLAG_HAS_ROT_Z = 0x20;

	/** @var int */
	public $actorRuntimeId;
	/** @var int */
	public $flags;
	/** @var float */
	public $xPos = 0.0;
	/** @var float */
	public $yPos = 0.0;
	/** @var float */
	public $zPos = 0.0;
	/** @var float */
	public $xRot = 0.0;
	/** @var float */
	public $yRot = 0.0;
	/** @var float */
	public $zRot = 0.0;

	protected function maybeReadCoord(int $flag) : float{
		if($this->flags & $flag){
			return $this->getLFloat();
		}
		return 0;
	}

	protected function maybeReadRotation(int $flag) : float{
		if($this->flags & $flag){
			return $this->getByteRotation();
		}
		return 0.0;
	}

	public function decodePayload(){
		$this->actorRuntimeId = $this->getActorRuntimeId();
		$this->flags = $this->getLShort();
		$this->xPos = $this->maybeReadCoord(self::FLAG_HAS_X);
		$this->yPos = $this->maybeReadCoord(self::FLAG_HAS_Y);
		$this->zPos = $this->maybeReadCoord(self::FLAG_HAS_Z);
		$this->xRot = $this->maybeReadRotation(self::FLAG_HAS_ROT_X);
		$this->yRot = $this->maybeReadRotation(self::FLAG_HAS_ROT_Y);
		$this->zRot = $this->maybeReadRotation(self::FLAG_HAS_ROT_Z);
	}

	protected function maybeWriteCoord(int $flag, float $val) : void{
		if($this->flags & $flag){
			$this->putLFloat($val);
		}
	}

	protected function maybeWriteRotation(int $flag, float $val) : void{
		if($this->flags & $flag){
			$this->putByteRotation($val);
		}
	}

	public function encodePayload(){
		$this->putActorRuntimeId($this->actorRuntimeId);
		$this->putLShort($this->flags);
		$this->maybeWriteCoord(self::FLAG_HAS_X, $this->xPos);
		$this->maybeWriteCoord(self::FLAG_HAS_Y, $this->yPos);
		$this->maybeWriteCoord(self::FLAG_HAS_Z, $this->zPos);
		$this->maybeWriteRotation(self::FLAG_HAS_ROT_X, $this->xRot);
		$this->maybeWriteRotation(self::FLAG_HAS_ROT_Y, $this->yRot);
		$this->maybeWriteRotation(self::FLAG_HAS_ROT_Z, $this->zRot);
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleMoveActorDelta($this);
	}
}


