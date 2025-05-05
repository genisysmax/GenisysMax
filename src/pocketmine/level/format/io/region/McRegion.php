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
use pocketmine\level\format\io\BaseLevelProvider;
use pocketmine\level\format\io\ChunkUtils;
use pocketmine\level\format\SubChunk;
use pocketmine\level\generator\Generator;
use pocketmine\level\Level;
use pocketmine\level\LevelException;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntArrayTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\Player;
use pocketmine\utils\MainLogger;
use pocketmine\utils\Utils;
use pocketmine\utils\Zlib;
use function array_filter;
use function array_values;
use function file_exists;
use function file_put_contents;
use function is_dir;
use function microtime;
use function mkdir;
use function pack;
use function rename;
use function scandir;
use function str_repeat;
use function strrpos;
use function substr;
use function time;
use function unpack;
use const SCANDIR_SORT_NONE;
use const ZLIB_ENCODING_DEFLATE;
use const ZLIB_ENCODING_GZIP;

class McRegion extends BaseLevelProvider{

	public const REGION_FILE_EXTENSION = "mcr";

	/** @var RegionLoader[] */
	protected $regions = [];

	/** @var Chunk[] */
	protected $chunks = [];

	/**
	 * @param Chunk $chunk
	 *
	 * @return string
	 */
	public function nbtSerialize(Chunk $chunk) : string{
		$nbt = new CompoundTag();
		$nbt->setInt("xPos", $chunk->getX());
		$nbt->setInt("zPos", $chunk->getZ());

		$nbt->setLong("LastUpdate", 0); //TODO
		$nbt->setByte("TerrainPopulated", $chunk->isPopulated() ? 1 : 0);
		$nbt->setByte("LightPopulated", $chunk->isLightPopulated() ? 1 : 0);

		$ids = "";
		$data = "";
		$skyLight = "";
		$blockLight = "";
		$subChunks = $chunk->getSubChunks();
		for($x = 0; $x < 16; ++$x){
			for($z = 0; $z < 16; ++$z){
				for($y = 0; $y < 8; ++$y){
					$subChunk = $subChunks[$y];
					$ids .= $subChunk->getBlockIdColumn($x, $z);
					$data .= $subChunk->getBlockDataColumn($x, $z);
					$skyLight .= $subChunk->getBlockSkyLightColumn($x, $z);
					$blockLight .= $subChunk->getBlockLightColumn($x, $z);
				}
			}
		}

		$nbt->setByteArray("Blocks", $ids);
		$nbt->setByteArray("Data", $data);
		$nbt->setByteArray("SkyLight", $skyLight);
		$nbt->setByteArray("BlockLight", $blockLight);

		$nbt->setByteArray("Biomes", $chunk->getBiomeIdArray()); //doesn't exist in regular McRegion, this is here for PocketMine-MP only
		$nbt->setByteArray("HeightMap", pack("C*", ...$chunk->getHeightMapArray()));

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

		return Zlib::compress(
			(new BigEndianNbtSerializer())->write(new TreeRoot(
				CompoundTag::create()
					->setTag("Level", $nbt))
			),
			ZLIB_ENCODING_DEFLATE,
			RegionLoader::$COMPRESSION_LEVEL
		);
	}

	/**
	 * @param string $data
	 *
	 * @return Chunk|null
	 */
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
			$fullIds = $chunk->getByteArray("Blocks", str_repeat("\x00", 32768));
			$fullData = $chunk->getByteArray("Data", str_repeat("\x00", 16384));
			$fullSkyLight = $chunk->getByteArray("SkyLight", str_repeat("\x00", 16384));
			$fullBlockLight = $chunk->getByteArray("BlockLight", str_repeat("\x00", 16384));

