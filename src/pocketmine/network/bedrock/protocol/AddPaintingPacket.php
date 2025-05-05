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

use pocketmine\math\Vector3;
use pocketmine\network\NetworkSession;

class AddPaintingPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ADD_PAINTING_PACKET;

	public ?int $entityUniqueId = null;
	public int $entityRuntimeId;
	public Vector3 $position;
	public int $direction;
	public string $title;

	public function decodePayload(){
		$this->entityUniqueId = $this->getActorUniqueId();
		$this->entityRuntimeId = $this->getActorRuntimeId();
		$this->position = $this->getVector3();
		$this->title = $this->getString();
	}

	public function encodePayload(){
		$this->putActorUniqueId($this->entityUniqueId ?? $this->entityRuntimeId);
		$this->putActorRuntimeId($this->entityRuntimeId);
		$this->putVector3($this->position);
		$this->putString($this->title);
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAddPainting($this);
	}
}


