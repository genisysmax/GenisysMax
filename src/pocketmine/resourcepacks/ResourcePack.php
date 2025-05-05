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


namespace pocketmine\resourcepacks;


interface ResourcePack{

	/**
	 * Returns the human-readable name of the resource pack
	 * @return string
	 */
	public function getPackName() : string;

	/**
	 * Returns the pack's UUID as a human-readable string
	 * @return string
	 */
	public function getPackId() : string;

	/**
	 * Returns the size of the pack on disk in bytes.
	 * @return int
	 */
	public function getPackSize() : int;

	/**
	 * Returns a version number for the pack in the format major.minor.patch
	 * @return string
	 */
	public function getPackVersion() : string;

	/**
	 * Returns the raw SHA256 sum of the compressed resource pack zip. This is used by clients to validate pack downloads.
	 * @return string byte-array length 32 bytes
	 */
	public function getSha256() : string;

	/**
	 * Returns a chunk of the resource pack zip as a byte-array for sending to clients.
	 *
	 * Note that resource packs must **always** be in zip archive format for sending.
	 * A folder resource loader may need to perform on-the-fly compression for this purpose.
	 *
	 * @param int $start Offset to start reading the chunk from
	 * @param int $length Maximum length of data to return.
	 *
	 * @return string byte-array
	 */
	public function getPackChunk(int $start, int $length) : string;
}

