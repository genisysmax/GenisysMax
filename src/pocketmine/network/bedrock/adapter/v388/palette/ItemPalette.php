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

namespace pocketmine\network\bedrock\adapter\v388\palette;

use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\NetworkBinaryStream;
use function count;
use function file_get_contents;
use function json_decode;

class ItemPalette{

	/** @var int[] */
	protected static $stringToNumericIdMap = [];
	/** @var string[] */
	protected static $numericToStringIdMap = [];

	/** @var string */
	protected static $encodedPalette;

	public static function init() : void{
		/** @var int[] $itemPalette */
		$itemPalette = json_decode(file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v388/item_id_map.json"), true);

		$stream = new NetworkBinaryStream();
		$stream->putUnsignedVarInt(count($itemPalette));

		foreach($itemPalette as $name => $id){
			self::$stringToNumericIdMap[$name] = $id;
			self::$numericToStringIdMap[$id] = $name;

			$stream->putString($name);
			$stream->putLShort($id);
		}

		self::$encodedPalette = $stream->getBuffer();
	}

	public static function lazyInit() : void{
		if(self::$encodedPalette === null){
			self::init();
		}
	}

	/**
	 * @param int $numberId
	 *
	 * @return string
	 */
	public static function getStringFromNumericId(int $numberId) : string{
		return self::$numericToStringIdMap[$numberId] ?? "minecraft:unknown";
	}

	/**
	 * @param string $stringId
	 *
	 * @return int
	 */
	public static function getNumericFromStringId(string $stringId) : int{
		return self::$stringToNumericIdMap[$stringId] ?? ItemIds::AIR;
	}

	/**
	 * @return string
	 */
	public static function getEncodedPalette() : string{
		return self::$encodedPalette;
	}
}

