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

namespace pocketmine\world\format\io;

use pocketmine\level\format\io\ChunkUtils;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\format\storage\BlockStorage;
use SplFixedArray;
use function array_flip;
use function array_keys;
use function count;
use function extension_loaded;
use function ord;
use function pack;
use function substr;

if(!extension_loaded("chunkutils2")){

	final class SubChunkConverter{

		public static function convertSubChunkXZY(string $idArray, string $metaArray) : PalettedBlockArray{
			$uniqueBlocks = [];
			$fullBlocks = [];
			for($index = 0; $index < 4096; ++$index){
				if(($index & 1) === 0){
					$fullBlock = (ord($idArray[$index]) << 4) | (ord($metaArray[$index >> 1]) & 0x0f);
				}else{
					$fullBlock = (ord($idArray[$index]) << 4) | (ord($metaArray[$index >> 1]) >> 4);
				}
				$fullBlocks[$index] = $fullBlock;

				$uniqueBlocks[$fullBlock] = true;
			}
			$palette = array_keys($uniqueBlocks);
			$paletteLookup = array_flip($palette);
			$paletteSize = count($palette);

			$bitsPerBlock = BlockStorage::PALETTED_16;
			foreach(BlockStorage::PALETTE_TYPES as $type){
				$maxCount = 1 << $type;
				if($paletteSize <= $maxCount){
					$bitsPerBlock = $type;
					break;
				}
			}
			$maxEntryValue = (1 << $bitsPerBlock) - 1;

			$wordArray = new SplFixedArray(PalettedBlockArray::getExpectedWordArraySize($bitsPerBlock));
			for($index = 0; $index < 4096; ++$index){
				$fullBlock = $fullBlocks[$index];

				$palettedVal = $paletteLookup[$fullBlock];
				BlockStorage::indexToOffset($bitsPerBlock, $index, $arrayIndex, $offset);

				$word = $wordArray->offsetGet($arrayIndex) ?? 0;
				$word &= ~($maxEntryValue << $offset); // clear old value
				$word |= ($palettedVal << $offset); // insert the new one
				$wordArray->offsetSet($arrayIndex, $word);
			}

			return PalettedBlockArray::fromData($bitsPerBlock, pack("V*", ...$wordArray), $palette);
		}

		public static function convertSubChunkYZX(string $idArray, string $metaArray) : PalettedBlockArray{
			return self::convertSubChunkXZY(ChunkUtils::reorderByteArray($idArray), ChunkUtils::reorderNibbleArray($metaArray));
		}

		public static function convertSubChunkFromLegacyColumn(string $idArray, string $metaArray, int $yOffset) : PalettedBlockArray{
			$offset = ($yOffset << 4);
			$ids = "";
			for($i = 0; $i < 256; ++$i){
				$ids .= substr($idArray, $offset, 16);
				$offset += 128;
			}

			$data = "";
			$offset = ($yOffset << 3);
			for($i = 0; $i < 256; ++$i){
				$data .= substr($metaArray, $offset, 8);
				$offset += 64;
			}

			return self::convertSubChunkXZY($ids, $data);
		}

		private function __construct(){
		}
	}
}

