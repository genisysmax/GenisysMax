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



/**
 * Implementation of MCPE-style chunks with subchunks with XZY ordering.
 */
declare(strict_types=1);

namespace pocketmine\level\format;

use pocketmine\block\BlockFactory;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Tile;
use pocketmine\utils\BinaryStream;
use function array_fill;
use function array_values;
use function assert;
use function chr;
use function count;
use function ord;
use function pack;
use function str_repeat;
use function strlen;
use function unpack;

class Chunk{

	public const MAX_SUBCHUNKS = 16;

	protected $x;
	protected $z;

	protected $hasChanged = false;

	protected $isInit = false;

	protected $lightPopulated = false;
	protected $terrainGenerated = false;
	protected $terrainPopulated = false;

	protected $height = Chunk::MAX_SUBCHUNKS;

	/** @var SubChunkInterface[] */
	protected $subChunks = [];

	/** @var EmptySubChunk */
	protected $emptySubChunk = null;

	/** @var int|null */
	protected $subChunkSendCount;

	/** @var Tile[] */
	protected $tiles = [];
	protected $tileList = [];

	/** @var Entity[] */
	protected $entities = [];

	/** @var \SplFixedArray|int[] */
	protected $heightMap;

	/** @var string */
	protected $biomeIds;

	/** @var int[] */
	protected $extraData = [];

	/** @var CompoundTag[] */
	protected $NBTtiles = [];

	/** @var CompoundTag[] */
	protected $NBTentities = [];

	/** @var array */
	protected $customData = [];

	protected bool $protect = false;

	/**
	 * @param int                 $chunkX
	 * @param int                 $chunkZ
	 * @param SubChunkInterface[] $subChunks
	 * @param CompoundTag[]       $entities
	 * @param CompoundTag[]       $tiles
	 * @param string              $biomeIds
	 * @param int[]               $heightMap
	 * @param int[]               $extraData
	 */
	public function __construct(int $chunkX, int $chunkZ, array $subChunks = [], array $entities = [], array $tiles = [], string $biomeIds = "", array $heightMap = [], array $extraData = []){
		$this->x = $chunkX;
		$this->z = $chunkZ;

		$this->height = Chunk::MAX_SUBCHUNKS; //TODO: add a way of changing this

		$this->emptySubChunk = new EmptySubChunk();

		foreach($subChunks as $y => $subChunk){
			if($y < 0 or $y >= $this->height){
				throw new ChunkException("Invalid subchunk index $y!");
			}
			if($subChunk->isEmpty()){
				$this->subChunks[$y] = $this->emptySubChunk;
			}else{
				$this->subChunks[$y] = $subChunk;
			}
		}

		for($i = 0; $i < $this->height; ++$i){
			if(!isset($this->subChunks[$i])){
				$this->subChunks[$i] = $this->emptySubChunk;
			}
		}

		if(count($heightMap) === 256){
			$this->heightMap = \SplFixedArray::fromArray($heightMap);
		}else{
			assert(count($heightMap) === 0, "Wrong HeightMap value count, expected 256, got " . count($heightMap));
			$val = $this->height * 16;
			$this->heightMap = \SplFixedArray::fromArray(array_fill(0, 256, $val));
		}

		if(strlen($biomeIds) === 256){
			$this->biomeIds = $biomeIds;
		}else{
			assert($biomeIds === "", "Wrong BiomeIds value count, expected 256, got " . strlen($biomeIds));
			$this->biomeIds = str_repeat("\x00", 256);
		}

		$this->extraData = $extraData;

		$this->NBTtiles = $tiles;
		$this->NBTentities = $entities;
	}

	public function setProtect(bool $protect = true) : void{
		$this->protect = $protect;
	}

	public function isProtected() : bool{
		return $this->protect;
	}

	/**
	 * @return int
	 */
	public function getX() : int{
		return $this->x;
	}

	/**
	 * @return int
	 */
	public function getZ() : int{
		return $this->z;
	}

	public function setX(int $x){
		$this->x = $x;
	}

	/**
	 * @param int $z
	 */
	public function setZ(int $z){
		$this->z = $z;
	}

	/**
	 * Returns the chunk height in count of subchunks.
	 *
	 * @return int
	 */
	public function getHeight() : int{
		return $this->height;
	}

	/**
	 * Returns a bitmap of block ID and meta at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y
	 * @param int $z 0-15
	 *
	 * @return int bitmap, (id << 4) | meta
	 */
	public function getFullBlock(int $x, int $y, int $z) : int{
		return $this->getSubChunk($y >> 4)->getFullBlock($x, $y & 0x0f, $z);
	}

