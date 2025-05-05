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

namespace pocketmine\utils;

use InvalidArgumentException;
use function function_exists;
use function libdeflate_gzip_compress;
use function libdeflate_zlib_compress;
use function zlib_decode;
use function zlib_encode;
use const ZLIB_ENCODING_DEFLATE;
use const ZLIB_ENCODING_GZIP;
use const ZLIB_ENCODING_RAW;

if(function_exists("libdeflate_deflate_compress")){

	final class Zlib{
		private function __construct(){

		}

		public static function decompress(string $payload, int $maxLen = 0) : string{
			$data = @zlib_decode($payload, $maxLen); //Max 2 MB
			if($data == false) {
				return "";
			}
			return $data;
		}

		/**
		 * @param string $payload
		 * @param int $encoding
		 * @param int $compressionLevel
		 *
		 * @return string
		 */
		public static function compress(string $payload, int $encoding, int $compressionLevel = 6) : string{
			switch($encoding){
				case ZLIB_ENCODING_RAW:
					return libdeflate_deflate_compress($payload, $compressionLevel);
				case ZLIB_ENCODING_DEFLATE:
					return libdeflate_zlib_compress($payload, $compressionLevel);
				case ZLIB_ENCODING_GZIP:
					return libdeflate_gzip_compress($payload, $compressionLevel);
			}
			throw new InvalidArgumentException("Unknwon Zlib enconding: {$encoding}");
		}
	}
}else{

	final class Zlib{
		private function __construct(){

		}

		public static function decompress(string $payload, int $maxLen = 0) : string{
			$data = @zlib_decode($payload, $maxLen);
			if($data == false) {
				return "";
			}
			return $data;
		}

		/**
		 * @param string $payload
		 * @param int $encoding
		 * @param int $compressionLevel
		 *
		 * @return string
		 */
		public static function compress(string $payload, int $encoding, int $compressionLevel = -1) : string{
			return zlib_encode($payload, $encoding, $compressionLevel);
		}
	}
}

