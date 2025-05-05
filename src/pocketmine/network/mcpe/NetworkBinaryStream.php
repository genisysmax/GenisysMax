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

namespace pocketmine\network\mcpe;

#include <rules/DataPacket.h>

use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\types\CommandOriginData;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\UUID;
use UnexpectedValueException;
use function count;
use function strlen;

class NetworkBinaryStream extends BinaryStream{

	public function getString() : string{
		return $this->get($this->getUnsignedVarInt());
	}

	public function putString(string $v) : void{
		$this->putUnsignedVarInt(strlen($v));
		$this->put($v);
	}

	/**
	 * Writes data in JSON-encoded format.
	 *
	 * @param mixed $data
	 */
	public function putJson($data) : void{
		$this->putString(json_encode($data));
	}

	/**
	 * Reads a JSON-encoded data.
	 *
	 * @return mixed|null
	 */
	public function getJson(){
		return json_decode($this->getString(), true);
	}

	public function getUUID() : UUID{
		//This is actually two little-endian longs: UUID Most followed by UUID Least
		$part1 = $this->getLInt();
		$part0 = $this->getLInt();
		$part3 = $this->getLInt();
		$part2 = $this->getLInt();

		return new UUID($part0, $part1, $part2, $part3);
	}

	public function putUUID(UUID $uuid) : void{
		$this->putLInt($uuid->getPart(1));
		$this->putLInt($uuid->getPart(0));
		$this->putLInt($uuid->getPart(3));
		$this->putLInt($uuid->getPart(2));
	}

	public function getSlot() : Item
    {
        $id = $this->getVarInt();
        if ($id <= 0) {
            return Item::get(0, 0, 0);
        }

        $auxValue = $this->getVarInt();
        $data = $auxValue >> 8;
        if ($data === 0x7fff) {
            $data = -1;
        }
        $cnt = $auxValue & 0xff;

        $nbtLen = $this->getLShort();
        $nbt = $this->get($nbtLen);

        //TODO
        $canPlaceOn = $this->getVarInt();
        if ($canPlaceOn > 128) {
            throw new \UnexpectedValueException("Too many canPlaceOn: $canPlaceOn");
        } elseif ($canPlaceOn > 0) {
            for ($i = 0; $i < $canPlaceOn; ++$i) {
                $this->getString();
            }
        }

        //TODO
        $canDestroy = $this->getVarInt();
        if ($canDestroy > 128) {
            throw new \UnexpectedValueException("Too many canDestroy: $canDestroy");
        } elseif ($canDestroy > 0) {
            for ($i = 0; $i < $canDestroy; ++$i) {
                $this->getString();
            }
        }

        $item = Item::get($id, $data, $cnt, $nbt);
        if ($data >= $item->getMaxDurability()) {
            $item->setDamage(0);
        }
        return $item;
    }


	public function putSlot(Item $item) : void
    {
        if ($item->isNull()) {
            $this->putVarInt(0);
            return;
        }

        $meta = $item->getDamage();
        if ($meta >= $item->getMaxDurability()) {
            $meta = 0;
        }

        $this->putVarInt($item->getId());
        $auxValue = (($meta & 0x7fff) << 8) | $item->getCount();
        $this->putVarInt($auxValue);

        $nbt = $item->getCompoundTag();
        $this->putLShort(strlen($nbt));
        $this->put($nbt);

        $this->putVarInt(0); //CanPlaceOn entry count (TODO)
        $this->putVarInt(0); //CanDestroy entry count (TODO)
    }

