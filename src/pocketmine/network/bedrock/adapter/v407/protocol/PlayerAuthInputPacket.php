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


use pocketmine\network\bedrock\protocol\types\PlayMode;

class PlayerAuthInputPacket extends \pocketmine\network\bedrock\protocol\PlayerAuthInputPacket{
	use PacketTrait;

	public function decodePayload(){
		$this->yaw = $this->getLFloat();
		$this->pitch = $this->getLFloat();
		$this->playerMovePosition = $this->getVector3();
		$this->motion = $this->getVector2();
		$this->headRotation = $this->getLFloat();
		$this->inputFlags = $this->getUnsignedVarLong();
		$this->inputMode = $this->getUnsignedVarInt();
		$this->playMode = $this->getUnsignedVarInt();
		if($this->playMode === PlayMode::VR){
			$this->vrGazeDirection = $this->getVector3();
		}
	}

	public function encodePayload(){
		$this->putLFloat($this->yaw);
		$this->putLFloat($this->pitch);
		$this->putVector3($this->playerMovePosition);
		$this->putVector2($this->motion);
		$this->putLFloat($this->headRotation);
		$this->putUnsignedVarLong($this->inputFlags);
		$this->putUnsignedVarInt($this->inputMode);
		$this->putUnsignedVarInt($this->playMode);
		if($this->playMode === PlayMode::VR){
			$this->putVector3($this->vrGazeDirection);
		}
	}
}