	/**
	 * Sets block ID and meta in one call at the specified chunk block coordinates
	 *
	 * @param int      $x 0-15
	 * @param int      $y
	 * @param int      $z 0-15
	 * @param int|null $blockId 0-255 if null, does not change
	 * @param int|null $meta 0-15 if null, does not change
	 *
	 * @return bool
	 */
	public function setBlock(int $x, int $y, int $z, $blockId = null, $meta = null) : bool{
		if($this->getSubChunk($y >> 4, true)->setBlock($x, $y & 0x0f, $z, $blockId !== null ? ($blockId & 0xff) : null, $meta !== null ? ($meta & 0x0f) : null)){
			$this->setChanged();
			return true;
		}
		return false;
	}

	/**
	 * Returns the block ID at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y
	 * @param int $z 0-15
	 *
	 * @return int 0-255
	 */
	public function getBlockId(int $x, int $y, int $z) : int{
		return $this->getSubChunk($y >> 4)->getBlockId($x, $y & 0x0f, $z);
	}

	/**
	 * Sets the block ID at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y
	 * @param int $z 0-15
	 * @param int $id 0-255
	 */
	public function setBlockId(int $x, int $y, int $z, int $id){
		if($this->getSubChunk($y >> 4, true)->setBlockId($x, $y & 0x0f, $z, $id)){
			$this->setChanged();
		}
	}

	/**
	 * Returns the block meta value at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y
	 * @param int $z 0-15
	 *
	 * @return int 0-15
	 */
	public function getBlockData(int $x, int $y, int $z) : int{
		return $this->getSubChunk($y >> 4)->getBlockData($x, $y & 0x0f, $z);
	}

	/**
	 * Sets the block meta value at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y
	 * @param int $z 0-15
	 * @param int $data 0-15
	 */
	public function setBlockData(int $x, int $y, int $z, int $data){
		if($this->getSubChunk($y >> 4)->setBlockData($x, $y & 0x0f, $z, $data)){
			$this->setChanged();
		}
	}

	/**
	 * Returns the raw block extra data value at the specified chunk block coordinates, or 0 if no data exists
	 *
	 * @param int $x 0-15
	 * @param int $y
	 * @param int $z 0-15
	 *
	 * @return int bitmap, (meta << 8) | id
	 */
	public function getBlockExtraData(int $x, int $y, int $z) : int{
		return $this->extraData[Chunk::chunkBlockHash($x, $y, $z)] ?? 0;
	}

	/**
	 * Sets the raw block extra data value at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y
	 * @param int $z 0-15
	 * @param int $data bitmap, (meta << 8) | id
	 */
	public function setBlockExtraData(int $x, int $y, int $z, int $data){
		if($data === 0){
			unset($this->extraData[Chunk::chunkBlockHash($x, $y, $z)]);
		}else{
			$this->extraData[Chunk::chunkBlockHash($x, $y, $z)] = $data;
		}

		$this->setChanged();
	}

	/**
	 * Returns the sky light level at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y
	 * @param int $z 0-15
	 *
	 * @return int 0-15
	 */
	public function getBlockSkyLight(int $x, int $y, int $z) : int{
		return $this->getSubChunk($y >> 4)->getBlockSkyLight($x, $y & 0x0f, $z);
	}

	/**
	 * Sets the sky light level at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y
	 * @param int $z 0-15
	 * @param int $level 0-15
	 */
	public function setBlockSkyLight(int $x, int $y, int $z, int $level){
		if($this->getSubChunk($y >> 4, true)->setBlockSkyLight($x, $y & 0x0f, $z, $level)){
			$this->setChanged();
		}
	}

	/**
	 * @param int $level
	 */
	public function setAllBlockSkyLight(int $level){
		$char = chr(($level & 0x0f) | ($level << 4));
		$data = str_repeat($char, 2048);
		for($y = $this->getHighestSubChunkIndex(); $y >= 0; --$y){
			$this->getSubChunk($y, true)->setBlockSkyLightArray($data);
		}
	}

	/**
	 * Returns the block light level at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y 0-15
	 * @param int $z 0-15
	 *
	 * @return int 0-15
	 */
	public function getBlockLight(int $x, int $y, int $z) : int{
		return $this->getSubChunk($y >> 4)->getBlockLight($x, $y & 0x0f, $z);
	}

