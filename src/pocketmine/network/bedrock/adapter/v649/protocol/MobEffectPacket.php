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

namespace pocketmine\network\bedrock\adapter\v649\protocol;

class MobEffectPacket extends \pocketmine\network\bedrock\protocol\MobEffectPacket {

	public function decodePayload(){
		$this->actorRuntimeId = $this->getActorRuntimeId();
		$this->eventId = $this->getByte();
		$this->effectId = $this->getVarInt();
		$this->amplifier = $this->getVarInt();
		$this->particles = $this->getBool();
		$this->duration = $this->getVarInt();
	}

	public function encodePayload(){
		$this->putActorRuntimeId($this->actorRuntimeId);
		$this->putByte($this->eventId);
		$this->putVarInt($this->effectId);
		$this->putVarInt($this->amplifier);
		$this->putBool($this->particles);
		$this->putVarInt($this->duration);
	}
}


