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

namespace pocketmine\level\format\io\leveldb;

use pocketmine\level\format\Chunk;
use pocketmine\level\format\io\BaseLevelProvider;
use pocketmine\level\format\io\ChunkUtils;
use pocketmine\level\format\io\exception\UnsupportedChunkFormatException;
use pocketmine\level\format\SubChunk;
use pocketmine\level\generator\Flat;
use pocketmine\level\generator\Generator;
use pocketmine\level\Level;
use pocketmine\level\LevelException;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\tag\{CompoundTag, IntTag, StringTag};
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\utils\Binary;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\MainLogger;
use Webmozart\PathUtil\Path;
use function array_map;
use function array_values;
use function chr;
use function count;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function ord;
use function pack;
use function rtrim;
use function strlen;
use function substr;
use function time;
use function trim;
use function unpack;

class LevelDB extends BaseLevelProvider{

	//According to Tomasso, these aren't supposed to be readable anymore. Thankfully he didn't change the readable ones...
	public const TAG_DATA_2D = "\x2d";
	public const TAG_DATA_2D_LEGACY = "\x2e";
	public const TAG_SUBCHUNK_PREFIX = "\x2f";
	public const TAG_LEGACY_TERRAIN = "0";
	public const TAG_BLOCK_ENTITY = "1";
	public const TAG_ENTITY = "2";
	public const TAG_PENDING_TICK = "3";
	public const TAG_BLOCK_EXTRA_DATA = "4";
	public const TAG_BIOME_STATE = "5";
	public const TAG_STATE_FINALISATION = "6";

	public const FINALISATION_NEEDS_INSTATICKING = 0;
	public const FINALISATION_NEEDS_POPULATION = 1;
	public const FINALISATION_DONE = 2;

	public const TAG_VERSION = "v";

	public const ENTRY_FLAT_WORLD_LAYERS = "game_flatworldlayers";

	public const GENERATOR_LIMITED = 0;
	public const GENERATOR_INFINITE = 1;
	public const GENERATOR_FLAT = 2;

	public const CURRENT_STORAGE_VERSION = 5; //Current MCPE level format version
	public const CURRENT_LEVEL_CHUNK_VERSION = 4;
	public const CURRENT_LEVEL_SUBCHUNK_VERSION = 0;

	/** @var Chunk[] */
	protected $chunks = [];

	/** @var \LevelDB */
	protected $db;

	public function __construct(Level $level, string $path){
		$this->level = $level;
		$this->path = $path;
		if(!file_exists($this->path)){
			mkdir($this->path, 0777, true);
		}

		$rawLevelData = file_get_contents($this->getPath() . "level.dat");
		if($rawLevelData === false){
			throw new LevelException("Failed to read level.dat (permission denied or doesn't exist)");
		}

		if(strlen($rawLevelData) <= 8){
			throw new LevelException("Truncated level.dat");
		}

		try{
			$this->levelData = (new LittleEndianNbtSerializer())->read(substr($rawLevelData, 8))->mustGetCompoundTag();
		}catch(NbtDataException $e){
			throw new LevelException($e->getMessage(), 0, $e);
		}

		$this->db = new \LevelDB(Path::join($path, "db"), [
			"compression" => LEVELDB_ZLIB_RAW_COMPRESSION,
			"block_size" => 64 * 1024 //64KB, big enough for most chunks
		]);

		/*if($this->levelData->getInt("StorageVersion", INT32_MAX) > self::CURRENT_STORAGE_VERSION){
			throw new LevelException("Specified LevelDB world format version is newer than the version supported by the server");
		}*/

		if(!$this->levelData->hasTag("generatorName", StringTag::class)){
			if($this->levelData->hasTag("Generator", IntTag::class)){
				switch($this->levelData->getInt("Generator")){ //Detect correct generator from MCPE data
					case self::GENERATOR_FLAT:
						$this->levelData->setString("generatorName", (string) Generator::getGenerator("FLAT"));
						if(($layers = $this->db->get(self::ENTRY_FLAT_WORLD_LAYERS)) !== false){ //Detect existing custom flat layers
							$layers = trim($layers, "[]");
						}else{
							$layers = "7,3,3,2";
						}
						$this->levelData->setString("generatorOptions", "2;" . $layers . ";1");
						break;
					case self::GENERATOR_INFINITE:
						//TODO: add a null generator which does not generate missing chunks (to allow importing back to MCPE and generating more normal terrain without PocketMine messing things up)
						$this->levelData->setString("generatorName", (string) Generator::getGenerator("DEFAULT"));
						$this->levelData->setString("generatorOptions", "");
						break;
					case self::GENERATOR_LIMITED:
						throw new LevelException("Limited worlds are not currently supported");
					default:
						throw new LevelException("Unknown LevelDB world format type, this level cannot be loaded");
				}
			}else{
				$this->levelData->setString("generatorName", (string) Generator::getGenerator("DEFAULT"));
			}
		}

		if(!$this->levelData->hasTag("generatorOptions", StringTag::class)){
			$this->levelData->setString("generatorOptions", "");
		}
	}

