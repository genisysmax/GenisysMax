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

namespace pocketmine\nbt;

use pocketmine\utils\BinaryDataException;

/**
 * @internal
 */
interface NbtStreamReader{

	/**
	 * @throws BinaryDataException
	 */
	public function readByte() : int;

	/**
	 * @throws BinaryDataException
	 */
	public function readSignedByte() : int;

	/**
	 * @throws BinaryDataException
	 */
	public function readShort() : int;

	/**
	 * @throws BinaryDataException
	 */
	public function readSignedShort() : int;

	/**
	 * @throws BinaryDataException
	 */
	public function readInt() : int;

	/**
	 * @throws BinaryDataException
	 */
	public function readLong() : int;

	/**
	 * @throws BinaryDataException
	 */
	public function readFloat() : float;

	/**
	 * @throws BinaryDataException
	 */
	public function readDouble() : float;

	/**
	 * @throws BinaryDataException
	 */
	public function readByteArray() : string;

	/**
	 * @throws BinaryDataException
	 */
	public function readString() : string;

	/**
	 * @return int[]
	 * @throws BinaryDataException
	 */
	public function readIntArray() : array;
}


