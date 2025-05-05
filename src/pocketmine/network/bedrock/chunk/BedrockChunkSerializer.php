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

namespace pocketmine\network\bedrock\chunk;

use Closure;
use pocketmine\level\format\Chunk;
use pocketmine\level\format\SubChunkInterface;
use pocketmine\level\generator\biome\Biome;
use pocketmine\network\bedrock\palette\BiomePalette;
use pocketmine\tile\Spawnable;
use pocketmine\utils\Binary;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\Utils;
use pocketmine\world\format\io\SubChunkConverter;
use pocketmine\world\format\PalettedBlockArray;
use function count;

final class BedrockChunkSerializer{
	public const LOWER_PADDING_SIZE = 4;

	private function __construct(){
		//NOOP
	}

	public static function serialize(Chunk $chunk, Closure $legacyToRuntime, ?string $tiles = null) : string{
		$stream = new BinaryStream();

		//TODO: HACK! fill in fake subchunks to make up for the new negative space client-side
		for($y = 0; $y < self::LOWER_PADDING_SIZE; $y++){
			$stream->putByte(8); //subchunk version 8
			$stream->putByte(0); //0 layers - client will treat this as all-air
		}

		for($y = 0, $subChunkCount = $chunk->getSubChunkSendCount(); $y < $subChunkCount; ++$y){
			$subChunk = $chunk->getSubChunk($y);
			$stream->put(self::serializeSubChunk($subChunk, $legacyToRuntime));
		}

		//TODO: right now we don't support 3D natively, so we just 3Dify our 2D biomes so they fill the column
		$encodedBiomePalette = self::serializeBiomesAsPalette($chunk);
		$stream->put(str_repeat($encodedBiomePalette, 24));

		$stream->putByte(0); //border block array count
		//Border block entry format: 1 byte (4 bits X, 4 bits Z). These are however useless since they crash the regular client.

		if($tiles !== null){
			$stream->put($tiles);
		}else{
			$stream->put(self::serializeTiles($chunk));
		}
		return $stream->buffer;
	}

	public static function serializeSubChunk(SubChunkInterface $subChunk, Closure $legacyToRuntime) : string{
		Utils::validateCallableSignature(function(int $blockId, int $meta) : int{}, $legacyToRuntime);
		$result = new BinaryStream();
		$result->putByte(8); // storage version

		$result->putByte(1); // layer count

		$blocks = SubChunkConverter::convertSubChunkXZY($subChunk->getBlockIdArray(), $subChunk->getBlockDataArray());

		// 1 is network format (palette out of runtimeIDs), 0 is storage format (palette out of NBT tags)
	    if($blocks->getBitsPerBlock() === 0){
			//TODO: we use these in memory, but the game doesn't support them yet
			//polyfill them with 1-bpb instead
			$bitsPerBlock = 1;
			$words = str_repeat("\x00", PalettedBlockArray::getExpectedWordArraySize(1));
		}else{
			$bitsPerBlock = $blocks->getBitsPerBlock();
			$words = $blocks->getWordArray();
		}
		$result->putByte(($bitsPerBlock << 1) | 1);
		$result->put($words);
		$palette = $blocks->getPalette();

		//these LSHIFT by 1 uvarints are optimizations: the client expects zigzag varints here
		//but since we know they are always unsigned, we can avoid the extra fcall overhead of
		//zigzag and just shift directly.
		$result->putUnsignedVarInt(count($palette) << 1);
		foreach($palette as $fullBlock){
			$runtimeId = $legacyToRuntime($fullBlock >> 4, $fullBlock & 0x0f);
			$result->putUnsignedVarInt($runtimeId << 1);
		}
		return $result->buffer;
	}

	public static function serializeTiles(Chunk $chunk) : string{
		$buffer = "";
		foreach($chunk->getTiles() as $tile){
			if($tile instanceof Spawnable){
				$buffer .= $tile->getSerializedSpawnCompound(true);
			}
		}
		return $buffer;
	}

	private static function serializeBiomesAsPalette(Chunk $chunk) : string{
		BiomePalette::lazyInit();

		$biomePalette = new PalettedBlockArray($chunk->getBiomeId(0, 0));
		for($x = 0; $x < 16; ++$x){
			for($z = 0; $z < 16; ++$z){
				$biomeId = $chunk->getBiomeId($x, $z);
				if(BiomePalette::getStringIdFromLegacyId($biomeId) === null){
					//make sure we aren't sending bogus biomes - the 1.18.0 client crashes if we do this
					$biomeId = Biome::OCEAN;
				}
				for($y = 0; $y < 16; ++$y){
					$biomePalette->set($x, $y, $z, $biomeId);
				}
			}
		}

		$biomePaletteBitsPerBlock = $biomePalette->getBitsPerBlock();
		$encodedBiomePalette =
			chr(($biomePaletteBitsPerBlock << 1) | 1) . //the last bit is non-persistence (like for blocks), though it has no effect on biomes since they always use integer IDs
			$biomePalette->getWordArray();

		//these LSHIFT by 1 uvarints are optimizations: the client expects zigzag varints here
		//but since we know they are always unsigned, we can avoid the extra fcall overhead of
		//zigzag and just shift directly.
		$biomePaletteArray = $biomePalette->getPalette();
		if($biomePaletteBitsPerBlock !== 0){
			$encodedBiomePalette .= Binary::writeUnsignedVarInt(count($biomePaletteArray) << 1);
		}
		foreach($biomePaletteArray as $p){
			$encodedBiomePalette .= Binary::writeUnsignedVarInt($p << 1);
		}

		return $encodedBiomePalette;
	}
}


