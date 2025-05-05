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

class EntityEventPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ENTITY_EVENT_PACKET;

    public const ARM_SWING = 1;
	public const HURT_ANIMATION = 2;
	public const DEATH_ANIMATION = 3;

	public const TAME_FAIL = 6;
	public const TAME_SUCCESS = 7;
	public const SHAKE_WET = 8;
	public const USE_ITEM = 9;
	public const EAT_GRASS_ANIMATION = 10;
	public const FISH_HOOK_BUBBLE = 11;
	public const FISH_HOOK_POSITION = 12;
	public const FISH_HOOK_HOOK = 13;
	public const FISH_HOOK_TEASE = 14;
	public const SQUID_INK_CLOUD = 15;
	public const AMBIENT_SOUND = 16;

	public const RESPAWN = 18;

	public const ARROW_SHAKE = 39;

	public const EATING_ITEM = 57;

	public const CONSUME_TOTEM = 65;

	//TODO: add more events

	public $entityRuntimeId;
	public $event;
	public $data = 0;

	public function decodePayload(){
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->event = $this->getByte();
		$this->data = $this->getVarInt();
	}

	public function encodePayload(){
		$this->putEntityRuntimeId($this->entityRuntimeId);
		$this->putByte($this->event);
		$this->putVarInt($this->data);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleEntityEvent($this);
	}

}