	public static function getProviderName() : string{
		return "leveldb";
	}

	public function getWorldHeight() : int{
		return 256;
	}

	public static function isValid(string $path) : bool{
		return file_exists($path . "/level.dat") and is_dir($path . "/db/");
	}

	public static function generate(string $path, string $name, int $seed, string $generator, array $options = []){
		if(!file_exists($path)){
			mkdir($path, 0777, true);
		}

		if(!file_exists($path . "/db")){
			mkdir($path . "/db", 0777, true);
		}

		switch($generator){
			case Flat::class:
				$generatorType = self::GENERATOR_FLAT;
				break;
			default:
				$generatorType = self::GENERATOR_INFINITE;
			//TODO: add support for limited worlds
		}

		$levelData = CompoundTag::create()
			//Vanilla fields
			->setInt("DayCycleStopTime", -1)
            ->setInt("Difficulty", Level::getDifficultyFromString((string) ($options["difficulty"] ?? "normal")))
			->setByte("ForceGameType", 0)
			->setInt("GameType", 0)
			->setInt("Generator", $generatorType)
			->setLong("LastPlayed", time())
			->setString("LevelName", $name)
			->setInt("NetworkVersion", ProtocolInfo::CURRENT_PROTOCOL)
			//->setInt("Platform", 2), //TODO: find out what the possible values are for
			->setLong("RandomSeed", $seed)
			->setInt("SpawnX", 0)
			->setInt("SpawnY", 32767)
			->setInt("SpawnZ", 0)
			->setInt("StorageVersion", self::CURRENT_STORAGE_VERSION)
			->setLong("Time", 0)
			->setByte("eduLevel", 0)
			->setByte("falldamage", 1)
			->setByte("firedamage", 1)
			->setByte("hasBeenLoadedInCreative", 1) //badly named, this actually determines whether achievements can be earned in this world...
			->setByte("immutableWorld", 0)
			->setFloat("lightningLevel", 0.0)
			->setInt("lightningTime", 0)
			->setByte("pvp", 1)
			->setFloat("rainLevel", 0.0)
			->setInt("rainTime", 0)
			->setByte("spawnMobs", 1)
			->setByte("texturePacksRequired", 0) //TODO

			//Additional PocketMine-MP fields
			->setTag("GameRules", new CompoundTag())
			->setByte("hardcore", ($options["hardcore"] ?? false) === true ? 1 : 0)
			->setString("generatorName", Generator::getGeneratorName($generator))
			->setString("generatorOptions", $options["preset"] ?? "");

		$buffer = (new LittleEndianNbtSerializer())->write(new TreeRoot($levelData));
		file_put_contents($path . "level.dat", Binary::writeLInt(self::CURRENT_STORAGE_VERSION) . Binary::writeLInt(strlen($buffer)) . $buffer);

		$db = new \LevelDB($path . "/db", [
			"compression" => LEVELDB_ZLIB_COMPRESSION
		]);

		if($generatorType === self::GENERATOR_FLAT and isset($options["preset"])){
			$layers = explode(";", $options["preset"])[1] ?? "";
			if($layers !== ""){
				$out = "[";
				foreach(Flat::parseLayers($layers) as $result){
					$out .= $result[0] . ","; //only id, meta will unfortunately not survive :(
				}
				$out = rtrim($out, ",") . "]"; //remove trailing comma
				$db->put(self::ENTRY_FLAT_WORLD_LAYERS, $out); //Add vanilla flatworld layers to allow terrain generation by MCPE to continue seamlessly
			}
		}

		$db->close();

	}

	public function saveLevelData(){
		$this->levelData->setInt("NetworkVersion", ProtocolInfo::CURRENT_PROTOCOL);
		$this->levelData->setInt("StorageVersion", self::CURRENT_STORAGE_VERSION);

		$buffer = (new LittleEndianNbtSerializer())->write(new TreeRoot($this->levelData));
		file_put_contents($this->getPath() . "level.dat", Binary::writeLInt(self::CURRENT_STORAGE_VERSION) . Binary::writeLInt(strlen($buffer)) . $buffer);
	}

