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

class ActorPickRequestPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ACTOR_PICK_REQUEST_PACKET;

    public int $entityUniqueId;
    public int $hotbarSlot;
    public bool $addUserData;

    public function decodePayload(){
        $this->entityUniqueId = $this->getLLong();
        $this->hotbarSlot = $this->getByte();
        $this->addUserData = $this->getBool();
    }

    public function encodePayload(){
        $this->putLLong($this->entityUniqueId);
        $this->putByte($this->hotbarSlot);
        $this->putBool($this->addUserData);
    }

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleActorPickRequest($this);
	}
}


