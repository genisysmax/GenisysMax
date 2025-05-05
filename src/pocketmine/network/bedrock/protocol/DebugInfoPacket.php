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

class DebugInfoPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::DEBUG_INFO_PACKET;

	/** @var int */
	public $playerUniqueId;
	/** @var string */
	public $data;

	public function decodePayload(){
		$this->playerUniqueId = $this->getActorUniqueId();
		$this->data = $this->getString();
	}

	public function encodePayload(){
		$this->putActorUniqueId($this->playerUniqueId);
		$this->putString($this->data);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleDebugInfo($this);
	}
}

