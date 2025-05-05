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

/**
 * ATTENTION! This is not an usual entity packet!
 * @see RemoveActorPacket
 */
class RemoveEntityPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::REMOVE_ENTITY_PACKET;

	/** @var int */
	public $entityNetId;

	public function decodePayload(){
		$this->entityNetId = $this->getEntityNetId();
	}

	public function encodePayload(){
		$this->putEntityNetId($this->entityNetId);
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleRemoveEntity($this);
	}
}