    public function getDifficulty() : int{
        return $this->levelData->getByte("Difficulty", Level::DIFFICULTY_NORMAL);
    }

    public function setDifficulty(int $difficulty): void{
        $this->levelData->setByte("Difficulty", $difficulty);
    }

	public function unloadChunks(){
		foreach($this->chunks as $chunk){
			$this->unloadChunk($chunk->getX(), $chunk->getZ(), false);
		}
		$this->chunks = [];
	}

	public function getGenerator() : string{
		return $this->levelData->getString("generatorName");
	}

	public function getGeneratorOptions() : array{
		return ["preset" => $this->levelData->getString("generatorOptions")];
	}

	public function getLoadedChunks() : array{
		return $this->chunks;
	}

	public function isChunkLoaded(int $x, int $z) : bool{
		return isset($this->chunks[Level::chunkHash($x, $z)]);
	}

	public function saveChunks(){
		foreach($this->chunks as $chunk){
			$this->saveChunk($chunk->getX(), $chunk->getZ());
		}
	}

	public function loadChunk(int $chunkX, int $chunkZ, bool $create = false) : bool{
		if(isset($this->chunks[$index = Level::chunkHash($chunkX, $chunkZ)])){
			return true;
		}

		$this->level->timings->syncChunkLoadDataTimer->startTiming();
		$chunk = $this->readChunk($chunkX, $chunkZ);
		if($chunk === null and $create){
			$chunk = Chunk::getEmptyChunk($chunkX, $chunkZ);
		}
		$this->level->timings->syncChunkLoadDataTimer->stopTiming();

		if($chunk !== null){
			$this->chunks[$index] = $chunk;

			return true;
		}else{
			return false;
		}
	}

