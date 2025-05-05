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

namespace pocketmine\network\bedrock\palette;

use function array_flip;
use function count;
use function file_get_contents;
use function json_decode;

final class BiomePalette{

	private function __construct(){
		//NOOP
	}

	/** @var int[] */
	private static $stringToLegacyIdMap = [];
	/** @var string[] */
	private static $legacyToStringIdMap = [];

	public static function init() : void{
		self::$stringToLegacyIdMap = json_decode(file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/biome_id_map.json"), true);
		self::$legacyToStringIdMap = array_flip(self::$stringToLegacyIdMap);
	}

	public static function lazyInit() : void{
		if(count(self::$stringToLegacyIdMap) === 0){
			self::init();
		}
	}

	/**
	 * @param int $legacyId
	 *
	 * @return string|null
	 */
	public static function getStringIdFromLegacyId(int $legacyId) : ?string{
		return self::$legacyToStringIdMap[$legacyId] ?? null;
	}

	/**
	 * @param string $stringId
	 *
	 * @return int
	 */
	public static function getLegacyIdFromStringId(string $stringId) : ?int{
		return self::$stringToLegacyIdMap[$stringId] ?? null;
	}
}