	/**
	 * Sets the block light level at the specified chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $y 0-15
	 * @param int $z 0-15
	 * @param int $level 0-15
	 */
	public function setBlockLight(int $x, int $y, int $z, int $level){
		if($this->getSubChunk($y >> 4, true)->setBlockLight($x, $y & 0x0f, $z, $level)){
			$this->setChanged();
		}
	}

	/**
	 * @param int $level
	 */
	public function setAllBlockLight(int $level){
		$char = chr(($level & 0x0f) | ($level << 4));
		$data = str_repeat($char, 2048);
		for($y = $this->getHighestSubChunkIndex(); $y >= 0; --$y){
			$this->getSubChunk($y, true)->setBlockLightArray($data);
		}
	}

	/**
	 * Returns the Y coordinate of the highest non-air block at the specified X/Z chunk block coordinates
	 *
	 * @param int  $x 0-15
	 * @param int  $z 0-15
	 *
	 * @return int 0-255, or -1 if there are no blocks in the column
	 */
	public function getHighestBlockAt(int $x, int $z) : int{
		$index = $this->getHighestSubChunkIndex();
		if($index === -1){
			return -1;
		}

		$height = $index << 4;

		for($y = $index; $y >= 0; --$y){
			$height = $this->getSubChunk($y)->getHighestBlockAt($x, $z) | ($y << 4);
			if($height !== -1){
				return $height;
			}
		}

		return -1;
	}

	public function getMaxY() : int{
		return ($this->getHighestSubChunkIndex() << 4) | 0x0f;
	}

	/**
	 * Returns the heightmap value at the specified X/Z chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $z 0-15
	 *
	 * @return int
	 */
	public function getHeightMap(int $x, int $z) : int{
		return $this->heightMap[($z << 4) | $x];
	}

	/**
	 * Returns the heightmap value at the specified X/Z chunk block coordinates
	 * @param int $x 0-15
	 * @param int $z 0-15
	 * @param int $value
	 */
	public function setHeightMap(int $x, int $z, int $value){
		$this->heightMap[($z << 4) | $x] = $value;
	}

	/**
	 * Recalculates the heightmap for the whole chunk.
	 */
	public function recalculateHeightMap(){
		for($z = 0; $z < 16; ++$z){
			for($x = 0; $x < 16; ++$x){
				$this->recalculateHeightMapColumn($x, $z);
			}
		}
	}

	/**
	 * Recalculates the heightmap for the block column at the specified X/Z chunk coordinates
	 *
	 * @param int $x 0-15
	 * @param int $z 0-15
	 *
	 * @return int New calculated heightmap value (0-256 inclusive)
	 */
	public function recalculateHeightMapColumn(int $x, int $z) : int{
		$max = $this->getHighestBlockAt($x, $z);
		for($y = $max; $y >= 0; --$y){
			if(BlockFactory::$lightFilter[$id = $this->getBlockId($x, $y, $z)] > 1 or BlockFactory::$diffusesSkyLight[$id]){
				break;
			}
		}

		$this->setHeightMap($x, $z, $y + 1);
		return $y + 1;
	}

	/**
	 * Performs basic sky light population on the chunk.
	 * This does not cater for adjacent sky light, this performs direct sky light population only. This may cause some strange visual artifacts
	 * if the chunk is light-populated after being terrain-populated.
	 *
	 * TODO: fast adjacent light spread
	 */
	public function populateSkyLight()
    {
        $maxY = $this->getMaxY();

        $this->setAllBlockSkyLight(0);

        for ($x = 0; $x < 16; ++$x) {
            for ($z = 0; $z < 16; ++$z) {
                $heightMap = $this->getHeightMap($x, $z);

                for ($y = $maxY; $y >= $heightMap; --$y) {
                    $this->setBlockSkyLight($x, $y, $z, 15);
                }

                $light = 15;
                for (; $y >= 0; --$y) {
                    $light -= BlockFactory::$lightFilter[$this->getBlockId($x, $y, $z)];
                    if ($light <= 0) {
                        break;
                    }
                    $this->setBlockSkyLight($x, $y, $z, $light);
                }
            }
        }
    }

	/**
	 * Returns the biome ID at the specified X/Z chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $z 0-15
	 *
	 * @return int 0-255
	 */
	public function getBiomeId(int $x, int $z) : int{
		return ord($this->biomeIds[($z << 4) | $x]);
	}

	/**
	 * Sets the biome ID at the specified X/Z chunk block coordinates
	 *
	 * @param int $x 0-15
	 * @param int $z 0-15
	 * @param int $biomeId 0-255
	 */
	public function setBiomeId(int $x, int $z, int $biomeId){
		$this->setChanged();
		$this->biomeIds[($z << 4) | $x] = chr($biomeId & 0xff);
	}

