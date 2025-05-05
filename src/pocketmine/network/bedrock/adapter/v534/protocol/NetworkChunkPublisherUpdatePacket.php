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

#include <rules/DataPacket.h>


namespace pocketmine\network\bedrock\adapter\v534\protocol;

class NetworkChunkPublisherUpdatePacket extends \pocketmine\network\bedrock\protocol\NetworkChunkPublisherUpdatePacket{

	public function decodePayload(){
		$this->getSignedBlockPosition($this->x, $this->y, $this->z);
		$this->radius = $this->getUnsignedVarInt();
	}

	public function encodePayload(){
		$this->putSignedBlockPosition($this->x, $this->y, $this->z);
		$this->putUnsignedVarInt($this->radius);
	}
}