	/**
	 * Decodes entity metadata from the stream.
	 *
	 * @param bool $types Whether to include metadata types along with values in the returned array
	 *
	 * @return array
	 */
	public function getEntityMetadata(bool $types = true) : array{
		$count = $this->getUnsignedVarInt();
		if($count > 128){
			throw new \UnexpectedValueException("Too many entity metadata: $count");
		}
		$data = [];
		for($i = 0; $i < $count; ++$i){
			$key = $this->getUnsignedVarInt();
			$type = $this->getUnsignedVarInt();
			$value = null;
			switch($type){
				case Entity::DATA_TYPE_BYTE:
					$value = $this->getByte();
					break;
				case Entity::DATA_TYPE_SHORT:
					$value = $this->getSignedLShort();
					break;
				case Entity::DATA_TYPE_INT:
					$value = $this->getVarInt();
					break;
				case Entity::DATA_TYPE_FLOAT:
					$value = $this->getLFloat();
					break;
				case Entity::DATA_TYPE_STRING:
					$value = $this->getString();
					break;
				case Entity::DATA_TYPE_COMPOUND_TAG:
					$value = $this->getSlot();
					break;
				case Entity::DATA_TYPE_POS:
                    $value = new Vector3();
                    $this->getSignedBlockPosition($value->x, $value->y, $value->z);
					break;
				case Entity::DATA_TYPE_LONG:
					$value = $this->getVarLong();
					break;
				case Entity::DATA_TYPE_VECTOR3F:
                    $value = $this->getVector3();
					break;
				default:
					throw new \UnexpectedValueException("Invalid data type " . $type);
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
	 * Writes entity metadata to the packet buffer.
	 *
	 * @param array $metadata
	 */
	public function putEntityMetadata(array $metadata) : void{
		$this->putUnsignedVarInt(count($metadata));
		foreach($metadata as $key => $d){
			$this->putUnsignedVarInt($key); //data key
			$this->putUnsignedVarInt($d[0]); //data type
			switch($d[0]){
				case Entity::DATA_TYPE_BYTE:
					$this->putByte($d[1]);
					break;
				case Entity::DATA_TYPE_SHORT:
					$this->putLShort($d[1]); //SIGNED short!
					break;
				case Entity::DATA_TYPE_INT:
					$this->putVarInt($d[1]);
					break;
				case Entity::DATA_TYPE_FLOAT:
					$this->putLFloat($d[1]);
					break;
				case Entity::DATA_TYPE_STRING:
					$this->putString($d[1]);
					break;
				case Entity::DATA_TYPE_COMPOUND_TAG:
					$this->putSlot($d[1]);
					break;
				case Entity::DATA_TYPE_POS:
                    $v = $d[1];
                    if($v !== null){
                        $this->putSignedBlockPosition($v->x, $v->y, $v->z);
                    }else{
                        $this->putSignedBlockPosition(0, 0, 0);
                    }
					break;
				case Entity::DATA_TYPE_LONG:
					$this->putVarLong($d[1]);
					break;
				case Entity::DATA_TYPE_VECTOR3F:
                    $this->putVector3Nullable($d[1]);
					break;
				default:
					throw new \UnexpectedValueException("Invalid data type " . $d[0]);
			}
		}
	}

	/**
	 * Reads a list of Attributes from the stream.
	 * @return Attribute[]
	 *
	 * @throws \UnexpectedValueException if reading an attribute with an unrecognized name
	 */
	public function getAttributeList() : array{
		$list = [];
		$count = $this->getUnsignedVarInt();
		if($count > 128){
			throw new \UnexpectedValueException("Too many attributes: $count");
		}

		for($i = 0; $i < $count; ++$i){
			$min = $this->getLFloat();
			$max = $this->getLFloat();
			$current = $this->getLFloat();
			$default = $this->getLFloat();
			$id = $this->getString();

			$attr = Attribute::getAttributeByName($id);
			if($attr !== null){
				$attr->setMinValue($min);
				$attr->setMaxValue($max);
				$attr->setValue($current);
				$attr->setDefaultValue($default);

				$list[] = $attr;
			}else{
				throw new \UnexpectedValueException("Unknown attribute type \"$id\"");
			}
		}

		return $list;
	}

	/**
	 * Writes a list of Attributes to the packet buffer using the standard format.
	 *
	 * @param Attribute ...$attributes
	 */
	public function putAttributeList(Attribute ...$attributes) : void{
		$this->putUnsignedVarInt(count($attributes));
		foreach($attributes as $attribute){
			$this->putLFloat($attribute->getMinValue());
			$this->putLFloat($attribute->getMaxValue());
			$this->putLFloat($attribute->getValue());
			$this->putLFloat($attribute->getDefaultValue());
			$this->putString($attribute->getName());
		}
	}

	/**
	 * Reads and returns an EntityUniqueID
	 * @return int
	 */
	public function getEntityUniqueId() : int{
		return $this->getVarLong();
	}

	/**
	 * Writes an EntityUniqueID
	 *
	 * @param int $eid
	 */
	public function putEntityUniqueId(int $eid) : void{
		$this->putVarLong($eid);
	}

	/**
	 * Reads and returns an EntityRuntimeID
	 * @return int
	 */
	public function getEntityRuntimeId() : int{
		return $this->getUnsignedVarLong();
	}

	/**
	 * Writes an EntityUniqueID
	 *
	 * @param int $eid
	 */
	public function putEntityRuntimeId(int $eid) : void{
		$this->putUnsignedVarLong($eid);
	}

	/**
	 * Reads an block position with unsigned Y coordinate.
	 *
	 * @param int &$x
	 * @param int &$y
	 * @param int &$z
	 */
	public function getBlockPosition(&$x, &$y, &$z) : void{
		$x = $this->getVarInt();
		$y = $this->getUnsignedVarInt();
		$z = $this->getVarInt();
	}

	/**
	 * Writes a block position with unsigned Y coordinate.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 */
	public function putBlockPosition(int $x, int $y, int $z) : void{
		$this->putVarInt($x);
		$this->putUnsignedVarInt($y);
		$this->putVarInt($z);
	}

	/**
	 * Reads a block position with a signed Y coordinate.
	 *
	 * @param int &$x
	 * @param int &$y
	 * @param int &$z
	 */
	public function getSignedBlockPosition(&$x, &$y, &$z) : void{
		$x = $this->getVarInt();
		$y = $this->getVarInt();
		$z = $this->getVarInt();
	}

	/**
	 * Writes a block position with a signed Y coordinate.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 */
	public function putSignedBlockPosition(int $x, int $y, int $z) : void{
		$this->putVarInt($x);
		$this->putVarInt($y);
		$this->putVarInt($z);
	}

	/**
	 * Reads a floating-point Vector3 object with coordinates rounded to 4 decimal places.
	 *
	 * @return Vector3
	 */
	public function getVector3() : Vector3{
		return new Vector3(
			$this->getLFloat(),
			$this->getLFloat(),
			$this->getLFloat()
		);
	}

	/**
	 * Writes a floating-point Vector3 object, or 3x zero if null is given.
	 *
	 * Note: ONLY use this where it is reasonable to allow not specifying the vector.
	 * For all other purposes, use the non-nullable version.
	 *
	 * @see NetworkBinaryStream::putVector3()
	 *
	 * @param Vector3|null $vector
	 */
	public function putVector3Nullable(?Vector3 $vector) : void{
		if($vector){
			$this->putVector3($vector);
		}else{
			$this->putLFloat(0.0);
			$this->putLFloat(0.0);
			$this->putLFloat(0.0);
		}
	}

	/**
	 * Writes a floating-point Vector3 object
	 *
	 * @param Vector3 $vector
	 */
	public function putVector3(Vector3 $vector) : void{
		$this->putLFloat($vector->x);
		$this->putLFloat($vector->y);
		$this->putLFloat($vector->z);
	}

	/**
	 * Writes a floating-point Vector3
	 *
	 * @param float $x
	 * @param float $y
	 * @param float $z
	 */
	public function putVector3f(float $x, float $y, float $z) : void{
		$this->putLFloat($x);
		$this->putLFloat($y);
		$this->putLFloat($z);
	}

	/**
	 * Reads a floating-point Vector3 with coordinates rounded to 4 decimal places.
	 *
	 * @param float &$x
	 * @param float &$y
	 * @param float &$z
	 */
	public function getVector3f(?float &$x, ?float &$y, ?float &$z) : void{
		$x = $this->getLFloat();
		$y = $this->getLFloat();
		$z = $this->getLFloat();
	}

	public function getByteRotation() : float{
		return (float) ($this->getByte() * (360 / 256));
	}

	public function putByteRotation(float $rotation) : void{
		$this->putByte((int) ($rotation / (360 / 256)));
	}

	/**
	 * Reads gamerules
	 * TODO: implement this properly
	 *
	 * @return array, members are in the structure [name => [type, value]]
	 */
	public function getGameRules() : array{
		$count = $this->getUnsignedVarInt();
		$rules = [];
		for($i = 0; $i < $count; ++$i){
			$name = $this->getString();
            $type = $this->getUnsignedVarInt();
			$value = null;
			switch($type){
				case 1:
					$value = $this->getBool();
					break;
				case 2:
					$value = $this->getUnsignedVarInt();
					break;
				case 3:
					$value = $this->getLFloat();
					break;
			}

			$rules[$name] = [$type, $value];
		}

		return $rules;
	}

	/**
	 * Writes a gamerule array, members should be in the structure [name => [type, value]]
	 * TODO: implement this properly
	 *
	 * @param array $rules
	 */
	public function putGameRules(array $rules) : void{
		$this->putUnsignedVarInt(count($rules));
		foreach($rules as $name => $rule){
			$this->putString($name);
            $this->putUnsignedVarInt($rule[0]);
			switch($rule[0]){
				case 1:
					$this->putBool($rule[1]);
					break;
				case 2:
					$this->putUnsignedVarInt($rule[1]);
					break;
				case 3:
					$this->putLFloat($rule[1]);
					break;
			}
		}
	}

	public function getNbtRoot() : TreeRoot{
		try{
			return (new NetworkNbtSerializer())->read($this->buffer, $this->offset, 512);
		}catch(NbtDataException $e){
			throw new UnexpectedValueException("Failed decoding NBT root: " . $e->getMessage(), 0, $e);
		}
	}

	public function getNbtCompoundRoot() : CompoundTag{
		try{
			return $this->getNbtRoot()->mustGetCompoundTag();
		}catch(NbtDataException $e){
			throw new UnexpectedValueException("Expected TAG_Compound NBT root: " . $e->getMessage(), 0, $e);
		}
	}

    /**
     * @phpstan-template T
     * @phpstan-param \Closure() : T $reader
     * @phpstan-return T|null
     */
    public function readOptional(\Closure $reader) : mixed{
        if($this->getBool()){
            return $reader();
        }
        return null;
    }

    /**
     * @phpstan-template T
     * @phpstan-param T|null $value
     * @phpstan-param \Closure(T) : void $writer
     */
    public function writeOptional(mixed $value, \Closure $writer) : void{
        if($value !== null){
            $this->putBool(true);
            $writer($value);
        }else{
            $this->putBool(false);
        }
    }
}


