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

namespace pocketmine\entity;

use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use function assert;
use function is_float;
use function is_int;
use function is_string;

class DataPropertyManager{

	/**
	 * @var mixed[][]
	 * @phpstan-var array<int, array{0: int, 1: mixed}>
	 */
	private array $properties = [];

	/**
	 * @var mixed[][]
	 * @phpstan-var array<int, array{0: int, 1: mixed}>
	 */
	private array $dirtyProperties = [];

	public function __construct(){

	}

	public function getByte(int $key) : ?int{
		$value = $this->getPropertyValue($key, Entity::DATA_TYPE_BYTE);
		assert(is_int($value) or $value === null);
		return $value;
	}

	public function setByte(int $key, int $value, bool $force = false) : void{
		$this->setPropertyValue($key, Entity::DATA_TYPE_BYTE, $value, $force);
	}

	public function getShort(int $key) : ?int{
		$value = $this->getPropertyValue($key, Entity::DATA_TYPE_SHORT);
		assert(is_int($value) or $value === null);
		return $value;
	}

	public function setShort(int $key, int $value, bool $force = false) : void{
		$this->setPropertyValue($key, Entity::DATA_TYPE_SHORT, $value, $force);
	}

	public function getInt(int $key) : ?int{
		$value = $this->getPropertyValue($key, Entity::DATA_TYPE_INT);
		assert(is_int($value) or $value === null);
		return $value;
	}

	public function setInt(int $key, int $value, bool $force = false) : void{
		$this->setPropertyValue($key, Entity::DATA_TYPE_INT, $value, $force);
	}

	public function getFloat(int $key) : ?float{
		$value = $this->getPropertyValue($key, Entity::DATA_TYPE_FLOAT);
		assert(is_float($value) or $value === null);
		return $value;
	}

	public function setFloat(int $key, float $value, bool $force = false) : void{
		$this->setPropertyValue($key, Entity::DATA_TYPE_FLOAT, $value, $force);
	}

	public function getString(int $key) : ?string{
		$value = $this->getPropertyValue($key, Entity::DATA_TYPE_STRING);
		assert(is_string($value) or $value === null);
		return $value;
	}

	public function setString(int $key, string $value, bool $force = false) : void{
		$this->setPropertyValue($key, Entity::DATA_TYPE_STRING, $value, $force);
	}

	public function getCompoundTag(int $key) : ?CompoundTag{
		$value = $this->getPropertyValue($key, Entity::DATA_TYPE_COMPOUND_TAG);
		assert($value instanceof CompoundTag or $value === null);
		return $value;
	}

	public function setCompoundTag(int $key, CompoundTag $value, bool $force = false) : void{
		$this->setPropertyValue($key, Entity::DATA_TYPE_COMPOUND_TAG, $value, $force);
	}

	public function getBlockPos(int $key) : ?Vector3{
		$value = $this->getPropertyValue($key, Entity::DATA_TYPE_POS);
		assert($value instanceof Vector3 or $value === null);
		return $value;
	}

	public function setBlockPos(int $key, ?Vector3 $value, bool $force = false) : void{
		$this->setPropertyValue($key, Entity::DATA_TYPE_POS, $value !== null ? $value->floor() : null, $force);
	}

	public function getLong(int $key) : ?int{
		$value = $this->getPropertyValue($key, Entity::DATA_TYPE_LONG);
		assert(is_int($value) or $value === null);
		return $value;
	}

	public function setLong(int $key, int $value, bool $force = false) : void{
		$this->setPropertyValue($key, Entity::DATA_TYPE_LONG, $value, $force);
	}

	public function getVector3(int $key) : ?Vector3{
		$value = $this->getPropertyValue($key, Entity::DATA_TYPE_VECTOR3F);
		assert($value instanceof Vector3 or $value === null);
		return $value;
	}

	public function setVector3(int $key, ?Vector3 $value, bool $force = false) : void{
		$this->setPropertyValue($key, Entity::DATA_TYPE_VECTOR3F, $value?->asVector3(), $force);
	}

	public function removeProperty(int $key) : void{
		unset($this->properties[$key]);
	}

	public function hasProperty(int $key) : bool{
		return isset($this->properties[$key]);
	}

	public function getPropertyType(int $key) : int{
		if(isset($this->properties[$key])){
			return $this->properties[$key][0];
		}

		return -1;
	}

	private function checkType(int $key, int $type) : void{
		if(isset($this->properties[$key]) and $this->properties[$key][0] !== $type){
			throw new \RuntimeException("Expected type $type, but have " . $this->properties[$key][0]);
		}
	}

	/**
	 * @return mixed
	 */
	public function getPropertyValue(int $key, int $type){
		if($type !== -1){
			$this->checkType($key, $type);
		}
		return isset($this->properties[$key]) ? $this->properties[$key][1] : null;
	}

	/**
	 * @param mixed $value
	 */
	public function setPropertyValue(int $key, int $type, $value, bool $force = false) : void{
		if(!$force){
			$this->checkType($key, $type);
		}
		$this->properties[$key] = $this->dirtyProperties[$key] = [$type, $value];
	}

	/**
	 * Returns all properties.
	 *
	 * @return mixed[][]
	 * @phpstan-return array<int, array{0: int, 1: mixed}>
	 */
	public function getAll() : array{
		return $this->properties;
	}

	/**
	 * Returns properties that have changed and need to be broadcasted.
	 *
	 * @return mixed[][]
	 * @phpstan-return array<int, array{0: int, 1: mixed}>
	 */
	public function getDirty() : array{
		return $this->dirtyProperties;
	}

	/**
	 * Clears records of dirty properties.
	 */
	public function clearDirtyProperties() : void{
		$this->dirtyProperties = [];
	}
}


