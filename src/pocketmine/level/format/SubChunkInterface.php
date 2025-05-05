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

interface SubChunkInterface{

	/**
	 * @param bool $checkLight
	 * @return bool
	 */
	public function isEmpty(bool $checkLight = true) : bool;

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 *
	 * @return int
	 */
	public function getBlockId(int $x, int $y, int $z) : int;

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $id
	 *
	 * @return bool
	 */
	public function setBlockId(int $x, int $y, int $z, int $id) : bool;

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 *
	 * @return int
	 */
	public function getBlockData(int $x, int $y, int $z) : int;

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $data
	 *
	 * @return bool
	 */
	public function setBlockData(int $x, int $y, int $z, int $data) : bool;

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 *
	 * @return int
	 */
	public function getFullBlock(int $x, int $y, int $z) : int;

	/**
	 * @param int      $x
	 * @param int      $y
	 * @param int      $z
	 * @param int|null $id
	 * @param int|null $data
	 *
	 * @return bool
	 */
	public function setBlock(int $x, int $y, int $z, $id = null, $data = null) : bool;

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 *
	 * @return int
	 */
	public function getBlockLight(int $x, int $y, int $z) : int;

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $level
	 *
	 * @return bool
	 */
	public function setBlockLight(int $x, int $y, int $z, int $level) : bool;

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 *
	 * @return int
	 */
	public function getBlockSkyLight(int $x, int $y, int $z) : int;

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $level
	 *
	 * @return bool
	 */
	public function setBlockSkyLight(int $x, int $y, int $z, int $level) : bool;

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return int
	 */
	public function getHighestBlockAt(int $x, int $z) : int;

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return string
	 */
	public function getBlockIdColumn(int $x, int $z) : string;

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return string
	 */
	public function getBlockDataColumn(int $x, int $z) : string;

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return string
	 */
	public function getBlockLightColumn(int $x, int $z) : string;

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return string
	 */
	public function getBlockSkyLightColumn(int $x, int $z) : string;

	/**
	 * @return string
	 */
	public function getBlockIdArray() : string;

	/**
	 * @return string
	 */
	public function getBlockDataArray() : string;

	/**
	 * @return string
	 */
	public function getBlockSkyLightArray() : string;

	/**
	 * @param string $data
	 */
	public function setBlockSkyLightArray(string $data);

	/**
	 * @return string
	 */
	public function getBlockLightArray() : string;

	/**
	 * @param string $data
	 */
	public function setBlockLightArray(string $data);

	/**
	 * @return string
	 */
	public function fastSerialize() : string;
}