	/**
	 * @param int $chunkX
	 * @param int $chunkZ
	 *
	 * @return Chunk|null
	 */
	private function readChunk($chunkX, $chunkZ){
		$index = LevelDB::chunkIndex($chunkX, $chunkZ);

		if(!$this->chunkExists($chunkX, $chunkZ)){
			return null;
		}

		try{
			/** @var SubChunk[] $subChunks */
			$subChunks = [];

			/** @var bool $lightPopulated */
			$lightPopulated = true;

			$chunkVersion = ord($this->db->get($index . self::TAG_VERSION));

			$binaryStream = new BinaryStream();

			switch($chunkVersion){
				case 4: //MCPE 1.1
					//TODO: check beds
				case 3: //MCPE 1.0
					for($y = 0; $y < Chunk::MAX_SUBCHUNKS; ++$y){
						if(($data = $this->db->get($index . self::TAG_SUBCHUNK_PREFIX . chr($y))) === false){
							continue;
						}

						$binaryStream->setBuffer($data, 0);
						$subChunkVersion = $binaryStream->getByte();

						switch($subChunkVersion){
							case 0:
								$blocks = $binaryStream->get(4096);
								$blockData = $binaryStream->get(2048);
								if($chunkVersion < 4){
									$blockSkyLight = $binaryStream->get(2048);
									$blockLight = $binaryStream->get(2048);
								}else{
									//Mojang didn't bother changing the subchunk version when they stopped saving sky light -_-
									$blockSkyLight = "";
									$blockLight = "";
									$lightPopulated = false;
								}

								$subChunks[$y] = new SubChunk($blocks, $blockData, $blockSkyLight, $blockLight);
								break;
							default:
								throw new UnsupportedChunkFormatException("don't know how to decode LevelDB subchunk format version $subChunkVersion");
						}
					}

					$binaryStream->setBuffer($this->db->get($index . self::TAG_DATA_2D), 0);

					$heightMap = array_values(unpack("v*", $binaryStream->get(512)));
					$biomeIds = $binaryStream->get(256);
					break;
				case 2: // < MCPE 1.0
					$binaryStream->setBuffer($this->db->get($index . self::TAG_LEGACY_TERRAIN));
					$fullIds = $binaryStream->get(32768);
					$fullData = $binaryStream->get(16384);
					$fullSkyLight = $binaryStream->get(16384);
					$fullBlockLight = $binaryStream->get(16384);

					for($yy = 0; $yy < 8; ++$yy){
						$subOffset = ($yy << 4);
						$ids = "";
						for($i = 0; $i < 256; ++$i){
							$ids .= substr($fullIds, $subOffset, 16);
							$subOffset += 128;
						}
						$data = "";
						$subOffset = ($yy << 3);
						for($i = 0; $i < 256; ++$i){
							$data .= substr($fullData, $subOffset, 8);
							$subOffset += 64;
						}
						$skyLight = "";
						$subOffset = ($yy << 3);
						for($i = 0; $i < 256; ++$i){
							$skyLight .= substr($fullSkyLight, $subOffset, 8);
							$subOffset += 64;
						}
						$blockLight = "";
						$subOffset = ($yy << 3);
						for($i = 0; $i < 256; ++$i){
							$blockLight .= substr($fullBlockLight, $subOffset, 8);
							$subOffset += 64;
						}
						$subChunks[$yy] = new SubChunk($ids, $data, $skyLight, $blockLight);
					}

					$heightMap = array_values(unpack("C*", $binaryStream->get(256)));
					$biomeIds = ChunkUtils::convertBiomeColors(array_values(unpack("N*", $binaryStream->get(1024))));
					break;
				default:
					throw new UnsupportedChunkFormatException("don't know how to decode chunk format version $chunkVersion");
			}

			$nbt = new LittleEndianNbtSerializer();

			$entities = [];
			if(($entityData = $this->db->get($index . self::TAG_ENTITY)) !== false and strlen($entityData) > 0){
				try{
					$entities = array_map(function(TreeRoot $root) : CompoundTag{
						return $root->mustGetCompoundTag();
					}, $nbt->readMultiple($entityData));
				}catch(NbtDataException $e){
					throw new LevelException($e->getMessage(), 0, $e);
				}
			}

			foreach($entities as $entityNBT){
				if($entityNBT->hasTag("id", IntTag::class)){
					$entityNBT->setInt("id", $entityNBT->getInt("id") & 0xff);
				}
			}

			$tiles = [];
			if(($tileData = $this->db->get($index . self::TAG_BLOCK_ENTITY)) !== false and strlen($tileData) > 0){
				try{
					$tiles = array_map(function(TreeRoot $root) : CompoundTag{
						return $root->mustGetCompoundTag();
					}, $nbt->readMultiple($tileData));
				}catch(NbtDataException $e){
					throw new LevelException($e->getMessage(), 0, $e);
				}
			}

			$extraData = [];
			if(($extraRawData = $this->db->get($index . self::TAG_BLOCK_EXTRA_DATA)) !== false and strlen($extraRawData) > 0){
				$binaryStream->setBuffer($extraRawData, 0);
				$count = $binaryStream->getLInt();
				for($i = 0; $i < $count; ++$i){
					$key = $binaryStream->getLInt();
					$value = $binaryStream->getLShort();
					$extraData[$key] = $value;
				}
			}

			$chunk = new Chunk(
				$chunkX,
				$chunkZ,
				$subChunks,
				$entities,
				$tiles,
				$biomeIds,
				$heightMap,
				$extraData
			);

			//TODO: tile ticks, biome states (?)

			$chunk->setGenerated(true);
			$chunk->setPopulated(true);
			$chunk->setLightPopulated($lightPopulated);

			return $chunk;
		}catch(UnsupportedChunkFormatException $e){
			//TODO: set chunks read-only so the version on disk doesn't get overwritten

			$logger = MainLogger::getLogger();
			$logger->error("Failed to decode LevelDB chunk: " . $e->getMessage());

			return null;
		}catch(\Throwable $t){
			$logger = MainLogger::getLogger();
			$logger->error("LevelDB chunk decode error");
			$logger->logException($t);

			return null;

		}
	}