			for($y = 0; $y < 8; ++$y){
				$offset = ($y << 4);
				$ids = "";
				for($i = 0; $i < 256; ++$i){
					$ids .= substr($fullIds, $offset, 16);
					$offset += 128;
				}
				$data = "";
				$offset = ($y << 3);
				for($i = 0; $i < 256; ++$i){
					$data .= substr($fullData, $offset, 8);
					$offset += 64;
				}
				$skyLight = "";
				$offset = ($y << 3);
				for($i = 0; $i < 256; ++$i){
					$skyLight .= substr($fullSkyLight, $offset, 8);
					$offset += 64;
				}
				$blockLight = "";
				$offset = ($y << 3);
				for($i = 0; $i < 256; ++$i){
					$blockLight .= substr($fullBlockLight, $offset, 8);
					$offset += 64;
				}
				$subChunks[$y] = new SubChunk($ids, $data, $skyLight, $blockLight);
			}

			if($chunk->hasTag("BiomeColors", IntArrayTag::class)){
				$biomeIds = ChunkUtils::convertBiomeColors($chunk->getIntArray("BiomeColors")); //Convert back to original format
			}elseif($chunk->hasTag("Biomes", ByteArrayTag::class)){
				$biomeIds = $chunk->getByteArray("Biomes");
			}else{
				$biomeIds = "";
			}

			$heightMap = [];
			if($chunk->hasTag("HeightMap")){
				$heightMapTag = $chunk->getTag("HeightMap");

				if($heightMapTag instanceof ByteArrayTag){
					$heightMap = array_values(unpack("C*", $heightMapTag->getValue()));
				}elseif($heightMapTag instanceof IntArrayTag){
					$heightMap = $heightMapTag->getValue(); #blameshoghicp
				}
			}

