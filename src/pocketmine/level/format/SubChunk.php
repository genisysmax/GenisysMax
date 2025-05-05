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

namespace pocketmine\level\format;

use function assert;
use function chr;
use function ord;
use function str_repeat;
use function strlen;
use function substr;

if(!defined(__NAMESPACE__ . '\ZERO_NIBBLE_ARRAY')){
	define(__NAMESPACE__ . '\ZERO_NIBBLE_ARRAY', str_repeat("\x00", 2048));
}
if(!defined(__NAMESPACE__ . '\DOUBLE_ZERO_NIBBLE_ARRAY')){
	define(__NAMESPACE__ . '\DOUBLE_ZERO_NIBBLE_ARRAY', str_repeat("\x00", 4096));
}
if(!defined(__NAMESPACE__ . '\FIFTEEN_NIBBLE_ARRAY')){
	define(__NAMESPACE__ . '\FIFTEEN_NIBBLE_ARRAY', str_repeat("\xff", 2048));
}

class SubChunk implements SubChunkInterface{

	protected $ids;
	protected $data;
	protected $blockLight;
	protected $skyLight;

	private static function assignData(&$target, string $data, int $length, string $default) : void{
		if($data === "" or $data === $default){
			$target = $default;
		}elseif(strlen($data) !== $length){
			assert(false, "Invalid length given, expected $length, got " . strlen($data));
			$target = $default;
		}else{
			$target = $data;
		}
	}

	public function __construct(string $ids = "", string $data = "", string $skyLight = "", string $blockLight = ""){
		self::assignData($this->ids, $ids, 4096, DOUBLE_ZERO_NIBBLE_ARRAY);
		self::assignData($this->data, $data, 2048, ZERO_NIBBLE_ARRAY);
		self::assignData($this->skyLight, $skyLight, 2048, FIFTEEN_NIBBLE_ARRAY);
		self::assignData($this->blockLight, $blockLight, 2048, ZERO_NIBBLE_ARRAY);
		$this->collectGarbage();
	}

	public function isEmpty(bool $checkLight = true) : bool{
		return (
			$this->ids === DOUBLE_ZERO_NIBBLE_ARRAY and
			(!$checkLight or (
				$this->skyLight === FIFTEEN_NIBBLE_ARRAY and
				$this->blockLight === ZERO_NIBBLE_ARRAY
			))
		);
	}

	public function getBlockId(int $x, int $y, int $z) : int{
		return ord($this->ids[($x << 8) | ($z << 4) | $y]);
	}

	public function setBlockId(int $x, int $y, int $z, int $id) : bool{
		$this->ids[($x << 8) | ($z << 4) | $y] = chr($id);
		return true;
	}

	public function getBlockData(int $x, int $y, int $z) : int{
		$m = ord($this->data[($x << 7) + ($z << 3) + ($y >> 1)]);
		if(($y & 1) === 0){
			return $m & 0x0f;
		}else{
			return $m >> 4;
		}
	}

	public function setBlockData(int $x, int $y, int $z, int $data) : bool{
		$i = ($x << 7) | ($z << 3) | ($y >> 1);
		if(($y & 1) === 0){
			$this->data[$i] = chr((ord($this->data[$i]) & 0xf0) | ($data & 0x0f));
		}else{
			$this->data[$i] = chr((($data & 0x0f) << 4) | (ord($this->data[$i]) & 0x0f));
		}
		return true;
	}

	public function getFullBlock(int $x, int $y, int $z) : int{
		$i = ($x << 8) | ($z << 4) | $y;
		if(($y & 1) === 0){
			return (ord($this->ids[$i]) << 4) | (ord($this->data[$i >> 1]) & 0x0f);
		}else{
			return (ord($this->ids[$i]) << 4) | (ord($this->data[$i >> 1]) >> 4);
		}
	}

	public function setBlock(int $x, int $y, int $z, $id = null, $data = null) : bool{
		$i = ($x << 8) | ($z << 4) | $y;
		$changed = false;
		if($id !== null){
			$block = chr($id);
			if($this->ids[$i] !== $block){
				$this->ids[$i] = $block;
				$changed = true;
			}
		}

		if($data !== null){
			$i >>= 1;
			$byte = ord($this->data[$i]);
			if(($y & 1) === 0){
				$this->data[$i] = chr(($byte & 0xf0) | ($data & 0x0f));
			}else{
				$this->data[$i] = chr((($data & 0x0f) << 4) | ($byte & 0x0f));
			}
			if($this->data[$i] !== $byte){
				$changed = true;
			}
		}

		return $changed;
	}

	public function getBlockLight(int $x, int $y, int $z) : int{
		$byte = ord($this->blockLight[($x << 7) + ($z << 3) + ($y >> 1)]);
		if(($y & 1) === 0){
			return $byte & 0x0f;
		}else{
			return $byte >> 4;
		}
	}

