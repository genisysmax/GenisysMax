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

namespace pocketmine\network\bedrock\palette\item;

use InvalidStateException;
use pocketmine\network\mcpe\NetworkBinaryStream;
use UnexpectedValueException;
use function count;
use function file_get_contents;
use function json_decode;

trait ItemPaletteSerializer{

	public function __construct(){
		//NOOP
	}

	/** @var int[] */
	private static $stringToRuntimeIdMap = [];
	/** @var string[] */
	private static $runtimeToStringIdMap = [];

	/** @var int[] */
	private static $simpleLegacyToRuntimeIdMap = [];
	/** @var int[] */
	private static $simpleRuntimeToLegacyIdMap = [];

	/** @var int[][] */
	private static $complexLegacyToRuntimeIdMap = []; // array[internalID][metadata] = runtimeID
	/** @var int[][] */
	private static $complexRuntimeToLegacyIdMap = []; // array[runtimeID] = [internalID, metadata]

	/** @var string */
	private static $encodedPalette;

	public static function init() : void{
		/** @var int[] $itemPalette */
		$itemMap = json_decode(file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v".self::PROTOCOL_R16."/r16_to_current_item_map.json"), true);

		/** @var int[] $itemPalette */
		$stringToIntMap = json_decode(file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/item_id_map.json"), true);

		$simpleMappings = [];
		foreach($itemMap["simple"] as $oldId => $newId){
            if(!isset($stringToIntMap[$oldId])){
                //new item without a fixed legacy ID - we can't handle this right now
                continue;
            }
			$simpleMappings[$newId] = $stringToIntMap[$oldId];
		}
		foreach($stringToIntMap as $stringId => $intId){
			if(isset($simpleMappings[$stringId])){
				throw new InvalidStateException("Old ID $stringId collides with new ID");
			}
			$simpleMappings[$stringId] = $intId;
		}

		$complexMappings = [];
		foreach($itemMap["complex"] as $oldId => $map){
			foreach($map as $meta => $newId){
				if(isset($stringToIntMap[$oldId])){
					$complexMappings[$newId] = [$stringToIntMap[$oldId], (int) $meta];
				}
			}
		}

		$itemList = json_decode(file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v".self::PROTOCOL_RIL."/required_item_list.json"), true);
		foreach($itemList as $stringId => $entry){
			$runtimeId = $entry["runtime_id"];

			self::$stringToRuntimeIdMap[$stringId] = $runtimeId;
			self::$runtimeToStringIdMap[$runtimeId] = $stringId;

			if(isset($complexMappings[$stringId])){
				[$id, $meta] = $complexMappings[$stringId];
				self::$complexLegacyToRuntimeIdMap[$id][$meta] = $runtimeId;
				self::$complexRuntimeToLegacyIdMap[$runtimeId] = [$id, $meta];
			}elseif(isset($simpleMappings[$stringId])){
				self::$simpleLegacyToRuntimeIdMap[$simpleMappings[$stringId]] = $runtimeId;
				self::$simpleRuntimeToLegacyIdMap[$runtimeId] = $simpleMappings[$stringId];
			}else{
                //not all items have a legacy mapping - for now, we only support the ones that do
                continue;
            }
		}

		$stream = new NetworkBinaryStream();
		$stream->putUnsignedVarInt(count($itemList));
		foreach($itemList as $stringId => $entry){
			$stream->putString($stringId);
			$stream->putLShort($entry["runtime_id"]);
			$stream->putBool($entry["component_based"]);
		}
		self::$encodedPalette = $stream->buffer;
	}

	/**
	 * @param int $runtimeId
	 *
	 * @return string
	 */
	public static function getStringFromRuntimeId(int $runtimeId) : string{
		return self::$runtimeToStringIdMap[$runtimeId] ?? "minecraft:unknown";
	}

	/**
	 * @param string $stringId
	 *
	 * @return int
	 */
	public static function getRuntimeFromStringId(string $stringId) : int{
		return self::$stringToRuntimeIdMap[$stringId] ?? -1;
	}

	/**
	 * @param int $id
	 * @param int $meta
	 *
	 * @return int[]
	 */
	public static function getRuntimeFromLegacyId(int $id, int $meta = 0) : array{
        if($meta === -1){
            $meta = 0x7fff;
        }
		if(isset(self::$complexLegacyToRuntimeIdMap[$id][$meta])){
			return [self::$complexLegacyToRuntimeIdMap[$id][$meta], 0];
		}
		if(isset(self::$simpleLegacyToRuntimeIdMap[$id])){
			return [self::$simpleLegacyToRuntimeIdMap[$id], $meta];
		}

		//throw new InvalidArgumentException("Unmapped ID/metadata combination $id:$meta");
        return [0, 0];
	}

	/**
	 * @param int $runtimeId
	 * @param int $runtimeMeta
	 * @param false $isComplex
	 *
	 * @return int[]
	 */
	public static function getLegacyFromRuntimeId(int $runtimeId, int $runtimeMeta, &$isComplex = false) : array{
		if(isset(self::$complexRuntimeToLegacyIdMap[$runtimeId])){
			if($runtimeMeta !== 0){
				throw new UnexpectedValueException("Unexpected non-zero network meta on complex item mapping");
			}

			$isComplex = true;
			return self::$complexRuntimeToLegacyIdMap[$runtimeId];
		}
		if(isset(self::$simpleRuntimeToLegacyIdMap[$runtimeId])){
			return [self::$simpleRuntimeToLegacyIdMap[$runtimeId], $runtimeMeta];
		}
        //throw new InvalidArgumentException("Unmapped network ID/metadata combination $runtimeId:$runtimeMeta");
        return [0, 0];
	}

	public static function getLegacyFromRuntimeIdWildcard(int $runtimeId, int $runtimeMeta) : array{
		if($runtimeMeta !== 0x7fff){
			return self::getLegacyFromRuntimeId($runtimeId, $runtimeMeta);
		}

		$isComplex = false;
		[$id, $meta] = self::getLegacyFromRuntimeId($runtimeId, 0, $isComplex);

		if($isComplex){
			return [$id, $meta];
		}else{
			return [$id, -1];
		}
	}

    public static function getEncodedPalette() : ?string{
        return self::$encodedPalette;
    }

    public static function setEncodedPalette(?string $buffer) : void{
        self::$encodedPalette = $buffer;
    }

    public static function getComplexRuntimeToLegacy() : array{
        return self::$complexRuntimeToLegacyIdMap;
    }

    public static function setComplexRuntimeToLegacy(array $complex) : void{
        self::$complexRuntimeToLegacyIdMap = $complex;
    }

    public static function getComplexLegacyToRuntime() : array{
        return self::$complexLegacyToRuntimeIdMap;
    }

    public static function setComplexLegacyToRuntime(array $complex) : void{
        self::$complexLegacyToRuntimeIdMap = $complex;
    }

    public static function getSimpleRuntimeToLegacy() : array{
        return self::$simpleRuntimeToLegacyIdMap;
    }

    public static function setSimpleRuntimeToLegacy(array $simple) : void{
        self::$simpleRuntimeToLegacyIdMap = $simple;
    }

    public static function getSimpleLegacyToRuntime() : array{
        return self::$simpleLegacyToRuntimeIdMap;
    }

    public static function setSimpleLegacyToRuntime(array $simple) : void{
        self::$simpleLegacyToRuntimeIdMap = $simple;
    }

    public static function getStringToRuntime() : array{
        return self::$stringToRuntimeIdMap;
    }

    public static function setStringToRuntime(array $string) : void{
        self::$stringToRuntimeIdMap = $string;
    }

    public static function getRuntimeToString() : array{
        return self::$runtimeToStringIdMap;
    }

    public static function setRuntimeToString(array $string) : void{
        self::$runtimeToStringIdMap = $string;
    }
}

