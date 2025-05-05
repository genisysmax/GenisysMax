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
use pocketmine\network\bedrock\protocol\types\entity\EntityLink;
use pocketmine\network\bedrock\protocol\types\entity\PropertySyncData;
use pocketmine\network\bedrock\protocol\types\inventory\ItemInstance;
use pocketmine\network\NetworkSession;
use pocketmine\Player;
use pocketmine\utils\UUID;
use function count;

class AddPlayerPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ADD_PLAYER_PACKET;

	public UUID $uuid;
	public string $username;
	public int $actorRuntimeId;
	public string $platformChatId = "";
	public Vector3 $position;
	public ?Vector3 $motion;
	public float $pitch = 0.0;
	public float $yaw = 0.0;
	public ?float $headYaw = null; //TODO
	public ItemInstance $item;
	public int $gameMode = Player::SURVIVAL;

	/** @var array */
	public array $metadata = [];
	/** @var PropertySyncData|null */
	public ?PropertySyncData $syncedProperties = null;

	/** @var UpdateAbilitiesPacket|null */
	public ?UpdateAbilitiesPacket $abilitiesPacket = null;

	/** @var EntityLink[] */
	public array $links = [];

	public string $deviceId = ""; //TODO: fill player's device ID (???)
	public int $deviceOS = -1; //TODO: fill player's device OS

	public function decodePayload(){
		$this->uuid = $this->getUUID();
		$this->username = $this->getString();
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
		$this->syncedProperties = PropertySyncData::read($this);

		if($this->abilitiesPacket === null){
			$this->abilitiesPacket = new UpdateAbilitiesPacket($this->buffer, $this->offset);
		}else{
			$this->abilitiesPacket->setBuffer($this->buffer, $this->offset);
		}
		$this->abilitiesPacket->decodePayload();

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
		($this->syncedProperties ?? new PropertySyncData())->write($this);

		if($this->abilitiesPacket === null){
			$this->abilitiesPacket = new UpdateAbilitiesPacket();
			$this->abilitiesPacket->targetActorUniqueId = $this->actorRuntimeId;
			$this->abilitiesPacket->abilityLayers = [];
		}else{
			$this->abilitiesPacket->reset();
		}
		$this->abilitiesPacket->encodePayload();
		$this->put($this->abilitiesPacket->getBuffer());

		$this->putUnsignedVarInt(count($this->links));
		foreach($this->links as $link){
			$this->putActorLink($link);
		}

		$this->putString($this->deviceId);
		$this->putLInt($this->deviceOS);
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAddPlayer($this);
	}
}