	public function setBlockLight(int $x, int $y, int $z, int $level) : bool{
		$i = ($x << 7) + ($z << 3) + ($y >> 1);
		$byte = ord($this->blockLight[$i]);
		if(($y & 1) === 0){
			$this->blockLight[$i] = chr(($byte & 0xf0) | ($level & 0x0f));
		}else{
			$this->blockLight[$i] = chr((($level & 0x0f) << 4) | ($byte & 0x0f));
		}
		return true;
	}

	public function getBlockSkyLight(int $x, int $y, int $z) : int{
		$byte = ord($this->skyLight[($x << 7) + ($z << 3) + ($y >> 1)]);
		if(($y & 1) === 0){
			return $byte & 0x0f;
		}else{
			return $byte >> 4;
		}
	}

	public function setBlockSkyLight(int $x, int $y, int $z, int $level) : bool{
		$i = ($x << 7) + ($z << 3) + ($y >> 1);
		$byte = ord($this->skyLight[$i]);
		if(($y & 1) === 0){
			$this->skyLight[$i] = chr(($byte & 0xf0) | ($level & 0x0f));
		}else{
			$this->skyLight[$i] = chr((($level & 0x0f) << 4) | ($byte & 0x0f));
		}
		return true;
	}

	public function getHighestBlockAt(int $x, int $z) : int{
		$low = ($x << 8) | ($z << 4);
		$i = $low | 0x0f;
		for(; $i >= $low; --$i){
			if($this->ids[$i] !== "\x00"){
				return $i & 0x0f;
			}
		}

		return -1; //highest block not in this subchunk
	}

	public function getBlockIdColumn(int $x, int $z) : string{
		return substr($this->ids, ($x << 8) | ($z << 4), 16);
	}

	public function getBlockDataColumn(int $x, int $z) : string{
		return substr($this->data, ($x << 7) | ($z << 3), 8);
	}

	public function getBlockLightColumn(int $x, int $z) : string{
		return substr($this->blockLight, ($x << 7) | ($z << 3), 8);
	}

	public function getBlockSkyLightColumn(int $x, int $z) : string{
		return substr($this->skyLight, ($x << 7) | ($z << 3), 8);
	}

	public function getBlockIdArray() : string{
		assert(strlen($this->ids) === 4096, "Wrong length of ID array, expecting 4096 bytes, got " . strlen($this->ids));
		return $this->ids;
	}

	public function getBlockDataArray() : string{
		assert(strlen($this->data) === 2048, "Wrong length of data array, expecting 2048 bytes, got " . strlen($this->data));
		return $this->data;
	}

	public function getBlockSkyLightArray() : string{
		assert(strlen($this->skyLight) === 2048, "Wrong length of skylight array, expecting 2048 bytes, got " . strlen($this->skyLight));
		return $this->skyLight;
	}

	public function setBlockSkyLightArray(string $data){
		assert(strlen($data) === 2048, "Wrong length of skylight array, expecting 2048 bytes, got " . strlen($data));
		$this->skyLight = $data;
	}

	public function getBlockLightArray() : string{
		assert(strlen($this->blockLight) === 2048, "Wrong length of light array, expecting 2048 bytes, got " . strlen($this->blockLight));
		return $this->blockLight;
	}

	public function setBlockLightArray(string $data){
		assert(strlen($data) === 2048, "Wrong length of light array, expecting 2048 bytes, got " . strlen($data));
		$this->blockLight = $data;
	}

	public function fastSerialize() : string{
		return
			$this->ids .
			$this->data .
			$this->skyLight .
			$this->blockLight;
	}

	public static function fastDeserialize(string $data) : SubChunk{
		return new SubChunk(
			substr($data,    0, 4096), //ids
			substr($data, 4096, 2048), //data
			substr($data, 6144, 2048), //sky light
			substr($data, 8192, 2048)  //block light
		);
	}

	public function collectGarbage() : void{
		/*
		 * This strange looking code is designed to exploit PHP's copy-on-write behaviour. Assigning will copy a
		 * reference to the const instead of duplicating the whole string. The string will only be duplicated when
		 * modified, which is perfect for this purpose.
		 */
		if($this->ids === DOUBLE_ZERO_NIBBLE_ARRAY){
			$this->ids = DOUBLE_ZERO_NIBBLE_ARRAY;
		}
		if($this->data === ZERO_NIBBLE_ARRAY){
			$this->data = ZERO_NIBBLE_ARRAY;
		}
		if($this->skyLight === FIFTEEN_NIBBLE_ARRAY){
			$this->skyLight = FIFTEEN_NIBBLE_ARRAY;
		}
		if($this->skyLight === ZERO_NIBBLE_ARRAY){
			$this->skyLight = ZERO_NIBBLE_ARRAY;
		}
		if($this->blockLight === ZERO_NIBBLE_ARRAY){
			$this->blockLight = ZERO_NIBBLE_ARRAY;
		}
	}
}

