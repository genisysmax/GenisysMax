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

namespace pocketmine\network\bedrock\adapter\v527\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\bedrock\protocol\UpdateAbilitiesPacket;
use function count;

class AddPlayerPacket extends \pocketmine\network\bedrock\adapter\v554\protocol\AddPlayerPacket{
	use PacketTrait;

	public function decodePayload(){
		$this->uuid = $this->getUUID();
		$this->username = $this->getString();
		$this->getActorUniqueId();
		$this->actorRuntimeId = $this->getActorRuntimeId();
		$this->platformChatId = $this->getString();
		$this->position = $this->getVector3();
		$this->motion = $this->getVector3();
		$this->pitch = $this->getLFloat();
		$this->yaw = $this->getLFloat();
		$this->headYaw = $this->getLFloat();
		$this->item = $this->getItemInstance();
		$this->gameMode = $this->getVarInt();
		$this->metadata = $this->getActorMetadata();

		// adventure packet stuff
		$this->getUnsignedVarInt();
		$this->getUnsignedVarInt();
		$this->getUnsignedVarInt();
		$this->getUnsignedVarInt();
		$this->getUnsignedVarInt();

		$this->getLLong();

		$this->abilitiesPacket = new UpdateAbilitiesPacket();

		$linkCount = $this->getUnsignedVarInt();
		for($i = 0; $i < $linkCount; ++$i){
			$this->links[$i] = $this->getActorLink();
		}

		$this->deviceId = $this->getString();
		$this->deviceOS = $this->getLInt();
	}

	public function encodePayload(){
		$this->putUUID($this->uuid);
		$this->putString($this->username);
		$this->putActorUniqueId($this->actorRuntimeId);
		$this->putActorRuntimeId($this->actorRuntimeId);
		$this->putString($this->platformChatId);
		$this->putVector3($this->position);
		$this->putVector3Nullable($this->motion);
		$this->putLFloat($this->pitch);
		$this->putLFloat($this->yaw);
		$this->putLFloat($this->headYaw ?? $this->yaw);
		$this->putItemInstance($this->item);
		$this->putVarInt($this->gameMode);
		$this->putActorMetadata($this->metadata);

		// adventure packet stuff
		$this->putUnsignedVarInt(0);
		$this->putUnsignedVarInt(0);
		$this->putUnsignedVarInt(0);
		$this->putUnsignedVarInt(0);
		$this->putUnsignedVarInt(0);

		$this->putLLong(0);

		$this->putUnsignedVarInt(count($this->links));
		foreach($this->links as $link){
			$this->putActorLink($link);
		}

		$this->putString($this->deviceId);
		$this->putLInt($this->deviceOS);
	}

}