			$result = new Chunk(
				$chunk->getInt("xPos"),
				$chunk->getInt("zPos"),
				$subChunks,
				$chunk->hasTag("Entities", ListTag::class) ? $chunk->getListTag("Entities")->getValue() : [],
				$chunk->hasTag("TileEntities", ListTag::class) ? $chunk->getListTag("TileEntities")->getValue() : [],
				$biomeIds,
				$heightMap
			);
			$result->setLightPopulated($chunk->getByte("LightPopulated", 0) > 0);
			$result->setPopulated($chunk->getByte("TerrainPopulated", 0) > 0);
			$result->setGenerated(true);
			return $result;
		}catch(\Throwable $e){
			MainLogger::getLogger()->logException($e);
			return null;
		}
	}

	public static function getProviderName() : string{
		return "mcregion";
	}

	/**
	 * Returns the storage version as per Minecraft PC world formats.
	 * @return int
	 */
	public static function getPcWorldFormatVersion() : int{
		return 19132; //mcregion
	}

	public function getWorldHeight() : int{
		//TODO: add world height options
		return 128;
	}

	public static function isValid(string $path) : bool{
		$isValid = (file_exists($path . "/level.dat") and is_dir($path . "/region/"));

		if($isValid){
			$files = array_filter(scandir($path . "/region/", SCANDIR_SORT_NONE), function($file){
				return substr($file, strrpos($file, ".") + 1, 2) === "mc"; //region file
			});

			foreach($files as $f){
				if(substr($f, strrpos($f, ".") + 1) !== static::REGION_FILE_EXTENSION){
					$isValid = false;
					break;
				}
			}
		}

		return $isValid;
	}

	public static function generate(string $path, string $name, int $seed, string $generator, array $options = []){
		if(!file_exists($path)){
			mkdir($path, 0777, true);
		}

		if(!file_exists($path . "/region")){
			mkdir($path . "/region", 0777);
		}
		//TODO, add extra details
		$levelData = CompoundTag::create()
            ->setByte("hardcore", ($options["hardcore"] ?? false) === true ? 1 : 0)
			->setByte("Difficulty", Level::getDifficultyFromString((string) ($options["difficulty"] ?? "normal")))
			->setByte("initialized", 1)
			->setInt("GameType", 0)
			->setInt("generatorVersion", 1) //2 in MCPE
			->setInt("SpawnX", 256)
			->setInt("SpawnY", 70)
			->setInt("SpawnZ", 256)
			->setInt("version", static::getPcWorldFormatVersion())
			->setInt("DayTime", 0)
			->setLong("LastPlayed", (int) (microtime(true) * 1000))
			->setLong("RandomSeed", $seed)
			->setLong("SizeOnDisk", 0)
			->setLong("Time", 0)
			->setString("generatorName", Generator::getGeneratorName($generator))
			->setString("generatorOptions", $options["preset"] ?? "")
			->setString("LevelName", $name)
			->setTag("GameRules", new CompoundTag());

		$tag = CompoundTag::create()->setTag("Data", $levelData);
		$decompressed = (new BigEndianNbtSerializer())->write(new TreeRoot($tag));
		$buffer = Zlib::compress($decompressed, ZLIB_ENCODING_GZIP);
		file_put_contents($path . "level.dat", $buffer);
	}

    public function getDifficulty() : int{
        return $this->levelData->getByte("Difficulty", Level::DIFFICULTY_NORMAL);
    }

    public function setDifficulty(int $difficulty): void{
        $this->levelData->setByte("Difficulty", $difficulty);
    }

	public function getGenerator() : string{
		return $this->levelData->getString("generatorName");
	}

	public function getGeneratorOptions() : array{
		return ["preset" => $this->levelData->getString("generatorOptions")];
	}

	public function getChunk(int $chunkX, int $chunkZ, bool $create = false){
		$index = Level::chunkHash($chunkX, $chunkZ);
		if(isset($this->chunks[$index])){
			return $this->chunks[$index];
		}else{
			$this->loadChunk($chunkX, $chunkZ, $create);

			return $this->chunks[$index] ?? null;
		}
	}

	public function setChunk(int $chunkX, int $chunkZ, Chunk $chunk){
		self::getRegionIndex($chunkX, $chunkZ, $regionX, $regionZ);
		$this->loadRegion($regionX, $regionZ);

		$chunk->setX($chunkX);
		$chunk->setZ($chunkZ);


		if(isset($this->chunks[$index = Level::chunkHash($chunkX, $chunkZ)]) and $this->chunks[$index] !== $chunk){
			$this->unloadChunk($chunkX, $chunkZ, false);
		}

		$this->chunks[$index] = $chunk;
	}

	public function saveChunk(int $chunkX, int $chunkZ) : bool{
		if($this->isChunkLoaded($chunkX, $chunkZ)){
			$chunk = $this->getChunk($chunkX, $chunkZ);
			if(!$chunk->isGenerated()){
				throw new \InvalidStateException("Cannot save un-generated chunk");
			}
			$this->getRegion($chunkX >> 5, $chunkZ >> 5)->writeChunk($chunk);

			return true;
		}

		return false;
	}

	public function saveChunks(){
		foreach($this->chunks as $chunk){
			$this->saveChunk($chunk->getX(), $chunk->getZ());
		}
	}

	public function loadChunk(int $chunkX, int $chunkZ, bool $create = false) : bool{
		$index = Level::chunkHash($chunkX, $chunkZ);
		if(isset($this->chunks[$index])){
			return true;
		}
		$regionX = $regionZ = null;
		self::getRegionIndex($chunkX, $chunkZ, $regionX, $regionZ);
		/** @noinspection PhpStrictTypeCheckingInspection */
		$this->loadRegion($regionX, $regionZ);
		$this->level->timings->syncChunkLoadDataTimer->startTiming();
		/** @noinspection PhpStrictTypeCheckingInspection */
		$chunk = $this->getRegion($regionX, $regionZ)->readChunk($chunkX - $regionX * 32, $chunkZ - $regionZ * 32);
		if($chunk === null and $create){
			$chunk = $this->getEmptyChunk($chunkX, $chunkZ);
		}
		$this->level->timings->syncChunkLoadDataTimer->stopTiming();

		if($chunk !== null){
			$this->chunks[$index] = $chunk;
			return true;
		}else{
			return false;
		}
	}

	public function unloadChunk(int $chunkX, int $chunkZ, bool $safe = true) : bool{
		$chunk = $this->chunks[$index = Level::chunkHash($chunkX, $chunkZ)] ?? null;
		if($chunk instanceof Chunk and $chunk->unload($safe)){
			unset($this->chunks[$index]);
			return true;
		}

		return false;
	}

	public function unloadChunks(){
		foreach($this->chunks as $chunk){
			$this->unloadChunk($chunk->getX(), $chunk->getZ(), false);
		}
		$this->chunks = [];
	}

	public function isChunkLoaded(int $chunkX, int $chunkZ) : bool{
		return isset($this->chunks[Level::chunkHash($chunkX, $chunkZ)]);
	}

	public function isChunkGenerated(int $chunkX, int $chunkZ) : bool{
		if(($region = $this->getRegion($chunkX >> 5, $chunkZ >> 5)) !== null){
			return $region->chunkExists($chunkX - $region->getX() * 32, $chunkZ - $region->getZ() * 32) and $this->getChunk($chunkX - $region->getX() * 32, $chunkZ - $region->getZ() * 32, true)->isGenerated();
		}

		return false;
	}

	public function isChunkPopulated(int $chunkX, int $chunkZ) : bool{
		$chunk = $this->getChunk($chunkX, $chunkZ);
		if($chunk !== null){
			return $chunk->isPopulated();
		}else{
			return false;
		}
	}

	public function getLoadedChunks() : array{
		return $this->chunks;
	}

	public function doGarbageCollection(){
		$limit = time() - 300;
		foreach($this->regions as $index => $region){
			if($region->lastUsed <= $limit){
				$region->close();
				unset($this->regions[$index]);
			}
		}
		Utils::reallocateArray($this->regions);
	}

	/**
	 * @param int $chunkX
	 * @param int $chunkZ
	 * @param int &$x
	 * @param int &$z
	 */
	public static function getRegionIndex(int $chunkX, int $chunkZ, &$x, &$z){
		$x = $chunkX >> 5;
		$z = $chunkZ >> 5;
	}

	/**
	 * @param int $chunkX
	 * @param int $chunkZ
	 *
	 * @return Chunk
	 */
	public function getEmptyChunk(int $chunkX, int $chunkZ) : Chunk{
		return Chunk::getEmptyChunk($chunkX, $chunkZ);
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return RegionLoader|null
	 */
	protected function getRegion(int $x, int $z){
		return $this->regions[Level::chunkHash($x, $z)] ?? null;
	}

	/**
	 * @param int $x
	 * @param int $z
	 */
	protected function loadRegion(int $x, int $z){
		if(!isset($this->regions[$index = Level::chunkHash($x, $z)])){
			$this->regions[$index] = new RegionLoader($this, $x, $z, static::REGION_FILE_EXTENSION);
			try{
				$this->regions[$index]->open();
			}catch(CorruptedRegionException $e){
				$logger = $this->level->getServer()->getLogger();
				$logger->error("Corrupted region file detected: " . $e->getMessage());

				$this->regions[$index]->close(false); //Do not write anything to the file

				$path = $this->regions[$index]->getFilePath();
				$backupPath = $path . ".bak." . time();
				rename($path, $backupPath);
				$logger->error("Corrupted region file has been backed up to " . $backupPath);

				$this->regions[$index] = new RegionLoader($this, $x, $z, static::REGION_FILE_EXTENSION);
				$this->regions[$index]->open(); //this will create a new empty region to replace the corrupted one
			}
		}
	}

	public function close(){
		$this->unloadChunks();
		foreach($this->regions as $index => $region){
			$region->close();
		}
		$this->regions = [];
		$this->level = null;
	}
}


