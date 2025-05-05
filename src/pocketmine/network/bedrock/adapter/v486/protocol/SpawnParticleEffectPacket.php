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

namespace pocketmine\network\bedrock\adapter\v486\protocol;

#include <rules/DataPacket.h>

class SpawnParticleEffectPacket extends \pocketmine\network\bedrock\protocol\SpawnParticleEffectPacket{

	public function decodePayload(){
		$this->dimensionId = $this->getByte();
		$this->actorUniqueId = $this->getActorUniqueId();
		$this->position = $this->getVector3();
		$this->particleName = $this->getString();
	}

	public function encodePayload(){
		$this->putByte($this->dimensionId);
		$this->putActorUniqueId($this->actorUniqueId);
		$this->putVector3($this->position);
		$this->putString($this->particleName);
	}
}