	/**
	 * Returns a column of block IDs from bottom to top at the specified X/Z chunk block coordinates.
	 * @param int $x 0-15
	 * @param int $z 0-15
	 *
	 * @return string
	 */
	public function getBlockIdColumn(int $x, int $z) : string{
		$result = "";
		foreach($this->subChunks as $subChunk){
			$result .= $subChunk->getBlockIdColumn($x, $z);
		}
		return $result;
	}

	/**
	 * Returns a column of block meta values from bottom to top at the specified X/Z chunk block coordinates.
	 * @param int $x 0-15
	 * @param int $z 0-15
	 *
	 * @return string
	 */
	public function getBlockDataColumn(int $x, int $z) : string{
		$result = "";
		foreach($this->subChunks as $subChunk){
			$result .= $subChunk->getBlockDataColumn($x, $z);
		}
		return $result;
	}

	/**
	 * Returns a column of sky light values from bottom to top at the specified X/Z chunk block coordinates.
	 * @param int $x 0-15
	 * @param int $z 0-15
	 *
	 * @return string
	 */
	public function getBlockSkyLightColumn(int $x, int $z) : string{
		$result = "";
		foreach($this->subChunks as $subChunk){
			$result .= $subChunk->getBlockSkyLightColumn($x, $z);
		}
		return $result;
	}

	/**
	 * Returns a column of block light values from bottom to top at the specified X/Z chunk block coordinates.
	 * @param int $x 0-15
	 * @param int $z 0-15
	 *
	 * @return string
	 */
	public function getBlockLightColumn(int $x, int $z) : string{
		$result = "";
		foreach($this->subChunks as $subChunk){
			$result .= $subChunk->getBlockLightColumn($x, $z);
		}
		return $result;
	}

	/**
	 * @return bool
	 */
	public function isLightPopulated() : bool{
		return $this->lightPopulated;
	}

	/**
	 * @param bool $value
	 */
	public function setLightPopulated(bool $value = true){
		$this->lightPopulated = $value;
	}

	/**
	 * @return bool
	 */
	public function isPopulated() : bool{
		return $this->terrainPopulated;
	}

	/**
	 * @param bool $value
	 */
	public function setPopulated(bool $value = true){
		$this->terrainPopulated = $value;
	}

	/**
	 * @return bool
	 */
	public function isGenerated() : bool{
		return $this->terrainGenerated;
	}

	/**
	 * @param bool $value
	 */
	public function setGenerated(bool $value = true){
		$this->terrainGenerated = $value;
	}

	/**
	 * @param Entity $entity
	 */
	public function addEntity(Entity $entity){
		if($entity->closed){
			throw new \InvalidArgumentException("Attempted to add a garbage closed Entity to a chunk");
		}
		$this->entities[$entity->getId()] = $entity;
		if(!($entity instanceof Player) and $this->isInit){
			$this->setChanged();
		}
	}

	/**
	 * @param Entity $entity
	 */
	public function removeEntity(Entity $entity){
		unset($this->entities[$entity->getId()]);
		if(!($entity instanceof Player) and $this->isInit){
			$this->setChanged();
		}
	}

	/**
	 * @param Tile $tile
	 */
	public function addTile(Tile $tile){
		if($tile->closed){
			throw new \InvalidArgumentException("Attempted to add a garbage closed Tile to a chunk");
		}
		$this->tiles[$tile->getId()] = $tile;
		if(isset($this->tileList[$index = (($tile->x & 0x0f) << 12) | (($tile->z & 0x0f) << 8) | ($tile->y & 0xff)]) and $this->tileList[$index] !== $tile){
			$this->tileList[$index]->close();
		}
		$this->tileList[$index] = $tile;
		if($this->isInit){
			$this->setChanged();
		}
	}

	/**
	 * @param Tile $tile
	 */
	public function removeTile(Tile $tile){
		unset($this->tiles[$tile->getId()]);
		unset($this->tileList[(($tile->x & 0x0f) << 12) | (($tile->z & 0x0f) << 8) | ($tile->y & 0xff)]);
		if($this->isInit){
			$this->setChanged();;
		}
	}

	/**
	 * Returns an array of entities currently using this chunk.
	 *
	 * @return Entity[]
	 */
	public function getEntities() : array{
		return $this->entities;
	}

