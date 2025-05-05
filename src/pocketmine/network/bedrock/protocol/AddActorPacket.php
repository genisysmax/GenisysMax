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

use pocketmine\entity\Attribute;
use pocketmine\math\Vector3;
use pocketmine\network\bedrock\protocol\types\entity\EntityLink;
use pocketmine\network\bedrock\protocol\types\entity\PropertySyncData;
use pocketmine\network\NetworkSession;
use function count;

class AddActorPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ADD_ACTOR_PACKET;

    public ?int $entityUniqueId = null; //TODO
    public int $entityRuntimeId;
    public string $type;
    public Vector3 $position;
    public ?Vector3 $motion = null;
    public float $pitch = 0.0;
    public float $yaw = 0.0;
    public float $headYaw = 0.0;
    public float $bodyYaw = 0.0;

	/** @var Attribute[] */
	public array $attributes = [];
	/** @var array */
	public array $metadata = [];
	/** @var PropertySyncData|null */
	public ?PropertySyncData $syncedProperties = null;
	/** @var EntityLink[] */
	public array $links = [];

	public function decodePayload(){
		$this->entityUniqueId = $this->getActorUniqueId();
		$this->entityRuntimeId = $this->getActorRuntimeId();
		$this->type = $this->getString();
		$this->position = $this->getVector3();
		$this->motion = $this->getVector3();
		$this->pitch = $this->getLFloat();
		$this->yaw = $this->getLFloat();
		$this->headYaw = $this->getLFloat();
		$this->bodyYaw = $this->getLFloat();

		$attrCount = $this->getUnsignedVarInt();
		for($i = 0; $i < $attrCount; ++$i){
			$name = $this->getString();
			$min = $this->getLFloat();
			$current = $this->getLFloat();
			$max = $this->getLFloat();
			$attr = Attribute::getAttributeByName($name);

			if($attr !== null){
				$attr->setMinValue($min);
				$attr->setMaxValue($max);
				$attr->setValue($current);
				$this->attributes[] = $attr;
			}else{
				throw new \UnexpectedValueException("Unknown attribute type \"$name\"");
			}
		}

		$this->metadata = $this->getActorMetadata();
        $this->syncedProperties = PropertySyncData::read($this);

		$linkCount = $this->getUnsignedVarInt();
		for($i = 0; $i < $linkCount; ++$i){
			$this->links[] = $this->getActorLink();
		}
	}

	public function encodePayload(){
		$this->putActorUniqueId($this->actorUniqueId ?? $this->entityRuntimeId);
		$this->putActorRuntimeId($this->entityRuntimeId);
		$this->putString($this->type);
		$this->putVector3($this->position);
		$this->putVector3Nullable($this->motion);
		$this->putLFloat($this->pitch);
		$this->putLFloat($this->yaw);
		$this->putLFloat($this->headYaw);
		$this->putLFloat($this->bodyYaw);

		$this->putUnsignedVarInt(count($this->attributes));
		foreach($this->attributes as $attribute){
			$this->putString($attribute->getName());
			$this->putLFloat($attribute->getMinValue());
			$this->putLFloat($attribute->getValue());
			$this->putLFloat($attribute->getMaxValue());
		}

		$this->putActorMetadata($this->metadata);
        ($this->syncedProperties ?? new PropertySyncData())->write($this);

		$this->putUnsignedVarInt(count($this->links));
		foreach($this->links as $link){
			$this->putActorLink($link);
		}
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAddActor($this);
	}
}