	private function writeChunk(Chunk $chunk){
		$index = LevelDB::chunkIndex($chunk->getX(), $chunk->getZ());
		$this->db->put($index . self::TAG_VERSION, chr(self::CURRENT_LEVEL_CHUNK_VERSION));

		$subChunks = $chunk->getSubChunks();
		foreach($subChunks as $y => $subChunk){
			$key = $index . self::TAG_SUBCHUNK_PREFIX . chr($y);
			if($subChunk->isEmpty(false)){ //MCPE doesn't save light anymore as of 1.1
				$this->db->delete($key);
			}else{
				$this->db->put($key,
					chr(self::CURRENT_LEVEL_SUBCHUNK_VERSION) .
					$subChunks[$y]->getBlockIdArray() .
					$subChunks[$y]->getBlockDataArray()
				);
			}
		}

		$this->db->put($index . self::TAG_DATA_2D, pack("v*", ...$chunk->getHeightMapArray()) . $chunk->getBiomeIdArray());

		$extraData = $chunk->getBlockExtraDataArray();
		if(count($extraData) > 0){
			$stream = new BinaryStream();
			$stream->putLInt(count($extraData));
			foreach($extraData as $key => $value){
				$stream->putLInt($key);
				$stream->putLShort($value);
			}

			$this->db->put($index . self::TAG_BLOCK_EXTRA_DATA, $stream->getBuffer());
		}else{
			$this->db->delete($index . self::TAG_BLOCK_EXTRA_DATA);
		}

		//TODO: use this properly
		$this->db->put($index . self::TAG_STATE_FINALISATION, chr(self::FINALISATION_DONE));

        /** @var CompoundTag[] $tiles */
        $tiles = [];
        foreach($chunk->getTiles() as $tile){
            $tiles[] = $tile->saveNBT();
        }
        $this->writeTags($tiles, $index . self::TAG_BLOCK_ENTITY);

        /** @var CompoundTag[] $tiles */
        $entities = [];
        foreach($chunk->getEntities() as $tile){
            $tile->saveNBT();
            $entities[] = $tile->namedtag;
        }
        $this->writeTags($entities, $index . self::TAG_ENTITY);

		$this->db->delete($index . self::TAG_DATA_2D_LEGACY);
		$this->db->delete($index . self::TAG_LEGACY_TERRAIN);
	}

	/**
	 * @param CompoundTag[] $targets
	 * @param string          $index
	 */
	private function writeTags(array $targets, string $index){
		$nbt = new LittleEndianNbtSerializer();

		$out = [];
		foreach($targets as $target){
            $out[] = new TreeRoot($target);
		}

		if(!empty($targets)){
			$this->db->put($index, $nbt->writeMultiple($out));
		}else{
			$this->db->delete($index);
		}
	}

	public function unloadChunk(int $x, int $z, bool $safe = true) : bool{
		$chunk = $this->chunks[$index = Level::chunkHash($x, $z)] ?? null;
		if($chunk instanceof Chunk and $chunk->unload($safe)){
			unset($this->chunks[$index]);

			return true;
		}

		return false;
	}

	public function saveChunk(int $chunkX, int $chunkZ) : bool{
		if($this->isChunkLoaded($chunkX, $chunkZ)){
			$chunk = $this->getChunk($chunkX, $chunkZ);
			if(!$chunk->isGenerated()){
				throw new \InvalidStateException("Cannot save un-generated chunk");
			}
			$this->writeChunk($chunk);

			return true;
		}

		return false;
	}

	/**
	 * @param int $chunkX
	 * @param int $chunkZ
	 * @param bool $create
	 *
	 * @return Chunk|null
	 */
	public function getChunk(int $chunkX, int $chunkZ, bool $create = false){
		$index = Level::chunkHash($chunkX, $chunkZ);
		if(isset($this->chunks[$index])){
			return $this->chunks[$index];
		}else{
			$this->loadChunk($chunkX, $chunkZ, $create);

			return $this->chunks[$index] ?? null;
		}
	}

	/**
	 * @return \LevelDB
	 */
	public function getDatabase() : \LevelDB{
		return $this->db;
	}

	public function setChunk(int $chunkX, int $chunkZ, Chunk $chunk){
		$chunk->setX($chunkX);
		$chunk->setZ($chunkZ);

		if(isset($this->chunks[$index = Level::chunkHash($chunkX, $chunkZ)]) and $this->chunks[$index] !== $chunk){
			$this->unloadChunk($chunkX, $chunkZ, false);
		}

		$this->chunks[$index] = $chunk;
	}

	public static function chunkIndex(int $chunkX, int $chunkZ) : string{
		return Binary::writeLInt($chunkX) . Binary::writeLInt($chunkZ);
	}

	private function chunkExists(int $chunkX, int $chunkZ) : bool{
		return $this->db->get(LevelDB::chunkIndex($chunkX, $chunkZ) . self::TAG_VERSION) !== false;
	}

	public function isChunkGenerated(int $chunkX, int $chunkZ) : bool{
		if($this->chunkExists($chunkX, $chunkZ) and ($chunk = $this->getChunk($chunkX, $chunkZ, false)) !== null){
			return true;
		}

		return false;
	}

	public function isChunkPopulated(int $chunkX, int $chunkZ) : bool{
		$chunk = $this->getChunk($chunkX, $chunkZ);
		if($chunk instanceof Chunk){
			return $chunk->isPopulated();
		}else{
			return false;
		}
	}

	public function close(){
		$this->unloadChunks();
		$this->db->close();
		$this->level = null;
	}
}


