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

namespace pocketmine\network\bedrock\adapter\v422\protocol;

use pocketmine\math\Vector3;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\bedrock\adapter\v422\protocol\types\ActorMetadataProperties as ActorMetadataProperties422;
use pocketmine\network\bedrock\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\bedrock\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\bedrock\protocol\types\entity\EntityMetadataTypes;
use pocketmine\network\bedrock\protocol\types\skin\Skin;
use pocketmine\network\bedrock\protocol\types\skin\SkinAnimation;
use pocketmine\network\mcpe\NetworkNbtSerializer;
use UnexpectedValueException;
use function assert;
use function count;
use function is_int;

trait PacketTrait{

    /**
     * @return Skin
     */
    public function getSkin() : Skin{
        $skinId = $this->getString();
        $skinResourcePatch = $this->getString();
        $skinImage = $this->getImage();

        $animations = [];
        $count = $this->getLInt();
        if($count > 128){
            throw new UnexpectedValueException("Too many skin animations: $count");
        }
        for($i = 0; $i < $count; ++$i){
            $image = $this->getImage();
            $type = $this->getLInt();
            $frames = $this->getLFloat();
            $expressionType = $this->getLInt();
            $animations[] = new SkinAnimation($image, $type, $frames, $expressionType);
        }

        $capeImage = $this->getImage();
        $geometryData = $this->getString();
        $animationData = $this->getString();
        $isPremium = $this->getBool();
        $isPersona = $this->getBool();
        $isCapeOnClassic = $this->getBool();
        $capeId = $this->getString();
        $fullSkinId = $this->getString();
        $armSize = $this->getString();
        $skinColor = $this->getString();

        $personaPieces = [];
        $count = $this->getLInt();
        if($count > 128){
            throw new UnexpectedValueException("Too many persona pieces: $count");
        }
        for($i = 0; $i < $count; ++$i){
            $personaPieces[] = $this->getPersonaPiece();
        }

        $pieceTintColors = [];
        $count = $this->getLInt();
        if($count > 128){
            throw new UnexpectedValueException("Too many piece tint colors: $count");
        }
        for($i = 0; $i < $count; ++$i){
            $pieceTintColors[] = $this->getPieceTintColor();
        }

        return new Skin($skinId, "", $skinResourcePatch, $skinImage, $animations, $capeImage, $geometryData, $animationData, $isPremium, $isPersona, $isCapeOnClassic, $capeId, $fullSkinId, $armSize, $skinColor, $personaPieces, $pieceTintColors);
    }

    /**
     * @param Skin $skin
     */
    public function putSkin(Skin $skin) : void{
        $this->putString($skin->getSkinId());
        $this->putString($skin->getSkinResourcePatch());
        $this->putImage($skin->getSkinImage());

        $animations = $skin->getAnimations();
        $this->putLInt(count($animations));
        foreach($animations as $animation){
            $this->putImage($animation->getImage());
            $this->putLInt($animation->getType());
            $this->putLFloat($animation->getFrames());
            $this->putLInt($animation->getExpressionType());
        }

        $this->putImage($skin->getCapeImage());
        $this->putString($skin->getGeometryData());
        $this->putString($skin->getAnimationData());
        $this->putBool($skin->isPremium());
        $this->putBool($skin->isPersona());
        $this->putBool($skin->isCapeOnClassic());
        $this->putString($skin->getCapeId());
        $this->putString($skin->getFullSkinId());
        $this->putString($skin->getArmSize());
        $this->putString($skin->getSkinColor());

        $this->putLInt(count($skin->getPersonaPieces()));
        foreach($skin->getPersonaPieces() as $personaPiece){
            $this->putPersonaPiece($personaPiece);
        }

        $this->putLInt(count($skin->getPieceTintColors()));
        foreach($skin->getPieceTintColors() as $pieceTintColor){
            $this->putPieceTintColor($pieceTintColor);
        }
    }

