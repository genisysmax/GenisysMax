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

namespace pocketmine\network\bedrock\adapter\v390\protocol;

use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\bedrock\adapter\v422\protocol\types\ActorMetadataProperties as ActorMetadataProperties422;
use pocketmine\network\bedrock\protocol\types\entity\EntityLink;
use pocketmine\network\bedrock\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\bedrock\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\bedrock\protocol\types\entity\EntityMetadataTypes;
use pocketmine\network\bedrock\protocol\types\inventory\ItemInstance;
use pocketmine\network\bedrock\protocol\types\PotionTypeRecipe;
use pocketmine\network\mcpe\NetworkNbtSerializer;
use pocketmine\utils\BinaryDataException;
use UnexpectedValueException;

trait PacketTrait{

	public function getItemStackWithoutStackId() : Item{
		$id = $this->getVarInt();
		if($id <= 0){
			return Item::air();
		}

		$auxValue = $this->getVarInt();
		$data = $auxValue >> 8;
		if($data === 0x7fff){
			$data = -1;
		}
		$cnt = $auxValue & 0xff;

		$nbt = null;

		$nbtLen = $this->getLShort();
		if($nbtLen === 0xffff){
			$c = $this->getByte();
			if($c !== 1){
				throw new UnexpectedValueException("Unexpected NBT count $c");
			}

			$nbt = $this->getNbtCompoundRoot();
			if($nbt->hasTag(self::DAMAGE_TAG, IntTag::class)){ //a hack: 1.12+ meta format
				$data = $nbt->getInt(self::DAMAGE_TAG);
				$nbt->removeTag(self::DAMAGE_TAG);
			}

			if($nbt->hasTag(self::DAMAGE_TAG_CONFLICT_RESOLUTION)){
				$nbt->setTag(self::DAMAGE_TAG, $nbt->getTag(self::DAMAGE_TAG_CONFLICT_RESOLUTION));
				$nbt->removeTag(self::DAMAGE_TAG_CONFLICT_RESOLUTION);
			}

			if($nbt->hasTag("map_uuid", LongTag::class)){ // 1.1 compatibility hack
				$nbt->setString("map_uuid", (string) $nbt->getLong("map_uuid"));
			}
		}elseif($nbtLen !== 0){
			throw new UnexpectedValueException("Unexpected fake NBT length $nbtLen");
		}

		//TODO
		$canPlaceOn = $this->getVarInt();
		if($canPlaceOn > 128){
			throw new UnexpectedValueException("Too many canPlaceOn: $canPlaceOn");
		}elseif($canPlaceOn > 0){
			for($i = 0; $i < $canPlaceOn; ++$i){
				$this->getString();
			}
		}

		//TODO
		$canDestroy = $this->getVarInt();
		if($canDestroy > 128){
			throw new UnexpectedValueException("Too many canDestroy: $canDestroy");
		}elseif($canDestroy > 0){
			for($i = 0; $i < $canDestroy; ++$i){
				$this->getString();
			}
		}

		if($id === 513){ //SHIELD
			$this->getVarLong(); //"blocking tick" (ffs mojang)
		}

        $item = Item::get($id, $data, $cnt, $nbt);
        if ($data >= $item->getMaxDurability()) {
            $item->setDamage(0);
        }
        return $item;
	}

