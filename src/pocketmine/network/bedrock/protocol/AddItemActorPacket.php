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
use pocketmine\network\bedrock\protocol\types\inventory\ItemInstance;
use pocketmine\network\NetworkSession;

class AddItemActorPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ADD_ITEM_ACTOR_PACKET;

    public ?int $entityUniqueId = null; //TODO
    public int $entityRuntimeId;
    public ItemInstance $item;
    public Vector3 $position;
    public ?Vector3 $motion;
    /**
     * @var mixed[][]
     * @phpstan-var array<int, array{0: int, 1: mixed}>
     */
    public array $metadata = [];
    public bool $isFromFishing = false;

	public function decodePayload(){
		$this->entityUniqueId = $this->getActorUniqueId();
		$this->entityRuntimeId = $this->getActorRuntimeId();
		$this->item = $this->getItemInstance();
		$this->position = $this->getVector3();
		$this->motion = $this->getVector3();
		$this->metadata = $this->getActorMetadata();
		$this->isFromFishing = $this->getBool();
	}

	public function encodePayload(){
        $this->putActorUniqueId($this->entityUniqueId ?? $this->entityRuntimeId);
        $this->putActorRuntimeId($this->entityRuntimeId);
        $this->putItemInstance($this->item);
        $this->putVector3($this->position);
        $this->putVector3Nullable($this->motion);
        $this->putActorMetadata($this->metadata);
        $this->putBool($this->isFromFishing);
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAddItemActor($this);
	}
}