	/**
	 * Decodes actor metadata from the stream.
	 *
	 * @param bool $types Whether to include metadata types along with values in the returned array
	 *
	 * @return array
	 */
	public function getActorMetadata(bool $types = true) : array{
		$count = $this->getUnsignedVarInt();
		if($count > 128){
			throw new UnexpectedValueException("Too many actor metadata: $count");
		}
		$data = [];
		for($i = 0; $i < $count; ++$i){
			$key = $this->getUnsignedVarInt();
			if($key >= ActorMetadataProperties422::AREA_EFFECT_CLOUD_RADIUS){
				++$key;
			}
			$type = $this->getUnsignedVarInt();
			$value = null;
			switch($type){
				case EntityMetadataTypes::BYTE:
					$value = $this->getByte();
					break;
				case EntityMetadataTypes::SHORT:
					$value = $this->getSignedLShort();
					break;
				case EntityMetadataTypes::INT:
					$value = $this->getVarInt();
					break;
				case EntityMetadataTypes::FLOAT:
					$value = $this->getLFloat();
					break;
				case EntityMetadataTypes::STRING:
					$value = $this->getString();
					break;
				case EntityMetadataTypes::NBT:
					$value = $this->getNbtCompoundRoot();
					break;
				case EntityMetadataTypes::POS:
                    $value = new Vector3();
                    $this->getSignedBlockPosition($value->x, $value->y, $value->z);
					break;
				case EntityMetadataTypes::LONG:
					$value = $this->getVarLong();
					break;
				case EntityMetadataTypes::VECTOR3F:
                    $value = $this->getVector3();
					break;
				default:
					throw new UnexpectedValueException("Invalid data type " . $type);
			}

			if($key === ActorMetadataProperties422::FLAGS){
				// add empty can dash flag
				$value = ($value >> EntityMetadataFlags::CAN_DASH << EntityMetadataFlags::LINGER)
					| ($value & ((1 << EntityMetadataFlags::CAN_DASH) - 1));
			}

			if($types){
				$data[$key] = [$type, $value];
			}else{
				$data[$key] = $value;
			}
		}

		return $data;
	}

	/**
	 * Writes actor metadata to the packet buffer.
	 *
	 * @param array $metadata
	 */
	public function putActorMetadata(array $metadata) : void{
		$this->putUnsignedVarInt(count($metadata));
		foreach($metadata as $key => $d){
			if($key >= EntityMetadataProperties::AREA_EFFECT_CLOUD_RADIUS){
				--$key;
			}
			$this->putUnsignedVarInt($key); //data key
			$this->putUnsignedVarInt($d[0]); //data type

			$value = $d[1];
			if($key === ActorMetadataProperties422::FLAGS){
				assert(is_int($value));

				// remove can dash flag
				$value = ($value >> (EntityMetadataFlags::CAN_DASH + 1) << EntityMetadataFlags::CAN_DASH)
					| ($value & ((1 << EntityMetadataFlags::CAN_DASH) - 1));
			}
			switch($d[0]){
				case EntityMetadataTypes::BYTE:
					$this->putByte($value);
					break;
				case EntityMetadataTypes::SHORT:
					$this->putLShort($value); //SIGNED short!
					break;
				case EntityMetadataTypes::INT:
					$this->putVarInt($value);
					break;
				case EntityMetadataTypes::FLOAT:
					$this->putLFloat($value);
					break;
				case EntityMetadataTypes::STRING:
					$this->putString($value);
					break;
				case EntityMetadataTypes::NBT:
					$this->put((new NetworkNbtSerializer())->write(new TreeRoot($value)));
					break;
				case EntityMetadataTypes::POS:
                    $v = $d[1];
                    if($v !== null){
                        $this->putSignedBlockPosition($v->x, $v->y, $v->z);
                    }else{
                        $this->putSignedBlockPosition(0, 0, 0);
                    }
					break;
				case EntityMetadataTypes::LONG:
					$this->putVarLong($value);
					break;
				case EntityMetadataTypes::VECTOR3F:
                    $this->putVector3Nullable($d[1]);
					break;
				default:
					throw new UnexpectedValueException("Invalid data type " . $d[0]);
			}
		}
	}
}