	/**
	 * @return Tile[]
	 */
	public function getTiles() : array{
		return $this->tiles;
	}

	/**
	 * Returns the tile at the specified chunk block coordinates, or null if no tile exists.
	 *
	 * @param int $x 0-15
	 * @param int $y
	 * @param int $z 0-15
	 *
	 * @return Tile|null
	 */
	public function getTile(int $x, int $y, int $z){
		$index = ($x << 12) | ($z << 8) | $y;
		return $this->tileList[$index] ?? null;
	}

	/**
	 * Unloads the chunk, closing entities and tiles.
	 *
	 * @param bool $safe Whether to check if there are still players using this chunk
	 *
	 * @return bool
	 */
	public function unload(bool $safe = true) : bool{
		if($safe){
			foreach($this->getEntities() as $entity){
				if($entity instanceof Player){
					return false;
				}
			}
		}

		foreach($this->getEntities() as $entity){
			if($entity instanceof Player){
				continue;
			}
			$entity->close();
		}

		foreach($this->getTiles() as $tile){
			$tile->close();
		}

		return true;
	}

	/**
	 * Deserializes tiles and entities from NBT
	 *
	 * @param Level $level
	 */
	public function initChunk(Level $level){
		if(!$this->isInit){
			$changed = false;
			if($this->NBTentities !== null){
				$level->timings->syncChunkLoadEntitiesTimer->startTiming();
				foreach($this->NBTentities as $nbt){
					if($nbt instanceof CompoundTag){
						$idTag = $nbt->getTag("id");
						if(!($idTag instanceof IntTag) && !($idTag instanceof StringTag)){ //allow mixed types (because of leveldb)
							$changed = true;
							continue;
						}

						try{
							$entity = Entity::createEntity($idTag->getValue(), $level, $nbt);
							if(!($entity instanceof Entity)){
								$changed = true;
								continue;
							}
						}catch(\Throwable $t){
							$level->getServer()->getLogger()->logException($t);
							$changed = true;
							continue;
						}
					}
				}
				$level->timings->syncChunkLoadEntitiesTimer->stopTiming();

				$level->timings->syncChunkLoadTileEntitiesTimer->startTiming();
				foreach($this->NBTtiles as $nbt){
					if($nbt instanceof CompoundTag){
						if(!$nbt->hasTag("id", StringTag::class)){
							$changed = true;
							continue;
						}

						if(Tile::createTile($nbt->getString("id"), $level, $nbt) === null){
							$changed = true;
							continue;
						}
					}
				}

				$level->timings->syncChunkLoadTileEntitiesTimer->stopTiming();

				$this->NBTentities = null;
				$this->NBTtiles = null;
			}

			$this->hasChanged = $changed;

			$this->isInit = true;
		}
	}

	/**
	 * @return string
	 */
	public function getBiomeIdArray() : string{
		return $this->biomeIds;
	}

	/**
	 * @return int[]
	 */
	public function getHeightMapArray() : array{
		return $this->heightMap->toArray();
	}

	/**
	 * @return int[]
	 */
	public function getBlockExtraDataArray() : array{
		return $this->extraData;
	}

	/**
	 * @return bool
	 */
	public function hasChanged() : bool{
		return $this->hasChanged;
	}

	/**
	 * @param bool $value
	 */
	public function setChanged(bool $value = true){
		$this->hasChanged = $value;
		$this->subChunkSendCount = null;
	}

	/**
	 * Returns the subchunk at the specified subchunk Y coordinate, or an empty, unmodifiable stub if it does not exist or the coordinate is out of range.
	 *
	 * @param int  $y
	 * @param bool $generateNew Whether to create a new, modifiable subchunk if there is not one in place
	 *
	 * @return SubChunkInterface
	 */
	public function getSubChunk(int $y, bool $generateNew = false) : SubChunkInterface{
		if($y < 0 or $y >= $this->height){
			return $this->emptySubChunk;
		}elseif($generateNew and $this->subChunks[$y] instanceof EmptySubChunk){
			$this->subChunks[$y] = new SubChunk();
		}
		assert($this->subChunks[$y] !== null, "Somehow something broke, no such subchunk at index $y");
		return $this->subChunks[$y];
	}

