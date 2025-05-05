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

namespace pocketmine\level\format\io\region;

use pocketmine\level\format\Chunk;
use pocketmine\level\format\ChunkException;
use pocketmine\level\format\io\ChunkUtils;
use pocketmine\level\format\SubChunk;
use pocketmine\level\LevelException;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntArrayTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\Player;
use pocketmine\utils\Zlib;
use const ZLIB_ENCODING_DEFLATE;

class Anvil extends McRegion{

	public const REGION_FILE_EXTENSION = "mca";

	public function nbtSerialize(Chunk $chunk) : string{
		$nbt = new CompoundTag();
		$nbt->setInt("xPos", $chunk->getX());
		$nbt->setInt("zPos", $chunk->getZ());

		$nbt->setByte("V", 1);
		$nbt->setLong("LastUpdate", 0); //TODO
		$nbt->setLong("InhabitedTime", 0); //TODO
		$nbt->setByte("TerrainPopulated", $chunk->isPopulated() ? 1 : 0);
		$nbt->setByte("LightPopulated", $chunk->isLightPopulated() ? 1 : 0);

		$sections = new ListTag([], NBT::TAG_Compound);
		foreach($chunk->getSubChunks() as $y => $subChunk){
			if($subChunk->isEmpty()){
				continue;
			}
			$sections->push(CompoundTag::create()
				->setByte("Y", $y)
				->setByteArray("Blocks", ChunkUtils::reorderByteArray($subChunk->getBlockIdArray())) //Generic in-memory chunks are currently always XZY
				->setByteArray("Data", ChunkUtils::reorderNibbleArray($subChunk->getBlockDataArray()))
				->setByteArray("SkyLight", ChunkUtils::reorderNibbleArray($subChunk->getBlockSkyLightArray(), "\xff"))
				->setByteArray("BlockLight", ChunkUtils::reorderNibbleArray($subChunk->getBlockLightArray()))
			);
		}
		$nbt->setTag("Sections", $sections);

		$nbt->setByteArray("Biomes", $chunk->getBiomeIdArray());
		$nbt->setIntArray("HeightMap", $chunk->getHeightMapArray());

		$entities = [];

		foreach($chunk->getEntities() as $entity){
			if(!($entity instanceof Player) and !$entity->closed){
				$entity->saveNBT();
				$entities[] = $entity->namedtag;
			}
		}

		$nbt->setTag("Entities", new ListTag($entities));

		$tiles = [];
		foreach($chunk->getTiles() as $tile){
			$tiles[] = $tile->saveNBT();
		}

		$nbt->setTag("TileEntities", new ListTag($tiles));

		//TODO: TileTicks

		return Zlib::compress(
			(new BigEndianNbtSerializer())->write(new TreeRoot(
				CompoundTag::create()
					->setTag("Level", $nbt))
			),
			ZLIB_ENCODING_DEFLATE,
			RegionLoader::$COMPRESSION_LEVEL
		);
	}

	public function nbtDeserialize(string $data){
		try{
			$data = @Zlib::decompress($data);
			if($data === false){
				throw new LevelException("Corrupted chunk data");
			}

			$chunk = (new BigEndianNbtSerializer())->read($data)->mustGetCompoundTag();

			if(!$chunk->hasTag("Level", CompoundTag::class)){
				throw new ChunkException("Invalid NBT format");
			}

			$chunk = $chunk->getCompoundTag("Level");

			$subChunks = [];
			if($chunk->hasTag("Sections", ListTag::class)){
				foreach($chunk->getListTag("Sections") as $subChunk){
					if($subChunk instanceof CompoundTag){
						$subChunks[$subChunk->getByte("Y")] = new SubChunk(
							ChunkUtils::reorderByteArray($subChunk->getByteArray("Blocks")),
							ChunkUtils::reorderNibbleArray($subChunk->getByteArray("Data")),
							ChunkUtils::reorderNibbleArray($subChunk->getByteArray("SkyLight"), "\xff"),
							ChunkUtils::reorderNibbleArray($subChunk->getByteArray("BlockLight"))
						);
					}
				}
			}

			if($chunk->hasTag("BiomeColors", IntArrayTag::class)){
				$biomeIds = ChunkUtils::convertBiomeColors($chunk->getIntArray("BiomeColors")); //Convert back to original format
			}elseif($chunk->hasTag("Biomes", IntArrayTag::class)){
				$biomeIds = $chunk->getInt("Biomes");
			}else{
				$biomeIds = "";
			}

			$result = new Chunk(
				$chunk->getInt("xPos"),
				$chunk->getInt("zPos"),
				$subChunks,
				$chunk->hasTag("Entities", ListTag::class) ? $chunk->getListTag("Entities")->getValue() : [],
				$chunk->hasTag("TileEntities", ListTag::class) ? $chunk->getListTag("TileEntities")->getValue() : [],
				$biomeIds,
				$chunk->hasTag("HeightMap", IntArrayTag::class) ? $chunk->getIntArray("HeightMap") : []
			);
			$result->setLightPopulated($chunk->getByte("LightPopulated", 0) > 0);
			$result->setPopulated($chunk->getByte("TerrainPopulated", 0) > 0);
			$result->setGenerated(true);
			return $result;
		}catch(\Throwable $e){
			\GlobalLogger::get()->logException($e);
			return null;
		}
	}

	public static function getProviderName() : string{
		return "anvil";
	}

	public static function getPcWorldFormatVersion() : int{
		return 19133; //anvil
	}

	public function getWorldHeight() : int{
		//TODO: add world height options
		return 256;
	}

}

