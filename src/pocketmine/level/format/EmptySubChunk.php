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

use function str_repeat;

if(!defined(__NAMESPACE__ . '\ZERO_NIBBLE_ARRAY')){
	define(__NAMESPACE__ . '\ZERO_NIBBLE_ARRAY', str_repeat("\x00", 2048));
}
if(!defined(__NAMESPACE__ . '\DOUBLE_ZERO_NIBBLE_ARRAY')){
	define(__NAMESPACE__ . '\DOUBLE_ZERO_NIBBLE_ARRAY', str_repeat("\x00", 4096));
}
if(!defined(__NAMESPACE__ . '\FIFTEEN_NIBBLE_ARRAY')){
	define(__NAMESPACE__ . '\FIFTEEN_NIBBLE_ARRAY', str_repeat("\xff", 2048));
}

class EmptySubChunk implements SubChunkInterface{

	public function isEmpty(bool $checkLight = true) : bool{
		return true;
	}

	public function getBlockId(int $x, int $y, int $z) : int{
		return 0;
	}

	public function setBlockId(int $x, int $y, int $z, int $id) : bool{
		return false;
	}

	public function getBlockData(int $x, int $y, int $z) : int{
		return 0;
	}

	public function setBlockData(int $x, int $y, int $z, int $data) : bool{
		return false;
	}

	public function getFullBlock(int $x, int $y, int $z) : int{
		return 0;
	}

	public function setBlock(int $x, int $y, int $z, $id = null, $data = null) : bool{
		return false;
	}

	public function getBlockLight(int $x, int $y, int $z) : int{
		return 0;
	}

	public function setBlockLight(int $x, int $y, int $z, int $level) : bool{
		return false;
	}

	public function getBlockSkyLight(int $x, int $y, int $z) : int{
		return 15;
	}

	public function setBlockSkyLight(int $x, int $y, int $z, int $level) : bool{
		return false;
	}

	public function getHighestBlockAt(int $x, int $z) : int{
		return -1;
	}

	public function getBlockIdColumn(int $x, int $z) : string{
		return "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
	}

	public function getBlockDataColumn(int $x, int $z) : string{
		return "\x00\x00\x00\x00\x00\x00\x00\x00";
	}

	public function getBlockLightColumn(int $x, int $z) : string{
		return "\x00\x00\x00\x00\x00\x00\x00\x00";
	}

	public function getBlockSkyLightColumn(int $x, int $z) : string{
		return "\xff\xff\xff\xff\xff\xff\xff\xff";
	}

	public function getBlockIdArray() : string{
		return DOUBLE_ZERO_NIBBLE_ARRAY;
	}

	public function getBlockDataArray() : string{
		return ZERO_NIBBLE_ARRAY;
	}

	public function getBlockLightArray() : string{
		return ZERO_NIBBLE_ARRAY;
	}

	public function setBlockLightArray(string $data){

	}

	public function getBlockSkyLightArray() : string{
		return FIFTEEN_NIBBLE_ARRAY;
	}

	public function setBlockSkyLightArray(string $data){

	}

	public function fastSerialize() : string{
		throw new \BadMethodCallException("Should not try to serialize empty subchunks");
	}
}