	/**
	 * Sets a subchunk in the chunk index
	 *
	 * @param int                    $y
	 * @param SubChunkInterface|null $subChunk
	 * @param bool                   $allowEmpty Whether1 to check if the chunk is empty, and if so replace it with an empty stub
	 *
	 * @return bool
	 */
	public function setSubChunk(int $y, SubChunkInterface $subChunk = null, bool $allowEmpty = false) : bool{
		if($y < 0 or $y >= $this->height){
			return false;
		}
		if($subChunk === null or ($subChunk->isEmpty() and !$allowEmpty)){
			$this->subChunks[$y] = $this->emptySubChunk;
		}else{
			$this->subChunks[$y] = $subChunk;
		}
		$this->setChanged();
		return true;
	}

	/**
	 * @return SubChunk[]
	 */
	public function getSubChunks() : array{
		return $this->subChunks;
	}

	/**
	 * Returns the Y coordinate of the highest non-empty subchunk in this chunk.
	 *
	 * @return int
	 */
	public function getHighestSubChunkIndex() : int{
		for($y = count($this->subChunks) - 1; $y >= 0; --$y){
			if($this->subChunks[$y] === null or $this->subChunks[$y] instanceof EmptySubChunk){
				//No need to thoroughly prune empties at runtime, this will just reduce performance.
				continue;
			}
			break;
		}

		return $y;
	}

	/**
	 * Returns the count of subchunks that need sending to players
	 *
	 * @return int
	 */
	public function getSubChunkSendCount() : int{
		return $this->subChunkSendCount ?? ($this->subChunkSendCount = $this->getHighestSubChunkIndex() + 1);
	}

	/**
	 * Disposes of empty subchunks and frees data where possible
	 */
	public function collectGarbage() : void{
		foreach($this->subChunks as $y => $subChunk){
			if($subChunk instanceof SubChunk){
				if($subChunk->isEmpty()){
					$this->subChunks[$y] = $this->emptySubChunk;
				}else{
					$subChunk->collectGarbage();
				}
			}
		}
	}

	/**
	 * Fast-serializes the chunk for passing between threads
	 * TODO: tiles and entities
	 *
	 * @return string
	 */
	public function fastSerialize() : string{
		$stream = new BinaryStream();
		$stream->putInt($this->x);
		$stream->putInt($this->z);
		$count = 0;
		$subChunks = "";
		foreach($this->subChunks as $y => $subChunk){
			if($subChunk instanceof EmptySubChunk){
				continue;
			}
			++$count;
			$subChunks .= chr($y) . $subChunk->fastSerialize();
		}
		$stream->putByte($count);
		$stream->put($subChunks);
		$stream->put(pack("v*", ...$this->heightMap) .
			$this->biomeIds .
			chr(($this->lightPopulated ? 4 : 0) | ($this->terrainPopulated ? 2 : 0) | ($this->terrainGenerated ? 1 : 0)));
		return $stream->getBuffer();
	}

	public function __set(string $name, $value) : void{
		$this->customData[$name] = $value;
	}

	public function &__get(string $name){
		return $this->customData[$name];
	}

	public function __isset(string $name) : bool{
		return isset($this->customData[$name]);
	}

	public function __unset(string $name) : void{
		unset($this->customData[$name]);
	}

	/**
	 * Deserializes a fast-serialized chunk
	 *
	 * @param string $data
	 *
	 * @return Chunk
	 */
	public static function fastDeserialize(string $data) : Chunk{
		$stream = new BinaryStream();
		$stream->setBuffer($data);
		$data = null;
		$x = $stream->getInt();
		$z = $stream->getInt();
		$subChunks = [];
		$count = $stream->getByte();
		for($y = 0; $y < $count; ++$y){
			$subChunks[$stream->getByte()] = SubChunk::fastDeserialize($stream->get(10240));
		}
		$heightMap = array_values(unpack("v*", $stream->get(512)));
		$biomeIds = $stream->get(256);

		$chunk = new Chunk($x, $z, $subChunks, [], [], $biomeIds, $heightMap);
		$flags = $stream->getByte();
		$chunk->lightPopulated = (bool) ($flags & 4);
		$chunk->terrainPopulated = (bool) ($flags & 2);
		$chunk->terrainGenerated = (bool) ($flags & 1);
		return $chunk;
	}

	//TODO: get rid of this
	public static function getEmptyChunk(int $x, int $z) : Chunk{
		return new Chunk($x, $z);
	}

	/**
	 * Creates a block hash from chunk block coordinates. Used for extra data keys in chunk packets.
	 * @internal
	 *
	 * @param int $x 0-15
	 * @param int $y 0-255
	 * @param int $z 0-15
	 *
	 * @return int
	 */
	public static function chunkBlockHash(int $x, int $y, int $z) : int{
		return ($x << 12) | ($z << 8) | $y;
	}

}


