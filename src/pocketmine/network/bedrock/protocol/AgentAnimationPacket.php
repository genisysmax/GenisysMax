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

use pocketmine\network\NetworkSession;

class AgentAnimationPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::AGENT_ANIMATION_PACKET;

	public const TYPE_ARM_SWING = 0;
	public const TYPE_SHRUG = 1;

	private int $animationType;
	private int $actorRuntimeId;

	public function getAnimationType() : int{ return $this->animationType; }

	public function getActorRuntimeId() : int{ return $this->actorRuntimeId; }

	public function decodePayload(){
		$this->animationType = $this->getByte();
		$this->actorRuntimeId = $this->getActorRuntimeId();
	}

	public function encodePayload(){
		$this->putByte($this->animationType);
		$this->putActorRuntimeId($this->actorRuntimeId);
	}

    
	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAgentAnimation($this);
	}
}