	public function putItemStackWithoutStackId(Item $item) : void{
        if($item->isNull()){
            $this->putVarInt(0);
            return;
        }

        $meta = $item->getDamage();
        if ($meta >= $item->getMaxDurability()) {
            $meta = 0;
        }

		$this->putVarInt($item->getId());
		$auxValue = $item->getCount();
		if(!$item instanceof Durable){
			$auxValue |= (($meta & 0x7fff) << 8);
		}
		$this->putVarInt($auxValue);

		if($item->hasCompoundTag() or ($item instanceof Durable and $meta !== 0)){
			$this->putLShort(0xffff);
			$this->putByte(1); //TODO: some kind of count field? always 1 as of 1.9.0

			$nbt = clone $item->getNamedTag();
			if($item instanceof Durable and $meta !== 0){
				if($nbt->hasTag(self::DAMAGE_TAG)){
					$nbt->setTag(self::DAMAGE_TAG_CONFLICT_RESOLUTION, $nbt->getTag(self::DAMAGE_TAG));
				}

				$nbt->setInt(self::DAMAGE_TAG, $meta & 0x7fff); //a hack: 1.12+ meta format
			}
			if($nbt->hasTag("map_uuid", StringTag::class)){ // 1.1 compatibility
				$nbt->setLong("map_uuid", (int) $nbt->getString("map_uuid"));
			}

			$this->put((new NetworkNbtSerializer())->write(new TreeRoot($nbt)));
		}else{
			$this->putLShort(0);
		}

		$this->putVarInt(0); //CanPlaceOn entry count (TODO)
		$this->putVarInt(0); //CanDestroy entry count (TODO)

		if($item->getId() === 513){ //SHIELD
			$this->putVarLong(0); //"blocking tick" (ffs mojang)
		}
	}

	public function putRecipeIngredient(Item $item) : void{
		if($item->getId() === 0){
			$this->putVarInt(0);
			return;
		}

		$this->putVarInt($item->getId());
		$this->putVarInt($item->hasAnyDamageValue() ? 0x7fff : $item->getDamage());
		$this->putVarInt($item->getCount());
	}

	public function getRecipeIngredient() : Item{
		$id = $this->getVarInt();
		if($id === 0){
			return Item::get(0, 0, 0);
		}

		$data = $this->getVarInt();
		if($data === 0x7fff){
			$data = -1;
		}

		$cnt = $this->getVarInt();
		return Item::get($id, $data, $cnt);
	}

    public function writePotionTypes(PotionTypeRecipe $entry):void{
        $this->putVarInt($entry->getInputPotionId());
        $this->putVarInt($entry->getIngredientItemId());
        $this->putVarInt($entry->getOutputPotionId());
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

            if($key === EntityMetadataProperties::FLAGS){
                // add empty can dash flag
                $value = ($value >> EntityMetadataFlags::FACING_TARGET_TO_RANGE_ATTACK << EntityMetadataFlags::FACING_TARGET_TO_RANGE_ATTACK)
                    | ($value & ((1 << EntityMetadataFlags::FACING_TARGET_TO_RANGE_ATTACK) - 1));
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
            if($key === EntityMetadataProperties::FLAGS){
                assert(is_int($value));

                // remove can dash flag
                $value = ($value >> (EntityMetadataFlags::FACING_TARGET_TO_RANGE_ATTACK + 1) << EntityMetadataFlags::FACING_TARGET_TO_RANGE_ATTACK)
                    | ($value & ((1 << EntityMetadataFlags::FACING_TARGET_TO_RANGE_ATTACK) - 1));
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

    /**
     * @return EntityLink
     *
     * @throws BinaryDataException
     */
    protected function getActorLink() : EntityLink{
        $link = new EntityLink();

        $link->fromActorUniqueId = $this->getActorUniqueId();
        $link->toActorUniqueId = $this->getActorUniqueId();
        $link->type = $this->getByte();
        $link->immediate = $this->getBool();

        return $link;
    }

    /**
     * @param EntityLink $link
     */
    protected function putActorLink(EntityLink $link) : void{
        $this->putActorUniqueId($link->fromActorUniqueId);
        $this->putActorUniqueId($link->toActorUniqueId);
        $this->putByte($link->type);
        $this->putBool($link->immediate);
    }

    /**
     * @param Item|ItemInstance $item
     */
    public function putItemInstance($item) : void{
        if($item instanceof ItemInstance){
            $item = $item->stack;
        }

        $this->putItemStackWithoutStackId($item);
    }

    public function getItemInstance() : ItemInstance{
        return ItemInstance::legacy($this->getItemStackWithoutStackId());
    }
}

