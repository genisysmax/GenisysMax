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

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\entity\Attribute;
use pocketmine\network\NetworkSession;
use function count;

class AddEntityPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ADD_ENTITY_PACKET;

	/** @var int|null */
	public $entityUniqueId = null; //TODO
	/** @var int */
	public $entityRuntimeId;
	public $type;
	public $x;
	public $y;
	public $z;
	public $speedX = 0.0;
	public $speedY = 0.0;
	public $speedZ = 0.0;
	public $yaw = 0.0;
	public $pitch = 0.0;
	/** @var Attribute[] */
	public $attributes = [];
	public $metadata = [];
	public $links = [];

	public function decodePayload(){
		$this->entityUniqueId = $this->getEntityUniqueId();
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->type = $this->getUnsignedVarInt();
		$this->getVector3f($this->x, $this->y, $this->z);
		$this->getVector3f($this->speedX, $this->speedY, $this->speedZ);
		$this->pitch = $this->getLFloat();
		$this->yaw = $this->getLFloat();

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

		$this->metadata = $this->getEntityMetadata();
		$linkCount = $this->getUnsignedVarInt();
		for($i = 0; $i < $linkCount; ++$i){
			$this->links[$i][0] = $this->getEntityUniqueId();
			$this->links[$i][1] = $this->getEntityUniqueId();
			$this->links[$i][2] = $this->getByte();
		}
	}

	public function encodePayload(){
		$this->putEntityUniqueId($this->entityUniqueId ?? $this->entityRuntimeId);
		$this->putEntityRuntimeId($this->entityRuntimeId);
		$this->putUnsignedVarInt($this->type);
		$this->putVector3f($this->x, $this->y, $this->z);
		$this->putVector3f($this->speedX, $this->speedY, $this->speedZ);
		$this->putLFloat($this->pitch);
		$this->putLFloat($this->yaw);

		$this->putUnsignedVarInt(count($this->attributes));
		foreach($this->attributes as $attribute){
			$this->putString($attribute->getName());
			$this->putLFloat($attribute->getMinValue());
			$this->putLFloat($attribute->getValue());
			$this->putLFloat($attribute->getMaxValue());
		}

		$this->putEntityMetadata($this->metadata);
		$this->putUnsignedVarInt(count($this->links));
		foreach($this->links as $link){
			$this->putEntityUniqueId($link[0]);
			$this->putEntityUniqueId($link[1]);
			$this->putByte($link[2]);
		}
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAddEntity($this);
	}

}


