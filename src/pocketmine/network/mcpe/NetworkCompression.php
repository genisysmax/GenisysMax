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

use pocketmine\utils\Zlib;
use const ZLIB_ENCODING_DEFLATE;

final class NetworkCompression{
	public static $LEVEL = 7;
	public static $THRESHOLD = 256;

	private function __construct(){

	}

	public static function decompress(string $payload) : string{
		return Zlib::decompress($payload, 1024 * 1024 * 2); //Max 2 MB
	}

	/**
	 * @param string $payload
	 * @param int|null $compressionLevel
	 *
	 * @return string
	 */
	public static function compress(string $payload, ?int $compressionLevel = null) : string{
		return Zlib::compress($payload, ZLIB_ENCODING_DEFLATE, $compressionLevel ?? self::$LEVEL);
	}
}

