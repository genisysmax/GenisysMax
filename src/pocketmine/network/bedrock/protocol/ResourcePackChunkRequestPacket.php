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

class ResourcePackChunkRequestPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::RESOURCE_PACK_CHUNK_REQUEST_PACKET;

	/** @var string */
	public $packId;
	/** @var int */
	public $chunkIndex;

	public function decodePayload(){
		$this->packId = $this->getString();
		$this->chunkIndex = $this->getLInt();
	}

	public function encodePayload(){
		$this->putString($this->packId);
		$this->putLInt($this->chunkIndex);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleResourcePackChunkRequest($this);
	}
}


