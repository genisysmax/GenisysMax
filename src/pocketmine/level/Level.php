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

/**
 * All Level related classes are here, like Generators, Populators, Noise, ...
 */
namespace pocketmine\level;

use pocketmine\BedrockPlayer;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\object\Item as DroppedItem;
use pocketmine\entity\object\XPOrb;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\level\ChunkLoadEvent;
use pocketmine\event\level\ChunkPopulateEvent;
use pocketmine\event\level\ChunkUnloadEvent;
use pocketmine\event\level\LevelSaveEvent;
use pocketmine\event\level\LevelUnloadEvent;
use pocketmine\event\level\SpawnChangeEvent;
use pocketmine\event\LevelTimings;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Timings;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Bucket;
use pocketmine\item\Item;
use pocketmine\level\format\Chunk;
use pocketmine\level\format\io\BaseLevelProvider;
use pocketmine\level\format\io\LevelProvider;
use pocketmine\level\generator\Generator;
use pocketmine\level\generator\GeneratorRegisterTask;
use pocketmine\level\generator\GeneratorUnregisterTask;
use pocketmine\level\generator\PopulationTask;
use pocketmine\level\light\BlockLightUpdate;
use pocketmine\level\light\LightPopulationTask;
use pocketmine\level\light\SkyLightUpdate;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\level\particle\Particle;
use pocketmine\level\sound\Sound;
use pocketmine\level\utils\VectorHashUtils;
use pocketmine\level\weather\Weather;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\metadata\BlockMetadataStore;
use pocketmine\metadata\Metadatable;
use pocketmine\metadata\MetadataValue;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\bedrock\palette\block\BlockPalette;
use pocketmine\network\bedrock\protocol\UpdateBlockPacket as BedrockUpdateBlockPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MoveEntityPacket;
use pocketmine\network\mcpe\protocol\SetDifficultyPacket;
use pocketmine\network\mcpe\protocol\SetEntityMotionPacket;
use pocketmine\network\mcpe\protocol\SetTimePacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\tile\Chest;
use pocketmine\tile\Tile;
use pocketmine\utils\Random;
use pocketmine\utils\ReversePriorityQueue;
use pocketmine\utils\Utils;
use SplFixedArray;
use function abs;
use function array_map;
use function count;
use function floor;
use function is_array;
use function is_subclass_of;
use function lcg_value;
use function max;
use function microtime;
use function min;
use function mt_rand;
use const INT32_MAX;
use const INT32_MIN;

#include <rules/Level.h>

class Level extends VectorHashUtils implements ChunkManager, Metadatable{

	private static $levelIdCounter = 1;
	private static $chunkLoaderCounter = 1;

	public const TIME_DAY = 0;
	public const TIME_SUNSET = 12000;
	public const TIME_NIGHT = 14000;
	public const TIME_SUNRISE = 23000;

	public const TIME_FULL = 24000;

    public const DIFFICULTY_PEACEFUL = 0;
    public const DIFFICULTY_EASY = 1;
    public const DIFFICULTY_NORMAL = 2;
    public const DIFFICULTY_HARD = 3;

    public const DIMENSION_NORMAL = 0;
    public const DIMENSION_NETHER = 1;
    public const DIMENSION_END = 2;

	/** @var Tile[] */
	private $tiles = [];

	/** @var Player[] */
	private $players = [];

	/** @var Entity[] */
	private $entities = [];

	/** @var Entity[] */
	public $updateEntities = [];
	/** @var Tile[] */
	public $updateTiles = [];

	/** @var Block[][] */
	private $blockCache = [];

	private $sendTimeTicker = 0;

	/** @var Server */
	private $server;

	/** @var int */
	private $levelId;

	/** @var LevelProvider */
	private $provider;

	/** @var LevelListener[] */
	private $levelListeners = [];

	/** @var ChunkLoader[] */
	private $loaders = [];
	/** @var int[] */
	private $loaderCounter = [];
	/** @var ChunkLoader[][] */
	private $chunkLoaders = [];
	/** @var Player[][] */
	private $playerLoaders = [];

	/** @var ChunkListener[][] */
	private $chunkListeners = [];

	/** @var DataPacket[][] */
	private $chunkPackets = [];

	/** @var float[] */
	private $unloadQueue = [];

	/** @var int */
	private $time;
	/** @var bool */
	public $stopTime = false;
	/** @var bool */
	public $sendTime = true;

	private $folderName;

	/** @var Chunk[] */
	private $chunks = [];

	/** @var Vector3[][] */
	private $changedBlocks = [];

	/** @var ReversePriorityQueue */
	private $scheduledBlockUpdateQueue;
	private $scheduledBlockUpdateQueueIndex = [];

	/** @var \SplQueue */
	private $neighbourBlockUpdateQueue;

	/** @var bool[] */
	private $chunkPopulationQueue = [];
	/** @var bool[] */
	private $chunkLock = [];
	/** @var int */
	private $chunkPopulationQueueSize = 2;
	/** @var bool[] */
	private $generatorRegisteredWorkers = [];

	protected $autoSave = true;

	/** @var BlockMetadataStore */
	private $blockMetadata;

	/** @var Position */
	private $temporalPosition;
	/** @var Vector3 */
	private $temporalVector;

	/** @var \SplFixedArray */
	private $blockStates;

    private int $sleepTicks = 0;

	private $chunkTickRadius;
	private $chunkTickList = [];
	private $chunksPerTick;
	private $clearChunksOnTick;

    private SplFixedArray $randomTickBlocks;

	/** @var LevelTimings */
	public $timings;

    public Random $random;

	private $tickRate;
	public $tickRateTime = 0;
	public $tickRateCounter = 0;

	/** @var string */
	private $generator;

	/** @var bool */
	private $closed = false;

	/** @var BlockLightUpdate|null */
	private $blockLightUpdate = null;
	/** @var SkyLightUpdate|null */
	private $skyLightUpdate = null;

    private int $dimension = self::DIMENSION_NORMAL;

    private Weather $weather;

	public static function generateChunkLoaderId(ChunkLoader $loader) : int{
		if($loader->getLoaderId() === 0){
			return self::$chunkLoaderCounter++;
		}else{
			throw new \InvalidStateException("ChunkLoader has a loader id already assigned: " . $loader->getLoaderId());
		}
	}

	/**
	 * Init the default level data
	 *
	 * @param Server $server
	 * @param string $name
	 * @param string $path
	 * @param string $provider Class that extends LevelProvider
	 *
	 * @throws \Exception
	 */
	public function __construct(Server $server, string $name, string $path, string $provider)
    {
        $this->blockStates = BlockFactory::getBlockStatesArray();
        $this->levelId = static::$levelIdCounter++;
        $this->blockMetadata = new BlockMetadataStore($this);
        $this->server = $server;
        $this->autoSave = $server->getAutoSave();

        /** @var LevelProvider $provider */

        if (is_subclass_of($provider, LevelProvider::class, true)) {
            $this->provider = new $provider($this, $path);
        } else {
            throw new LevelException("Provider is not a subclass of LevelProvider");
        }
        $this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.level.preparing", [$this->provider->getName()]));
        $this->generator = $server->getAdvancedProperty("level-settings.force-void", false) ? Generator::getGenerator("void") : Generator::getGenerator($this->provider->getGenerator());

        $this->folderName = $name;
        $this->scheduledBlockUpdateQueue = new ReversePriorityQueue();
        $this->scheduledBlockUpdateQueue->setExtractFlags(\SplPriorityQueue::EXTR_BOTH);

        $this->neighbourBlockUpdateQueue = new \SplQueue();

        $this->time = (int)$this->provider->getTime();

        $this->chunkTickRadius = min($this->server->getViewDistance(), max(1, (int)$this->server->getProperty("chunk-ticking.tick-radius", 4)));
        $this->chunksPerTick = (int)$this->server->getProperty("chunk-ticking.per-tick", 40);
        $this->chunkPopulationQueueSize = (int)$this->server->getProperty("chunk-generation.population-queue-size", 2);
        $this->chunkTickList = [];
        $this->clearChunksOnTick = (bool)$this->server->getProperty("chunk-ticking.clear-tick-list", true);

        $dontTickBlocks = $this->server->getProperty("chunk-ticking.disable-block-ticking", []);
        $this->randomTickBlocks = new SplFixedArray(256);
        foreach ($this->randomTickBlocks as $id => $null) {
            $block = Block::get($id); //Make sure it's a copy
            if (!isset($dontTickBlocks[$id]) and $block->ticksRandomly()) {
                $this->randomTickBlocks[$id] = $block;
            }
        }

        $this->timings = new LevelTimings($this);
        $this->random = new Random();
        $this->temporalPosition = new Position(0, 0, 0, $this);
        $this->weather = new Weather($this, 0);
        $this->temporalVector = new Vector3(0, 0, 0);
        $this->tickRate = 1;

        $this->setDimension(self::DIMENSION_NORMAL);

        if($server->getAdvancedProperty("level.weather", true) and $this->getDimension() == self::DIMENSION_NORMAL){
            $this->weather->setCanCalculate(true);
        }else {
            $this->weather->setCanCalculate(false);
        }
	}

    public function setDimension(int $dimension): void{
        $this->dimension = $dimension;
    }

    public function getDimension() : int{
        return $this->dimension;
    }

    /**
     * @return Weather
     */
    public function getWeather(){
        return $this->weather;
    }

	public function getTickRate() : int{
		return $this->tickRate;
	}

	public function getTickRateTime() : float{
		return $this->tickRateTime;
	}

	public function setTickRate(int $tickRate){
		$this->tickRate = $tickRate;
	}

	public function registerGeneratorToWorker(int $worker) : void{
		$this->generatorRegisteredWorkers[$worker] = true;
		$this->server->getScheduler()->scheduleAsyncTaskToWorker(new GeneratorRegisterTask($this, $this->generator, $this->provider->getGeneratorOptions()), $worker);
	}

	public function unregisterGenerator(){
		foreach($this->generatorRegisteredWorkers as $i => $bool){
			$this->server->getScheduler()->scheduleAsyncTaskToWorker(new GeneratorUnregisterTask($this), $i);
		}
		$this->generatorRegisteredWorkers = [];
	}

	public function getBlockMetadata() : BlockMetadataStore{
		return $this->blockMetadata;
	}

	public function getServer() : Server{
		return $this->server;
	}

	final public function getProvider() : LevelProvider{
		return $this->provider;
	}

	/**
	 * Returns the unique level identifier
	 */
	final public function getId() : int{
		return $this->levelId;
	}

	public function isClosed() : bool{
		return $this->closed;
	}

	public function close(){
		if($this->closed){
			throw new \InvalidStateException("Tried to close a level which is already closed");
		}

		if($this->getAutoSave()){
			$this->save();
		}

		foreach($this->chunks as $chunk){
			$this->unloadChunk($chunk->getX(), $chunk->getZ(), false);
		}
		$this->unregisterGenerator();

		$this->provider->close();
		$this->provider = null;
		$this->blockMetadata = null;
		$this->blockCache = [];
		$this->temporalPosition = null;
		$this->closed = true;
	}

	public function addSound(Sound $sound, array $players = null){
		$pk = $sound->encode();

		if($players === null){
			if($pk !== null){
				if(!is_array($pk)){
					$this->addChunkPacket((int)$sound->x >> 4, (int)$sound->z >> 4, $pk);
				}else{
					foreach($pk as $e){
						$this->addChunkPacket((int)$sound->x >> 4, (int)$sound->z >> 4, $e);
					}
				}
			}
		}else{
			if($pk !== null){
				if(!is_array($pk)){
					$this->server->broadcastPacket($players, $pk);
				}else{
					$this->server->batchPackets($players, $pk, false);
				}
			}
		}
	}

	public function addParticle(Particle $particle, array $players = null){
		$pk = $particle->encode();

		if($players === null){
			if($pk !== null){
				if(!is_array($pk)){
					$this->addChunkPacket(((int)$particle->x) >> 4, ((int)$particle->z) >> 4, $pk);
				}else{
					foreach($pk as $e){
						$this->addChunkPacket(((int)$particle->x) >> 4, ((int)$particle->z) >> 4, $e);
					}
				}
			}
		}else{
			if($pk !== null){
				if(!is_array($pk)){
					$this->server->broadcastPacket($players, $pk);
				}else{
					$this->server->batchPackets($players, $pk, false);
				}
			}
		}
	}

	/**
	 * Broadcasts a LevelEvent to players in the area. This could be sound, particles, weather changes, etc.
	 *
	 * @param Vector3 $pos
	 * @param int $evid
	 * @param int $data
	 */
	public function broadcastLevelEvent(Vector3 $pos, int $evid, int $data = 0){
		$pk = new LevelEventPacket();
		$pk->evid = $evid;
		$pk->data = $data;
		list($pk->x, $pk->y, $pk->z) = [$pos->x, $pos->y, $pos->z];
		$this->addChunkPacket((int)$pos->x >> 4, (int)$pos->z >> 4, $pk);
	}

	/**
	 * Broadcasts a LevelSoundEvent to players in the area.
	 *
	 * @param Vector3 $pos
	 * @param int     $soundId
	 * @param int     $extraData
	 * @param int     $entityType
	 * @param bool    $isBabyMob
	 * @param bool    $disableRelativeVolume If true, all players receiving this sound-event will hear the sound at full volume regardless of distance
	 */
	public function broadcastLevelSoundEvent(Vector3 $pos, int $soundId, int $extraData = -1, int $entityType = 1, bool $isBabyMob = false, bool $disableRelativeVolume = false){
		$pk = new LevelSoundEventPacket();
		$pk->sound = $soundId;
		$pk->extraData = $extraData;
		$pk->entityType = $entityType;
		$pk->isBabyMob = $isBabyMob;
		$pk->disableRelativeVolume = $disableRelativeVolume;
		list($pk->x, $pk->y, $pk->z) = [$pos->x, $pos->y, $pos->z];
		$this->addChunkPacket((int)$pos->x >> 4, (int)$pos->z >> 4, $pk);
	}

	public function getAutoSave() : bool{
		return $this->autoSave;
	}

	public function setAutoSave(bool $value){
		$this->autoSave = $value;
	}

	/**
	 * Unloads the current level from memory safely
	 *
	 * @param bool $force default false, force unload of default level
	 *
	 * @return bool
	 */
	public function unload(bool $force = false) : bool{

		$ev = new LevelUnloadEvent($this);

		if($this === $this->server->getDefaultLevel() and $force !== true){
			$ev->setCancelled(true);
		}

		$ev->call();

		if(!$force and $ev->isCancelled()){
			return false;
		}

		$this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.level.unloading", [$this->getName()]));
		$defaultLevel = $this->server->getDefaultLevel();
		foreach($this->getPlayers() as $player){
			if($this === $defaultLevel or $defaultLevel === null){
				$player->close($player->getLeaveMessage(), "Forced default level unload");
			}elseif($defaultLevel instanceof Level){
				$player->teleport($this->server->getDefaultLevel()->getSafeSpawn());
			}
		}

		foreach($this->levelListeners as $listener){
			$listener->onLevelUnloaded($this);
		}

		if($this === $defaultLevel){
			$this->server->setDefaultLevel(null);
		}

		$this->close();

		return true;
	}

	/**
	 * Gets the players being used in a specific chunk
	 *
	 * @param int $chunkX
	 * @param int $chunkZ
	 *
	 * @return Player[]
	 */
	public function getChunkPlayers(int $chunkX, int $chunkZ) : array{
		return $this->playerLoaders[Level::chunkHash($chunkX, $chunkZ)] ?? [];
	}

	/**
	 * Gets the chunk loaders being used in a specific chunk
	 *
	 * @param int $chunkX
	 * @param int $chunkZ
	 *
	 * @return ChunkLoader[]
	 */
	public function getChunkLoaders(int $chunkX, int $chunkZ) : array{
		return $this->chunkLoaders[Level::chunkHash($chunkX, $chunkZ)] ?? [];
	}

	/**
	 * Returns an array of players who have the target position within their view distance.
	 * 
	 * @param Vector3 $pos
	 *
	 * @return Player[]
	 */
	public function getViewersForPosition(Vector3 $pos) : array{
		return $this->getChunkPlayers((int)$pos->getFloorX() >> 4, (int)$pos->getFloorZ() >> 4);
	}

	public function addChunkPacket(int $chunkX, int $chunkZ, DataPacket $packet){
		if(!isset($this->chunkPackets[$index = Level::chunkHash($chunkX, $chunkZ)])){
			$this->chunkPackets[$index] = [$packet];
		}else{
			$this->chunkPackets[$index][] = $packet;
		}
	}

	public function registerChunkLoader(ChunkLoader $loader, int $chunkX, int $chunkZ, bool $autoLoad = true){
		$hash = $loader->getLoaderId();

		if(!isset($this->chunkLoaders[$index = Level::chunkHash($chunkX, $chunkZ)])){
			$this->chunkLoaders[$index] = [];
			$this->playerLoaders[$index] = [];
		}elseif(isset($this->chunkLoaders[$index][$hash])){
			return;
		}

		$this->chunkLoaders[$index][$hash] = $loader;
		if($loader instanceof Player){
			$this->playerLoaders[$index][$hash] = $loader;
		}

		if(!isset($this->loaders[$hash])){
			$this->loaderCounter[$hash] = 1;
			$this->loaders[$hash] = $loader;
		}else{
			++$this->loaderCounter[$hash];
		}

		$this->cancelUnloadChunkRequest($chunkX, $chunkZ);

		if($autoLoad){
			$this->loadChunk($chunkX, $chunkZ);
		}
	}

	public function unregisterChunkLoader(ChunkLoader $loader, int $chunkX, int $chunkZ){
		if(isset($this->chunkLoaders[$index = Level::chunkHash($chunkX, $chunkZ)][$hash = $loader->getLoaderId()])){
			unset($this->chunkLoaders[$index][$hash]);
			unset($this->playerLoaders[$index][$hash]);
			if(count($this->chunkLoaders[$index]) === 0){
				unset($this->chunkLoaders[$index]);
				unset($this->playerLoaders[$index]);
				$this->unloadChunkRequest($chunkX, $chunkZ, true);
			}

			if(--$this->loaderCounter[$hash] === 0){
				unset($this->loaderCounter[$hash]);
				unset($this->loaders[$hash]);
			}
		}
	}

	/**
	 * Registers a listener to receive events on a chunk.
	 *
	 * @param ChunkListener $listener
	 * @param int           $chunkX
	 * @param int           $chunkZ
	 */
	public function registerChunkListener(ChunkListener $listener, int $chunkX, int $chunkZ) : void{
		$hash = Level::chunkHash($chunkX, $chunkZ);
		if(isset($this->chunkListeners[$hash])){
			$this->chunkListeners[$hash][spl_object_id($listener)] = $listener;
		}else{
			$this->chunkListeners[$hash] = [spl_object_id($listener) => $listener];
		}
	}

	/**
	 * Unregisters a chunk listener previously registered.
	 * @see Level::registerChunkListener()
	 *
	 * @param ChunkListener $listener
	 * @param int           $chunkX
	 * @param int           $chunkZ
	 */
	public function unregisterChunkListener(ChunkListener $listener, int $chunkX, int $chunkZ) : void{
		$hash = Level::chunkHash($chunkX, $chunkZ);
		if(isset($this->chunkListeners[$hash])){
			unset($this->chunkListeners[$hash][spl_object_id($listener)]);
			if(empty($this->chunkListeners[$hash])){
				unset($this->chunkListeners[$hash]);
			}
		}
	}

	/**
	 * Returns all the listeners attached to this chunk.
	 *
	 * @param int $chunkX
	 * @param int $chunkZ
	 *
	 * @return ChunkListener[]
	 */
	public function getChunkListeners(int $chunkX, int $chunkZ) : array{
		return $this->chunkListeners[Level::chunkHash($chunkX, $chunkZ)] ?? [];
	}

	/**
	 * Registers a listener to receive events on a Level.
	 *
	 * @param LevelListener $listener
	 */
	public function registerLevelListener(LevelListener $listener) : void{
		$this->levelListeners[spl_object_id($listener)] = $listener;
	}

	/**
	 * Unregisters a level listener previously registered.
	 * @see Level::registerLevelListener()
	 *
	 * @param LevelListener $listener
	 */
	public function unregisterLevelListener(LevelListener $listener) : void{
		unset($this->levelListeners[spl_object_id($listener)]);
	}

	/**
	 * WARNING: Do not use this, it's only for internal use.
	 * Changes to this function won't be recorded on the version.
	 */
	public function checkTime(){
		if($this->stopTime === true){
			return;
		}else{
			$this->time += 1;
		}
	}

	/**
	 * WARNING: Do not use this, it's only for internal use.
	 * Changes to this function won't be recorded on the version.
	 *
	 * @param Player[] ...$targets If empty, will send to all players in the level.
	 */
	public function sendTime(Player ...$targets){
		if(!$this->sendTime){
			return;
		}
		$pk = new SetTimePacket();
		$pk->time = (int) $this->time;

		$this->server->broadcastPacket(count($targets) > 0 ? $targets : $this->players, $pk);
	}

	/**
	 * WARNING: Do not use this, it's only for internal use.
	 * Changes to this function won't be recorded on the version.
	 *
	 * @param int $currentTick
	 *
	 */
	public function doTick(int $currentTick){
		if($this->closed){
			throw new \InvalidStateException("Attempted to tick a Level which has been closed");
		}

		$this->timings->doTick->startTiming();

		$this->checkTime();

		if(++$this->sendTimeTicker === 200){
			$this->sendTime();
			$this->sendTimeTicker = 0;
		}

        $this->weather->calcWeather($currentTick);

		$this->unloadChunks();

		//Do block updates
		$this->timings->doTickPending->startTiming();

		//Delayed updates
		while($this->scheduledBlockUpdateQueue->count() > 0 and $this->scheduledBlockUpdateQueue->current()["priority"] <= $currentTick){
			$block = $this->getBlock($this->scheduledBlockUpdateQueue->extract()["data"]);
			unset($this->scheduledBlockUpdateQueueIndex[Level::blockHash($block->x, $block->y, $block->z)]);
            $block->onScheduledUpdate();
		}

		//Normal updates
		while($this->neighbourBlockUpdateQueue->count() > 0){
			$index = $this->neighbourBlockUpdateQueue->dequeue();
			Level::getBlockXYZ($index, $x, $y, $z);

			$block = $this->getBlockAt($x, $y, $z);
			$block->clearCaches(); //for blocks like fences, force recalculation of connected AABBs

			$ev = new BlockUpdateEvent($block);
			$ev->call();
			if(!$ev->isCancelled()){
                $ev->getBlock()->onNearbyBlockChange();
			}
		}

		$this->timings->doTickPending->stopTiming();

		$this->timings->entityTick->startTiming();
		//Update entities that need update
		Timings::$tickEntityTimer->startTiming();
		foreach($this->updateEntities as $id => $entity){
			if($entity->closed or !$entity->onUpdate($currentTick)){
				unset($this->updateEntities[$id]);
			}
			if($entity->isFlaggedForDespawn()){
				$entity->close();
			}
		}
		Timings::$tickEntityTimer->stopTiming();
		$this->timings->entityTick->stopTiming();

		$this->timings->tileEntityTick->startTiming();
		Timings::$tickTileEntityTimer->startTiming();
		//Update tiles that need update
		if(count($this->updateTiles) > 0){
			foreach($this->updateTiles as $id => $tile){
				if($tile->onUpdate() !== true){
					unset($this->updateTiles[$id]);
				}
			}
		}
		Timings::$tickTileEntityTimer->stopTiming();
		$this->timings->tileEntityTick->stopTiming();

		$this->timings->doTickTiles->startTiming();
		$this->tickChunks();
		$this->timings->doTickTiles->stopTiming();

		$this->executeQueuedLightUpdates();

		if(count($this->changedBlocks) > 0){
			if(count($this->players) > 0){
				foreach($this->changedBlocks as $index => $blocks){
					if(empty($blocks)){ //blocks can be set normally and then later re-set with direct send
						continue;
					}
					Level::getXZ($index, $chunkX, $chunkZ);
					if(count($blocks) > 512){
						$chunk = $this->getChunk($chunkX, $chunkZ);
						foreach($this->getChunkPlayers($chunkX, $chunkZ) as $p){
							$p->onChunkChanged($chunk);
						}
					}else{
						$this->sendBlocks($this->getChunkPlayers($chunkX, $chunkZ), $blocks, UpdateBlockPacket::FLAG_ALL);
					}
				}
			}

			$this->changedBlocks = [];

		}

		if($this->sleepTicks > 0 and --$this->sleepTicks <= 0){
			$this->checkSleep();
		}

		foreach($this->chunkPackets as $index => $entries){
			Level::getXZ($index, $chunkX, $chunkZ);
			$chunkPlayers = $this->getChunkPlayers($chunkX, $chunkZ);
			if(count($chunkPlayers) > 0){
				$this->server->batchPackets($chunkPlayers, $entries, false, false);
			}
		}
		$this->chunkPackets = [];

		$this->timings->doTick->stopTiming();
	}

	public function checkSleep(){
		if(count($this->players) === 0){
			return;
		}

		$resetTime = true;
		foreach($this->getPlayers() as $p){
			if(!$p->isSleeping()){
				$resetTime = false;
				break;
			}
		}

		if($resetTime){
			$time = $this->getTime() % Level::TIME_FULL;

			if($time >= Level::TIME_NIGHT and $time < Level::TIME_SUNRISE){
				$this->setTime($this->getTime() + Level::TIME_FULL - $time);

				foreach($this->getPlayers() as $p){
					$p->stopSleep();
				}
			}
		}
	}

    public function setSleepTicks(int $ticks) : void{
        $this->sleepTicks = $ticks;
    }

	public function sendBlockExtraData(int $x, int $y, int $z, int $id, int $data, array $targets = null){
		$pk = new LevelEventPacket;
		$pk->evid = LevelEventPacket::EVENT_SET_DATA;
		$pk->x = $x + 0.5;
		$pk->y = $y + 0.5;
		$pk->z = $z + 0.5;
		$pk->data = ($data << 8) | $id;

		$this->server->broadcastPacket($targets ?? $this->getChunkPlayers((int)$x >> 4, (int)$z >> 4), $pk);
	}

	/**
	 * @param Player[] $target
	 * @param Block[]|Vector3[]  $blocks
	 * @param int      $flags
	 * @param bool     $optimizeRebuilds @deprecated
	 */
	public function sendBlocks(array $target, array $blocks, $flags = UpdateBlockPacket::FLAG_NONE, bool $optimizeRebuilds = false){
		$packets = [];

		foreach($blocks as $b){
			if(!$b instanceof Vector3){
				continue;
			}

			$pk = new UpdateBlockPacket();
			$pk->x = $b->x;
			$pk->y = $b->y;
			$pk->z = $b->z;

			if($b instanceof Block){
				$pk->blockId = $b->getId();
				$pk->blockData = $b->getDamage();
			}else{
				$fullBlock = $this->getFullBlock($b->x, $b->y, $b->z);
				$pk->blockId = (int)$fullBlock >> 4;
				$pk->blockData = $fullBlock & 0xf;
			}

			$pk->flags = $flags;

			$packets[] = $pk;
		}

		$this->server->batchPackets($target, $packets, false, false);
	}

	public function clearCache(bool $force = false){
		if($force){
			$this->blockCache = [];
		}else{
			$count = 0;
			foreach($this->blockCache as $list){
				$count += count($list);
				if($count > 2048){
					$this->blockCache = [];
					break;
				}
			}
		}
	}

    /**
     * @phpstan-return SplFixedArray<Block|null>
     */
    public function getRandomTickedBlocks() : SplFixedArray{
        return $this->randomTickBlocks;
    }

    public function addRandomTickedBlock(int $id) : void{
        $this->randomTickBlocks[$id] = Block::get($id);
    }

    public function removeRandomTickedBlock(int $id) : void{
        $this->randomTickBlocks[$id] = null;
    }

	private function tickChunks(){
		if($this->chunksPerTick <= 0 or count($this->loaders) === 0){
			$this->chunkTickList = [];
			return;
		}

		$chunksPerLoader = min(200, max(1, (int) ((($this->chunksPerTick - count($this->loaders)) / count($this->loaders)) + 0.5)));
		$randRange = 3 + $chunksPerLoader / 30;
		$randRange = (int) ($randRange > $this->chunkTickRadius ? $this->chunkTickRadius : $randRange);

		foreach($this->loaders as $loader){
			$chunkX = (int) floor($loader->getX()) >> 4;
			$chunkZ = (int) floor($loader->getZ()) >> 4;

			$index = Level::chunkHash($chunkX, $chunkZ);
			$existingLoaders = max(0, $this->chunkTickList[$index] ?? 0);
			$this->chunkTickList[$index] = $existingLoaders + 1;
			for($chunk = 0; $chunk < $chunksPerLoader; ++$chunk){
				$dx = mt_rand(-$randRange, $randRange);
				$dz = mt_rand(-$randRange, $randRange);
				$hash = Level::chunkHash($dx + $chunkX, $dz + $chunkZ);
				if(!isset($this->chunkTickList[$hash]) and isset($this->chunks[$hash])){
					$this->chunkTickList[$hash] = -1;
				}
			}
		}

		foreach($this->chunkTickList as $index => $loaders){
			Level::getXZ($index, $chunkX, $chunkZ);

			for($cx = -1; $cx <= 1; ++$cx){
				for($cz = -1; $cz <= 1; ++$cz){
					if(!isset($this->chunks[Level::chunkHash($chunkX + $cx, $chunkZ + $cz)])){
						unset($this->chunkTickList[$index]);
						goto skip_to_next; //no "continue 3" thanks!
					}
				}
			}

			if($loaders <= 0){
				unset($this->chunkTickList[$index]);
			}

			$chunk = $this->chunks[$index];
			foreach($chunk->getEntities() as $entity){
				$entity->scheduleUpdate();
			}

			foreach($chunk->getSubChunks() as $Y => $subChunk){
				if(!$subChunk->isEmpty()){
					$k = mt_rand(0, 0xfffffffff); //36 bits
					for($i = 0; $i < 3; ++$i){
						$x = $k & 0x0f;
						$y = ((int)$k >> 4) & 0x0f;
						$z = ((int)$k >> 8) & 0x0f;
						(int)$k >>= 12;

						$blockId = $subChunk->getBlockId($x, $y, $z);
						if(isset($this->randomTickBlocks[$blockId])){
                            $block = clone $this->randomTickBlocks[$blockId];
                            $block->setDamage($subChunk->getBlockData($x, $y, $z));
							$block->x = $chunkX * 16 + $x;
							$block->y = ($Y << 4) + $y;
							$block->z = $chunkZ * 16 + $z;
							$block->level = $this;
                            $block->onRandomTick();
						}
					}
				}
			}

			skip_to_next:
		}

		if($this->clearChunksOnTick){
			$this->chunkTickList = [];
		}
	}

	public function __debugInfo() : array{
		return [];
	}

	/**
	 * @param bool $force
	 *
	 * @return bool
	 */
	public function save(bool $force = false){

		if(!$this->getAutoSave() and !$force){
			return false;
		}

		(new LevelSaveEvent($this))->call();

		$this->provider->setTime((int) $this->time);
		$this->saveChunks();
		if($this->provider instanceof BaseLevelProvider){
			$this->provider->saveLevelData();
		}

		return true;
	}

	public function saveChunks(){
		foreach($this->chunks as $chunk){
			if($chunk->hasChanged() and $chunk->isGenerated()){
				$this->provider->setChunk($chunk->getX(), $chunk->getZ(), $chunk);
				$this->provider->saveChunk($chunk->getX(), $chunk->getZ());
				$chunk->setChanged(false);
			}
		}
	}

	/**
	 * @deprecated This method will be removed in the future due to misleading/ambiguous name. Use {@link Level#scheduleDelayedBlockUpdate} instead.
	 *
	 * @param Vector3 $pos
	 * @param int     $delay
	 */
	public function scheduleUpdate(Vector3 $pos, int $delay){
		$this->scheduleDelayedBlockUpdate($pos, $delay);
	}

	/**
	 * Schedules a block update to be executed after the specified number of ticks.
	 * Blocks will be updated with the scheduled update type.
	 *
	 * @param Vector3 $pos
	 * @param int     $delay
	 */
	public function scheduleDelayedBlockUpdate(Vector3 $pos, int $delay){
		if(
			!$this->isInWorld($pos->x, $pos->y, $pos->z) or
			(isset($this->scheduledBlockUpdateQueueIndex[$index = Level::blockHash($pos->x, $pos->y, $pos->z)]) and $this->scheduledBlockUpdateQueueIndex[$index] <= $delay)
		){
			return;
		}

		$ev = new BlockUpdateEvent($this->getBlockAt((int) $pos->x, (int) $pos->y, (int) $pos->z));
		$ev->call();
		if(!$ev->isCancelled()){
			$this->scheduledBlockUpdateQueueIndex[$index] = $delay;
			$this->scheduledBlockUpdateQueue->insert(new Vector3((int) $pos->x, (int) $pos->y, (int) $pos->z), (int) $delay + $this->server->getTick());
		}
	}

	/**
	 * Schedules the blocks around the specified position to be updated at the end of this tick.
	 * Blocks will be updated with the normal update type.
	 *
	 * @param Vector3 $pos
	 */
	public function scheduleNeighbourBlockUpdates(Vector3 $pos){
		$pos = $pos->floor();

		for($i = 0; $i <= 5; ++$i){
			$side = $pos->getSide($i);
			if($this->isInWorld($side->x, $side->y, $side->z)){
				$this->neighbourBlockUpdateQueue->enqueue(Level::blockHash($side->x, $side->y, $side->z));
			}
		}
	}

	/**
	 * @param AxisAlignedBB $bb
	 * @param bool          $targetFirst
	 *
	 * @return Block[]
	 */
	public function getCollisionBlocks(AxisAlignedBB $bb, bool $targetFirst = false) : array{
		$minX = (int) floor($bb->minX - 1);
		$minY = (int) floor($bb->minY - 1);
		$minZ = (int) floor($bb->minZ - 1);
		$maxX = (int) floor($bb->maxX + 1);
		$maxY = (int) floor($bb->maxY + 1);
		$maxZ = (int) floor($bb->maxZ + 1);

		$collides = [];

		if($targetFirst){
			for($z = $minZ; $z <= $maxZ; ++$z){
				for($x = $minX; $x <= $maxX; ++$x){
					for($y = $minY; $y <= $maxY; ++$y){
						$block = $this->getBlockAt($x, $y, $z);
						if($block->getId() !== 0 and $block->collidesWithBB($bb)){
							return [$block];
						}
					}
				}
			}
		}else{
			for($z = $minZ; $z <= $maxZ; ++$z){
				for($x = $minX; $x <= $maxX; ++$x){
					for($y = $minY; $y <= $maxY; ++$y){
						$block = $this->getBlockAt($x, $y, $z);
						if($block->getId() !== 0 and $block->collidesWithBB($bb)){
							$collides[] = $block;
						}
					}
				}
			}
		}


		return $collides;
	}

	/**
	 * @param Vector3 $pos
	 *
	 * @return bool
	 */
	public function isFullBlock(Vector3 $pos) : bool{
		if($pos instanceof Block){
			if($pos->isSolid()){
				return true;
			}
			$bb = $pos->getBoundingBox();
		}else{
			$bb = $this->getBlock($pos)->getBoundingBox();
		}

		return $bb !== null and $bb->getAverageEdgeLength() >= 1;
	}

	/**
	 * @param Entity        $entity
	 * @param AxisAlignedBB $bb
	 * @param bool          $entities
	 *
	 * @return AxisAlignedBB[]
	 */
	public function getCollisionCubes(Entity $entity, AxisAlignedBB $bb, bool $entities = true) : array{
		$minX = (int) floor($bb->minX - 1);
		$minY = (int) floor($bb->minY - 1);
		$minZ = (int) floor($bb->minZ - 1);
		$maxX = (int) floor($bb->maxX + 1);
		$maxY = (int) floor($bb->maxY + 1);
		$maxZ = (int) floor($bb->maxZ + 1);

		$collides = [];

		for($z = $minZ; $z <= $maxZ; ++$z){
			for($x = $minX; $x <= $maxX; ++$x){
				for($y = $minY; $y <= $maxY; ++$y){
					$block = $this->getBlockAt($x, $y, $z);
					if(!$block->canPassThrough() and $block->collidesWithBB($bb)){
						foreach($block->getCollisionBoxes() as $blockBB){
							$collides[] = $blockBB;
						}
					}
				}
			}
		}

		if($entities){
			foreach($this->getCollidingEntities($bb->grow(0.25, 0.25, 0.25), $entity) as $ent){
				$collides[] = clone $ent->boundingBox;
			}
		}

		return $collides;
	}

	public function getFullLight(Vector3 $pos) : int{
		return $this->getFullLightAt($pos->x, $pos->y, $pos->z);
	}

	public function getFullLightAt(int $x, int $y, int $z) : int{
		//TODO: decrease light level by time of day
		$skyLight = $this->getBlockSkyLightAt($x, $y, $z);
		if($skyLight < 15){
			return max($skyLight, $this->getBlockLightAt($x, $y, $z));
		}else{
			return $skyLight;
		}
	}

	/**
	 * @param $x
	 * @param $y
	 * @param $z
	 *
	 * @return int bitmap, (id << 4) | data
	 */
	public function getFullBlock(int $x, int $y, int $z) : int{
		return $this->getChunk((int)$x >> 4, (int)$z >> 4, true)->getFullBlock($x & 0x0f, $y, $z & 0x0f);
	}

	public function isInWorld(float $x, float $y, float $z) : bool{
		return (
			$x <= INT32_MAX and $x >= INT32_MIN and
			$y < $this->getWorldHeight() and $y >= 0 and
			$z <= INT32_MAX and $z >= INT32_MIN
		);
	}

	/**
	 * Gets the Block object at the Vector3 location. This method wraps around {@link getBlockAt}, converting the
	 * vector components to integers.
	 *
	 * Note: If you're using this for performance-sensitive code, and you're guaranteed to be supplying ints in the
	 * specified vector, consider using {@link getBlockAt} instead for better performance.
	 *
	 * @param Vector3 $pos
	 * @param bool    $cached Whether to use the block cache for getting the block (faster, but may be inaccurate)
	 * @param bool    $addToCache Whether to cache the block object created by this method call.
	 *
	 * @return Block
	 */
	public function getBlock(Vector3 $pos, bool $cached = true, bool $addToCache = true) : Block{
		return $this->getBlockAt((int) floor($pos->x), (int) floor($pos->y), (int) floor($pos->z), $cached, $addToCache);
	}

	/**
	 * Gets the Block object at the specified coordinates.
	 *
	 * Note for plugin developers: If you are using this method a lot (thousands of times for many positions for
	 * example), you may want to set addToCache to false to avoid using excessive amounts of memory.
	 *
	 * @param int  $x
	 * @param int  $y
	 * @param int  $z
	 * @param bool $cached Whether to use the block cache for getting the block (faster, but may be inaccurate)
	 * @param bool $addToCache Whether to cache the block object created by this method call.
	 *
	 * @return Block
	 */
	public function getBlockAt(int $x, int $y, int $z, bool $cached = true, bool $addToCache = true) : Block{
		$fullState = 0;
		$blockHash = null;
		$chunkHash = Level::chunkHash((int)$x >> 4, (int)$z >> 4);

		if($this->isInWorld($x, $y, $z)){
			$blockHash = Level::blockHash($x, $y, $z);

			if($cached and isset($this->blockCache[$chunkHash][$blockHash])){
				return $this->blockCache[$chunkHash][$blockHash];
			}

			$chunk = $this->getChunk((int)$x >> 4, (int)$z >> 4, true);
			if($chunk !== null){
				$fullState = $chunk->getFullBlock($x & 0x0f, $y, $z & 0x0f);
			}else{
				$addToCache = false;
			}
		}
		$block = clone $this->blockStates[$fullState & 0xfff];

		$block->x = $x;
		$block->y = $y;
		$block->z = $z;
		$block->level = $this;

		if($addToCache and $blockHash !== null){
			$this->blockCache[$chunkHash][$blockHash] = $block;
		}
		return $block;
	}

	public function updateAllLight(Vector3 $pos){
		$this->updateBlockSkyLight($pos->x, $pos->y, $pos->z);
		$this->updateBlockLight($pos->x, $pos->y, $pos->z);
	}

	/**
	 * Returns the highest block light level available in the positions adjacent to the specified block coordinates.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 *
	 * @return int
	 */
	public function getHighestAdjacentBlockSkyLight(int $x, int $y, int $z) : int{
		return max([
			$this->getBlockSkyLightAt($x + 1, $y, $z),
			$this->getBlockSkyLightAt($x - 1, $y, $z),
			$this->getBlockSkyLightAt($x, $y + 1, $z),
			$this->getBlockSkyLightAt($x, $y - 1, $z),
			$this->getBlockSkyLightAt($x, $y, $z + 1),
			$this->getBlockSkyLightAt($x, $y, $z - 1)
		]);
	}

	public function updateBlockSkyLight(int $x, int $y, int $z){
		$this->timings->doBlockSkyLightUpdates->startTiming();

		$oldHeightMap = $this->getHeightMap($x, $z);
		$sourceId = $this->getBlockIdAt($x, $y, $z);

		$yPlusOne = $y + 1;

		if($yPlusOne === $oldHeightMap){ //Block changed directly beneath the heightmap. Check if a block was removed or changed to a different light-filter.
			$newHeightMap = $this->getChunk((int)$x >> 4, (int)$z >> 4)->recalculateHeightMapColumn($x & 0x0f, $z & 0x0f);
		}elseif($yPlusOne > $oldHeightMap){ //Block changed above the heightmap.
			if(BlockFactory::$lightFilter[$sourceId] > 1 or BlockFactory::$diffusesSkyLight[$sourceId]){
				$this->setHeightMap($x, $z, $yPlusOne);
				$newHeightMap = $yPlusOne;
			}else{ //Block changed which has no effect on direct sky light, for example placing or removing glass.
				$this->timings->doBlockSkyLightUpdates->stopTiming();
				return;
			}
		}else{ //Block changed below heightmap
			$newHeightMap = $oldHeightMap;
		}

		if($this->skyLightUpdate === null){
			$this->skyLightUpdate = new SkyLightUpdate($this);
		}
		if($newHeightMap > $oldHeightMap){ //Heightmap increase, block placed, remove sky light
			for($i = $y; $i >= $oldHeightMap; --$i){
				$this->skyLightUpdate->setAndUpdateLight($x, $i, $z, 0); //Remove all light beneath, adjacent recalculation will handle the rest.
			}
		}elseif($newHeightMap < $oldHeightMap){ //Heightmap decrease, block changed or removed, add sky light
			for($i = $y; $i >= $newHeightMap; --$i){
				$this->skyLightUpdate->setAndUpdateLight($x, $i, $z, 15);
			}
		}else{ //No heightmap change, block changed "underground"
			$this->skyLightUpdate->setAndUpdateLight($x, $y, $z, max(0, $this->getHighestAdjacentBlockSkyLight($x, $y, $z) - BlockFactory::$lightFilter[$sourceId]));
		}

		$this->timings->doBlockSkyLightUpdates->stopTiming();
	}

	/**
	 * Returns the highest block light level available in the positions adjacent to the specified block coordinates.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 *
	 * @return int
	 */
	public function getHighestAdjacentBlockLight(int $x, int $y, int $z) : int{
		return max([
			$this->getBlockLightAt($x + 1, $y, $z),
			$this->getBlockLightAt($x - 1, $y, $z),
			$this->getBlockLightAt($x, $y + 1, $z),
			$this->getBlockLightAt($x, $y - 1, $z),
			$this->getBlockLightAt($x, $y, $z + 1),
			$this->getBlockLightAt($x, $y, $z - 1)
		]);
	}

	public function updateBlockLight(int $x, int $y, int $z){
		$this->timings->doBlockLightUpdates->startTiming();

		$id = $this->getBlockIdAt($x, $y, $z);
		$newLevel = max(BlockFactory::$light[$id], $this->getHighestAdjacentBlockLight($x, $y, $z) - BlockFactory::$lightFilter[$id]);

		if($this->blockLightUpdate === null){
			$this->blockLightUpdate = new BlockLightUpdate($this);
		}
		$this->blockLightUpdate->setAndUpdateLight($x, $y, $z, $newLevel);

		$this->timings->doBlockLightUpdates->stopTiming();
	}

	public function executeQueuedLightUpdates() : void{
		if($this->blockLightUpdate !== null){
			$this->timings->doBlockLightUpdates->startTiming();
			$this->blockLightUpdate->execute();
			$this->blockLightUpdate = null;
			$this->timings->doBlockLightUpdates->stopTiming();
		}
		if($this->skyLightUpdate !== null){
			$this->timings->doBlockSkyLightUpdates->startTiming();
			$this->skyLightUpdate->execute();
			$this->skyLightUpdate = null;
			$this->timings->doBlockSkyLightUpdates->stopTiming();
		}
	}

	/**
	 * Sets on Vector3 the data from a Block object,
	 * does block updates and puts the changes to the send queue.
	 *
	 * If $direct is true, it'll send changes directly to players. if false, it'll be queued
	 * and the best way to send queued changes will be done in the next tick.
	 * This way big changes can be sent on a single chunk update packet instead of thousands of packets.
	 *
	 * If $update is true, it'll get the neighbour blocks (6 sides) and update them.
	 * If you are doing big changes, you might want to set this to false, then update manually.
	 *
	 * @param Vector3 $pos
	 * @param Block   $block
	 * @param bool    $direct @deprecated
	 * @param bool    $update
	 *
	 * @return bool Whether the block has been updated or not
	 */
	public function setBlock(Vector3 $pos, Block $block, bool $direct = false, bool $update = true) : bool{
		$pos = $pos->floor();
		if(!$this->isInWorld($pos->x, $pos->y, $pos->z)){
			return false;
		}

		$this->timings->setBlock->startTiming();

		if($this->getChunk((int)$pos->x >> 4, (int)$pos->z >> 4, true)->setBlock($pos->x & 0x0f, $pos->y, $pos->z & 0x0f, $block->getId(), $block->getDamage())){
			if(!($pos instanceof Position)){
				$pos = $this->temporalPosition->setComponents($pos->x, $pos->y, $pos->z);
			}

			$block->position($pos);
			$block->clearCaches();

			$chunkX = (int)$pos->x >> 4;
			$chunkZ = (int)$pos->z >> 4;

			$chunkHash = Level::chunkHash($chunkX, $chunkZ);
			$blockHash = Level::blockHash($pos->x, $pos->y, $pos->z);

			unset($this->blockCache[$chunkHash][$blockHash]);

			if($direct === true){
				$this->sendBlocks($this->getChunkPlayers((int)$pos->x >> 4, (int)$pos->z >> 4), [$block], UpdateBlockPacket::FLAG_ALL_PRIORITY);
				unset($this->changedBlocks[$chunkHash][$blockHash]);
			}else{
				if(!isset($this->changedBlocks[$chunkHash])){
					$this->changedBlocks[$chunkHash] = [];
				}

				$this->changedBlocks[$chunkHash][$blockHash] = clone $block;
			}

			foreach($this->getChunkListeners((int)$pos->x >> 4, (int)$pos->z >> 4) as $listener){
				$listener->onBlockChanged($block);
			}

			if($update === true){
				$this->updateAllLight($block);

				$ev = new BlockUpdateEvent($block);
				$ev->call();
				if(!$ev->isCancelled()){
                    foreach($this->getNearbyEntities(new AxisAlignedBB($block->x - 1, $block->y - 1, $block->z - 1, $block->x + 2, $block->y + 2, $block->z + 2)) as $entity){
                        $entity->onNearbyBlockChange();
                    }
                    $ev->getBlock()->onNearbyBlockChange();
					$this->scheduleNeighbourBlockUpdates($pos);
				}
			}

			$this->timings->setBlock->stopTiming();

			return true;
		}

		$this->timings->setBlock->stopTiming();

		return false;
	}

	/**
	 * @param Tile $tile
	 */
	public function onTileChanged(Tile $tile) : void{
		foreach($this->getChunkListeners((int)$tile->x >> 4, (int)$tile->z >> 4) as $listener){
			$listener->onTileChanged($tile);
		}
	}

	/**
	 * @param Vector3 $source
	 * @param Item    $item
	 * @param Vector3 $motion
	 * @param int     $delay
	 *
	 * @return DroppedItem|null
	 */
	public function dropItem(Vector3 $source, Item $item, Vector3 $motion = null, int $delay = 10){
		$motion = $motion ?? new Vector3(lcg_value() * 0.2 - 0.1, 0.2, lcg_value() * 0.2 - 0.1);
		$itemTag = $item->nbtSerialize();

		if($item->getId() > 0 and $item->getCount() > 0){
			$nbt = EntityDataHelper::createBaseNBT($source, $motion, lcg_value() * 360)
				->setShort("Health", 5)
				->setTag("Item", $itemTag)
				->setShort("PickupDelay", $delay);
			$itemEntity = Entity::createEntity("Item", $this, $nbt);

			$itemEntity->spawnToAll();
			return $itemEntity;
		}
		return null;
	}

	/**
	 * Tries to break a block using a item, including Player time checks if available
	 * It'll try to lower the durability if Item is a tool, and set it to Air if broken.
	 *
	 * @param Vector3 $vector
	 * @param Item    &$item (if null, can break anything)
	 * @param Player  $player
	 * @param bool    $createParticles
	 *
	 * @return bool
	 */
	public function useBreakOn(Vector3 $vector, Item &$item = null, Player $player = null, bool $createParticles = false) : bool{
		$target = $this->getBlock($vector);
		$affectedBlocks = $target->getAffectedBlocks();

		if($item === null){
			$item = Item::get(Item::AIR, 0, 0);
		}

        $drops = [];
        if($player === null or !$player->isCreative()){
            $drops = array_merge(...array_map(function(Block $block) use ($item) : array{ return $block->getDrops($item); }, $affectedBlocks));
        }

        $xpDrop = 0;
        if($player !== null and !$player->isCreative()){
            $xpDrop = array_sum(array_map(function(Block $block) use ($item) : int{ return $block->getXpDropForTool($item); }, $affectedBlocks));
        }

		if($player !== null){
			$ev = new BlockBreakEvent($player, $target, $item, $player->isCreative(), $drops, $xpDrop);

			if($target instanceof Air or ($player->isSurvival() and $item instanceof Item and !$target->isBreakable($item)) or $player->isSpectator()){
				$ev->setCancelled();
			}

			if($player->isAdventure(true) and !$ev->isCancelled()){
				$tag = $item->getNamedTag();

				$canBreak = false;
				if($tag->hasTag("CanDestroy", ListTag::class)){
					foreach($tag->getListTag("CanDestroy") as $v){
						if($v instanceof StringTag){
							$entry = Item::fromString($v->getValue());
							if($entry->getId() > 0 and $entry->getBlock() !== null and $entry->getBlock()->getId() === $target->getId()){
								$canBreak = true;
								break;
							}
						}
					}
				}

				$ev->setCancelled(!$canBreak);
			}

			$ev->call();
			if($ev->isCancelled()){
				return false;
			}

            $drops = $ev->getDrops();
            $xpDrop = $ev->getXpDropAmount();
		}elseif($item !== null and !$target->isBreakable($item)){
			return false;
		}

		foreach($affectedBlocks as $t){
			$this->destroyBlockInternal($t, $item, $player, $createParticles);
		}

        $item->onDestroyBlock($target);

		if(!empty($drops)){
			$dropPos = $target->add(0.5, 0.5, 0.5);
			foreach($drops as $drop){
				if(!$drop->isNull()){
					$this->dropItem($dropPos, $drop);
				}
			}
		}

        if($xpDrop > 0){
            $this->dropExperience($target->add(0.5, 0.5, 0.5), $xpDrop);
        }

		return true;
	}

	private function destroyBlockInternal(Block $target, Item $item, ?Player $player = null, bool $createParticles = false) : void{
		$above = $this->getBlock(new Vector3($target->x, $target->y + 1, $target->z));
		if($above !== null){
			if($above->getId() === Item::FIRE){
				$this->setBlock($above, new Air(), true);
			}
		}

		if($createParticles){
			$this->addParticle(new DestroyBlockParticle($target->add(0.5, 0.5, 0.5), $target));
		}

		$target->onBreak($item);

		$tile = $this->getTile($target);
		if($tile !== null){
			if($tile instanceof InventoryHolder){
				if($tile instanceof Chest){
					$tile->unpair();
				}

				foreach($tile->getInventory()->getContents() as $chestItem){
					$this->dropItem($target, $chestItem);
				}
			}

			$tile->close();
		}
	}

    /**
     * @param Vector3 $vector
     * @param Item $item
     * @param int $face
     * @param Vector3|null $clickVector
     * @param Player|null $player
     * @param bool $playSound
     * @return bool
     */
	public function useItemOn(Vector3 $vector, Item &$item, int $face, Vector3 $clickVector = null, Player $player = null, bool $playSound = false) : bool{
		$blockClicked = $this->getBlock($vector);
		$blockReplace = $blockClicked->getSide($face);

        if($clickVector === null){
            $clickVector = new Vector3(0.0, 0.0, 0.0);
        }

        if(!$this->isInWorld($blockReplace->x, $blockReplace->y, $blockReplace->z)){
            //TODO: build height limit messages for custom world heights and mcregion cap
            return false;
        }

		if($blockClicked->getId() === Item::AIR){
			return false;
		}

		if($player !== null){
			$ev = new PlayerInteractEvent($player, $item, $blockClicked, $clickVector, $face, PlayerInteractEvent::RIGHT_CLICK_BLOCK);
            if($player->isSpectator()){
                $ev->setCancelled(); //set it to cancelled so plugins can bypass this
            }

			$ev->call();
			if(!$ev->isCancelled()) {
                if ((!$player->isSneaking() or $item->isNull()) and $blockClicked->onActivate($item, $player)) {
                    return true;
                }

                if ($item->onActivate($player, $blockReplace, $blockClicked, $face, $clickVector)) {
                    return true;
                }

				if($player instanceof BedrockPlayer and $item instanceof Bucket){
					$pk = new BedrockUpdateBlockPacket();
					$pk->x = $blockClicked->x;
					$pk->y = $blockClicked->y;
					$pk->z = $blockClicked->z;
					$pk->blockRuntimeId = BlockPalette::getRuntimeFromLegacyId(Block::AIR);
					$pk->dataLayerId = BedrockUpdateBlockPacket::DATA_LAYER_LIQUID;
					$player->sendDataPacket($pk);
				}
			}else {
                if ($player instanceof BedrockPlayer and $item instanceof Bucket) {
                    $pk = new BedrockUpdateBlockPacket();
                    $pk->x = $blockClicked->x;
                    $pk->y = $blockClicked->y;
                    $pk->z = $blockClicked->z;
                    $pk->blockRuntimeId = BlockPalette::getRuntimeFromLegacyId(Block::AIR);
                    $pk->dataLayerId = BedrockUpdateBlockPacket::DATA_LAYER_LIQUID;
                    $player->sendDataPacket($pk);
                }

                return false;
            }
		}elseif($blockClicked->onActivate($item, $player)){
			return true;
		}

        if($item->canBePlaced()){
            $hand = $item->getBlock();
            $hand->position($blockReplace);
        }else{
            return false;
        }

        if($hand->canBePlacedAt($blockClicked, $clickVector, $face, true)){
            $blockReplace = $blockClicked;
            $hand->position($blockReplace);
        }elseif(!$hand->canBePlacedAt($blockReplace, $clickVector, $face, false)){
            return false;
        }

        if($hand->isSolid()){
            foreach($hand->getCollisionBoxes() as $collisionBox){
                if(count($collidingEntities = $this->getCollidingEntities($collisionBox)) > 0){
                    $collidingEntities = array_filter($collidingEntities, function(Entity $entity) : bool{
                        return !($entity instanceof DroppedItem);
                    });

                    if(count($collidingEntities) > 0){
                        return false; // Entity in block
                    }
                }
            }
        }

		if($player !== null){
			$ev = new BlockPlaceEvent($player, $hand, $blockReplace, $blockClicked, $item);

            if($player->isAdventure(true) and !$ev->isCancelled()){
                $canPlace = false;
                $tag = $item->getNamedTag();
                if($tag->hasTag("CanPlaceOn", ListTag::class)){
                    foreach($tag->getListTag("CanPlaceOn") as $v){
                        if($v instanceof StringTag){
                            $entry = Item::fromString($v->getValue());
                            if($entry->getId() > 0 and $entry->getBlock() !== null and $entry->getBlock()->getId() === $blockClicked->getId()){
                                $canPlace = true;
                                break;
                            }
                        }
                    }
                }

                $ev->setCancelled(!$canPlace);
            }

			$ev->call();
			if($ev->isCancelled()){
				return false;
			}
		}

		if($hand->place($item, $blockReplace, $blockClicked, $face, $clickVector, $player) === false){
			return false;
		}

		if($playSound){
			$this->broadcastLevelSoundEvent($hand, LevelSoundEventPacket::SOUND_PLACE, $hand->getId(), 1);
		}

        $item->pop();
		return true;
	}

	/**
	 * @param int $entityId
	 *
	 * @return Entity|null
	 */
	public function getEntity(int $entityId){
		return $this->entities[$entityId] ?? null;
	}

	/**
	 * Gets the list of all the entities in this level
	 *
	 * @return Entity[]
	 */
	public function getEntities() : array{
		return $this->entities;
	}

	/**
	 * Returns the entities colliding the current one inside the AxisAlignedBB
	 *
	 * @param AxisAlignedBB $bb
	 * @param Entity|null   $entity
	 *
	 * @return Entity[]
	 */
	public function getCollidingEntities(AxisAlignedBB $bb, Entity $entity = null) : array{
		$nearby = [];

		if($entity === null or $entity->canCollide){
			$minX = (int) floor(($bb->minX - 2) / 16);
			$maxX = (int) ceil(($bb->maxX + 2) / 16);
			$minZ = (int) floor(($bb->minZ - 2) / 16);
			$maxZ = (int) ceil(($bb->maxZ + 2) / 16);

			for($x = $minX; $x <= $maxX; ++$x){
				for($z = $minZ; $z <= $maxZ; ++$z){
					foreach($this->getChunkEntities($x, $z) as $ent){
						if(($entity === null or ($ent !== $entity and $entity->canCollideWith($ent))) and $ent->boundingBox->intersectsWith($bb)){
							$nearby[] = $ent;
						}
					}
				}
			}
		}

		return $nearby;
	}

	/**
	 * Returns the entities near the current one inside the AxisAlignedBB
	 *
	 * @param AxisAlignedBB $bb
	 * @param Entity|null   $entity
	 *
	 * @return Entity[]
	 */
	public function getNearbyEntities(AxisAlignedBB $bb, ?Entity $entity = null) : array{
		$nearby = [];

		$minX = (int) floor(($bb->minX - 2) / 16);
		$maxX = (int) ceil(($bb->maxX + 2) / 16);
		$minZ = (int) floor(($bb->minZ - 2) / 16);
		$maxZ = (int) ceil(($bb->maxZ + 2) / 16);

		for($x = $minX; $x <= $maxX; ++$x){
			for($z = $minZ; $z <= $maxZ; ++$z){
				foreach($this->getChunkEntities($x, $z) as $ent){
					if($ent !== $entity and $ent->boundingBox->intersectsWith($bb)){
						$nearby[] = $ent;
					}
				}
			}
		}

		return $nearby;
	}

    /**
     * Returns the closest Entity to the specified position, within the given radius.
     *
     * @param Vector3 $pos
     * @param float $maxDistance
     * @param string $entityType Class of entity to use for instanceof
     * @param bool $includeDead Whether to include entitites which are dead
     *
     * @return Entity|null an entity of type $entityType, or null if not found
     */
    public function getNearestEntity(Vector3 $pos, float $maxDistance, string $entityType = Entity::class, bool $includeDead = false) : ?Entity{
        assert(is_a($entityType, Entity::class, true));

        $minX = ((int) floor($pos->x - $maxDistance)) >> 4;
        $maxX = ((int) floor($pos->x + $maxDistance)) >> 4;
        $minZ = ((int) floor($pos->z - $maxDistance)) >> 4;
        $maxZ = ((int) floor($pos->z + $maxDistance)) >> 4;

        $currentTargetDistSq = $maxDistance ** 2;

        /** @var Entity|null $currentTarget */
        $currentTarget = null;

        for($x = $minX; $x <= $maxX; ++$x){
            for($z = $minZ; $z <= $maxZ; ++$z){
                foreach($this->getChunkEntities($x, $z) as $entity){
                    if(!($entity instanceof $entityType) or $entity->closed or $entity->isFlaggedForDespawn() or (!$includeDead and !$entity->isAlive())){
                        continue;
                    }
                    $distSq = $entity->distanceSquared($pos);
                    if($distSq < $currentTargetDistSq){
                        $currentTargetDistSq = $distSq;
                        $currentTarget = $entity;
                    }
                }
            }
        }

        return $currentTarget;
    }

    public function getNearbyExperienceOrb(AxisAlignedBB $bb) : array{
        $nearby = [];

        foreach($this->getNearbyEntities($bb) as $entity){
            if($entity instanceof XPOrb){
                $nearby[] = $entity;
            }
        }

        return $nearby;
    }

	/**
	 * Returns a list of the Tile entities in this level
	 *
	 * @return Tile[]
	 */
	public function getTiles() : array{
		return $this->tiles;
	}

	/**
	 * @param $tileId
	 *
	 * @return Tile|null
	 */
	public function getTileById(int $tileId){
		return $this->tiles[$tileId] ?? null;
	}

	/**
	 * Returns a list of the players in this level
	 *
	 * @return Player[]
	 */
	public function getPlayers() : array{
		return $this->players;
	}

	/**
	 * @return ChunkLoader[]
	 */
	public function getLoaders() : array{
		return $this->loaders;
	}

	/**
	 * Returns the Tile in a position, or null if not found.
	 *
	 * Note: This method wraps getTileAt(). If you're guaranteed to be passing integers, and you're using this method
	 * in performance-sensitive code, consider using getTileAt() instead of this method for better performance.
	 *
	 * @deprecated
	 * @param Vector3 $pos
	 *
	 * @return Tile|null
	 */
	public function getTile(Vector3 $pos) : ?Tile{
		return $this->getTileAt((int) floor($pos->x), (int) floor($pos->y), (int) floor($pos->z));
	}

	/**
	 * Returns the tile at the specified x,y,z coordinates, or null if it does not exist.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 *
	 * @return Tile|null
	 */
	public function getTileAt(int $x, int $y, int $z) : ?Tile{
		$chunk = $this->getChunk((int)$x >> 4, (int)$z >> 4);
		if($chunk !== null){
			return $chunk->getTile($x & 0x0f, $y, $z & 0x0f);
		}
		return null;
	}

	/**
	 * Returns a list of the entities on a given chunk
	 *
	 * @param int $X
	 * @param int $Z
	 *
	 * @return Entity[]
	 */
	public function getChunkEntities($X, $Z) : array{
		return ($chunk = $this->getChunk($X, $Z)) !== null ? $chunk->getEntities() : [];
	}

	/**
	 * Gives a list of the Tile entities on a given chunk
	 *
	 * @param int $X
	 * @param int $Z
	 *
	 * @return Tile[]
	 */
	public function getChunkTiles($X, $Z) : array{
		return ($chunk = $this->getChunk($X, $Z)) !== null ? $chunk->getTiles() : [];
	}

	/**
	 * Gets the raw block id.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 *
	 * @return int 0-255
	 */
	public function getBlockIdAt(int $x, int $y, int $z) : int{
		return $this->getChunk((int)$x >> 4, (int)$z >> 4, true)->getBlockId($x & 0x0f, $y, $z & 0x0f);
	}

	/**
	 * Sets the raw block id.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $id 0-255
	 */
	public function setBlockIdAt(int $x, int $y, int $z, int $id){
		unset($this->blockCache[$chunkHash = Level::chunkHash((int)$x >> 4, (int)$z >> 4)][$blockHash = Level::blockHash($x, $y, $z)]);
		$this->getChunk((int)$x >> 4, (int)$z >> 4, true)->setBlockId($x & 0x0f, $y, $z & 0x0f, $id & 0xff);

		if(!isset($this->changedBlocks[$chunkHash])){
			$this->changedBlocks[$chunkHash] = [];
		}
		$this->changedBlocks[$chunkHash][$blockHash] = $v = new Vector3($x, $y, $z);
		foreach($this->getChunkListeners((int)$x >> 4, (int)$z >> 4) as $loader){
			$loader->onBlockChanged($v);
		}
	}

	/**
	 * Gets the raw block extra data
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 *
	 * @return int 16-bit
	 */
	public function getBlockExtraDataAt(int $x, int $y, int $z) : int{
		return $this->getChunk((int)$x >> 4, (int)$z >> 4, true)->getBlockExtraData($x & 0x0f, $y, $z & 0x0f);
	}

	/**
	 * Sets the raw block metadata.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $id
	 * @param int $data
	 */
	public function setBlockExtraDataAt(int $x, int $y, int $z, int $id, int $data){
		$this->getChunk((int)$x >> 4, (int)$z >> 4, true)->setBlockExtraData($x & 0x0f, $y, $z & 0x0f, ($data << 8) | $id);

		$this->sendBlockExtraData($x, $y, $z, $id, $data);
	}

	/**
	 * Gets the raw block metadata
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 *
	 * @return int 0-15
	 */
	public function getBlockDataAt(int $x, int $y, int $z) : int{
		return $this->getChunk($x >> 4, $z >> 4, true)->getBlockData($x & 0x0f, $y, $z & 0x0f);
	}

	/**
	 * Sets the raw block metadata.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $data 0-15
	 */
	public function setBlockDataAt(int $x, int $y, int $z, int $data){
		unset($this->blockCache[$chunkHash = Level::chunkHash($x >> 4, $z >> 4)][$blockHash = Level::blockHash($x, $y, $z)]);
		$this->getChunk((int)$x >> 4, (int)$z >> 4, true)->setBlockData($x & 0x0f, $y, $z & 0x0f, $data & 0x0f);

		if(!isset($this->changedBlocks[$chunkHash])){
			$this->changedBlocks[$chunkHash] = [];
		}
		$this->changedBlocks[$chunkHash][$blockHash] = $v = new Vector3($x, $y, $z);
		foreach($this->getChunkListeners((int)$x >> 4, (int)$z >> 4) as $loader){
			$loader->onBlockChanged($v);
		}
	}

	/**
	 * Gets the raw block skylight level
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 *
	 * @return int 0-15
	 */
	public function getBlockSkyLightAt(int $x, int $y, int $z) : int{
		return $this->getChunk((int)$x >> 4, (int)$z >> 4, true)->getBlockSkyLight($x & 0x0f, $y, $z & 0x0f);
	}

	/**
	 * Sets the raw block skylight level.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $level 0-15
	 */
	public function setBlockSkyLightAt(int $x, int $y, int $z, int $level){
		$this->getChunk((int)$x >> 4, (int)$z >> 4, true)->setBlockSkyLight($x & 0x0f, $y, $z & 0x0f, $level & 0x0f);
	}

	/**
	 * Gets the raw block light level
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 *
	 * @return int 0-15
	 */
	public function getBlockLightAt(int $x, int $y, int $z) : int{
		return $this->getChunk((int)$x >> 4, (int)$z >> 4, true)->getBlockLight($x & 0x0f, $y, $z & 0x0f);
	}

	/**
	 * Sets the raw block light level.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $level 0-15
	 */
	public function setBlockLightAt(int $x, int $y, int $z, int $level){
		$this->getChunk((int)$x >> 4, (int)$z >> 4, true)->setBlockLight($x & 0x0f, $y, $z & 0x0f, $level & 0x0f);
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return int
	 */
	public function getBiomeId(int $x, int $z) : int{
		return $this->getChunk((int)$x >> 4, (int)$z >> 4, true)->getBiomeId($x & 0x0f, $z & 0x0f);
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return int
	 */
	public function getHeightMap(int $x, int $z) : int{
		return $this->getChunk((int)$x >> 4, (int)$z >> 4, true)->getHeightMap($x & 0x0f, $z & 0x0f);
	}

	/**
	 * @param int $x
	 * @param int $z
	 * @param int $biomeId
	 */
	public function setBiomeId(int $x, int $z, int $biomeId){
		$this->getChunk((int)$x >> 4, (int)$z >> 4, true)->setBiomeId($x & 0x0f, $z & 0x0f, $biomeId);
	}

	/**
	 * @param int $x
	 * @param int $z
	 * @param int $value
	 */
	public function setHeightMap(int $x, int $z, int $value){
		$this->getChunk((int)$x >> 4, (int)$z >> 4, true)->setHeightMap($x & 0x0f, $z & 0x0f, $value);
	}

	/**
	 * @return Chunk[]
	 */
	public function getChunks() : array{
		return $this->chunks;
	}

	/**
	 * Gets the Chunk object
	 *
	 * @param int  $x
	 * @param int  $z
	 * @param bool $create Whether to generate the chunk if it does not exist
	 *
	 * @return Chunk|null
	 */
	public function getChunk(int|float $x, int|float $z, bool $create = false) : ?Chunk{
		if(isset($this->chunks[$index = Level::chunkHash((int)$x, (int)$z)])){
			return $this->chunks[$index];
		}elseif($this->loadChunk((int)$x, (int)$z, $create)){
			return $this->chunks[$index];
		}

		return null;
	}

	/**
	 * Returns the chunks adjacent to the specified chunk.
	 *
	 * @param int $x
	 * @param int $z
	 *
	 * @return Chunk[]
	 */
	public function getAdjacentChunks(int $x, int $z) : array{
		$result = [];
		for($xx = 0; $xx <= 2; ++$xx){
			for($zz = 0; $zz <= 2; ++$zz){
				$i = $zz * 3 + $xx;
				if($i === 4){
					continue; //center chunk
				}
				$result[$i] = $this->getChunk($x + $xx - 1, $z + $zz - 1, false);
			}
		}

		return $result;
	}

	public function lockChunk(int $chunkX, int $chunkZ) : void{
		$chunkHash = Level::chunkHash($chunkX, $chunkZ);
		if(isset($this->chunkLock[$chunkHash])){
			throw new \InvalidArgumentException("Chunk $chunkX $chunkZ is already locked");
		}
		$this->chunkLock[$chunkHash] = true;
	}

	public function unlockChunk(int $chunkX, int $chunkZ) : void{
		unset($this->chunkLock[Level::chunkHash($chunkX, $chunkZ)]);
	}

	public function isChunkLocked(int $chunkX, int $chunkZ) : bool{
		return isset($this->chunkLock[Level::chunkHash($chunkX, $chunkZ)]);
	}

	public function generateChunkCallback(int $x, int $z, ?Chunk $chunk){
		Timings::$generationCallbackTimer->startTiming();
		if(isset($this->chunkPopulationQueue[$index = Level::chunkHash($x, $z)])){
			for($xx = -1; $xx <= 1; ++$xx){
				for($zz = -1; $zz <= 1; ++$zz){
					$this->unlockChunk($x + $xx, $z + $zz);
				}
			}
			unset($this->chunkPopulationQueue[$index]);

			if($chunk !== null){
				$oldChunk = $this->getChunk($x, $z, false);
				$this->setChunk($x, $z, $chunk, false);
				if(($oldChunk === null or !$oldChunk->isPopulated()) and $chunk->isPopulated()){
					(new ChunkPopulateEvent($this, $chunk))->call();

					foreach($this->getChunkListeners($x, $z) as $loader){
						$loader->onChunkPopulated($chunk);
					}
				}
			}
		}elseif($this->isChunkLocked($x, $z)){
			$this->unlockChunk($x, $z);
			if($chunk !== null){
				$this->setChunk($x, $z, $chunk, false);
			}
		}elseif($chunk !== null){
			$this->setChunk($x, $z, $chunk, false);
		}
		Timings::$generationCallbackTimer->stopTiming();
	}
	/**
	 * @param int   $chunkX
	 * @param int   $chunkZ
	 * @param Chunk $chunk
	 * @param bool  $unload
	 */
	public function setChunk(int $chunkX, int $chunkZ, Chunk $chunk = null, bool $unload = true){
		if($chunk === null){
			return;
		}
		$chunkHash = Level::chunkHash($chunkX, $chunkZ);
		$oldChunk = $this->getChunk($chunkX, $chunkZ, false);
		if($unload and $oldChunk !== null){
			$this->unloadChunk($chunkX, $chunkZ, false, false);

			$this->provider->setChunk($chunkX, $chunkZ, $chunk);
			$this->chunks[$chunkHash] = $chunk;
		}else{
			$oldEntities = $oldChunk !== null ? $oldChunk->getEntities() : [];
			$oldTiles = $oldChunk !== null ? $oldChunk->getTiles() : [];

			foreach($oldEntities as $entity){
				$chunk->addEntity($entity);
				$oldChunk->removeEntity($entity);
				$entity->chunk = $chunk;
			}

			foreach($oldTiles as $tile){
				$chunk->addTile($tile);
				$oldChunk->removeTile($tile);
				$tile->chunk = $chunk;
			}

			$this->provider->setChunk($chunkX, $chunkZ, $chunk);
			$this->chunks[$chunkHash] = $chunk;
		}

		unset($this->chunks[$chunkHash]);
		unset($this->chunkTickList[$chunkHash]);
		unset($this->blockCache[$chunkHash]);
		unset($this->changedBlocks[$chunkHash]);

		$chunk->setChanged();

		if(!$this->isChunkInUse($chunkX, $chunkZ)){
			$this->unloadChunkRequest($chunkX, $chunkZ);
		}else{
			foreach($this->getChunkListeners($chunkX, $chunkZ) as $listener){
				$listener->onChunkChanged($chunk);
			}
		}
	}

    /**
     * Add an experience orb
     *
     * @param Vector3 $pos
     * @param int $amount
     * @return list<XPOrb>
     */
    public function dropExperience(Vector3 $pos, int $amount = 1):array{
        $orbs = [];

        foreach(XPOrb::splitIntoOrbSizes($amount) as $split){
            $nbt = EntityDataHelper::createBaseNBT(
                $pos->add(0, 0.5, 0),
                new Vector3((lcg_value() * 0.2 - 0.1) * 2, lcg_value() * 0.4, (lcg_value() * 0.2 - 0.1) * 2),
                lcg_value() * 360,
                0
            );
            $nbt->setLong("Experience", $split);

            $expOrb = new XPOrb($this, $nbt);
            $expOrb->spawnToAll();

            $orbs[] = $expOrb;
        }
        return $orbs;
    }


    /**
	 * Gets the highest block Y value at a specific $x and $z
	 *
	 * @param int $x
	 * @param int $z
	 *
	 * @return int 0-255
	 */
	public function getHighestBlockAt(int $x, int $z) : int{
		return $this->getChunk((int)$x >> 4, (int)$z >> 4, true)->getHighestBlockAt($x & 0x0f, $z & 0x0f);
	}

    public function canBlockSeeSky(Vector3 $pos) : bool{
        return $this->getHighestBlockAt($pos->getFloorX(), $pos->getFloorZ()) < $pos->getY();
    }

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return bool
	 */
	public function isChunkLoaded(int $x, int $z) : bool{
		return isset($this->chunks[Level::chunkHash($x, $z)]) or $this->provider->isChunkLoaded($x, $z);
	}

    /**
     * Returns whether the given position is in a loaded area of terrain.
     */
    public function isInLoadedTerrain(Vector3 $pos) : bool{
        return $this->isChunkLoaded((int)$pos->getFloorX() >> 4, (int)$pos->getFloorZ() >> 4);
    }

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return bool
	 */
	public function isChunkGenerated(int $x, int $z) : bool{
		$chunk = $this->getChunk($x, $z);
		return $chunk !== null ? $chunk->isGenerated() : false;
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return bool
	 */
	public function isChunkPopulated(int $x, int $z) : bool{
		$chunk = $this->getChunk($x, $z);
		return $chunk !== null ? $chunk->isPopulated() : false;
	}

	/**
	 * Returns a Position pointing to the spawn
	 *
	 * @return Position
	 */
	public function getSpawnPosition() : Position{
		return Position::fromObject($this->provider->getSpawn(), $this);
	}

	/**
	 * @deprecated
	 * @return Position
	 */
	public function getSpawnLocation() : Position{
		return $this->getSpawnPosition();
	}

	/**
	 * Sets the level spawn position
	 *
	 * @param Vector3 $pos
	 */
	public function setSpawnPosition(Vector3 $pos) : void{
		$previousSpawn = $this->getSpawnPosition();
		$this->provider->setSpawn($pos);
		(new SpawnChangeEvent($this, $previousSpawn))->call();
	}

	/**
	 * @deprecated
	 * @param Vector3 $pos
	 */
	public function setSpawnLocation(Vector3 $pos){
		$this->setSpawnPosition($pos);
	}

	/**
	 * Removes the entity from the level index
	 *
	 * @param Entity $entity
	 *
	 * @throws LevelException
	 */
	public function removeEntity(Entity $entity){
		if($entity->getLevel() !== $this){
			throw new LevelException("Invalid Entity level");
		}

		if($entity instanceof Player){
			unset($this->players[$entity->getId()]);
			$this->checkSleep();
		}

		unset($this->entities[$entity->getId()]);
		unset($this->updateEntities[$entity->getId()]);
	}

	/**
	 * @param Entity $entity
	 *
	 * @throws LevelException
	 */
	public function addEntity(Entity $entity){
		if($entity->getLevel() !== $this){
			throw new LevelException("Invalid Entity level");
		}
		if($entity instanceof Player){
			$this->players[$entity->getId()] = $entity;
		}
		$this->entities[$entity->getId()] = $entity;
	}

	/**
	 * @param Tile $tile
	 *
	 * @throws LevelException
	 */
	public function addTile(Tile $tile){
		if($tile->getLevel() !== $this){
			throw new LevelException("Invalid Tile level");
		}
		$this->tiles[$tile->getId()] = $tile;

		if(($chunk = $this->getChunk((int)$tile->x >> 4, (int)$tile->z >> 4)) !== null){
			$chunk->addTile($tile);
		}
	}

	/**
	 * @param Tile $tile
	 *
	 * @throws LevelException
	 */
	public function removeTile(Tile $tile){
		if($tile->getLevel() !== $this){
			throw new LevelException("Invalid Tile level");
		}

		unset($this->tiles[$tile->getId()]);
		unset($this->updateTiles[$tile->getId()]);

		if(($chunk = $this->getChunk((int)$tile->x >> 4, (int)$tile->z >> 4)) !== null){
			$chunk->removeTile($tile);
		}
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return bool
	 */
	public function isChunkInUse(int $x, int $z) : bool{
		return isset($this->chunkLoaders[$index = Level::chunkHash($x, $z)]) and count($this->chunkLoaders[$index]) > 0;
	}

	/**
	 * @param int  $x
	 * @param int  $z
	 * @param bool $generate
	 *
	 * @return bool
	 *
	 * @throws \InvalidStateException
	 */
	public function loadChunk(int $x, int $z, bool $generate = true) : bool{
		if(isset($this->chunks[$index = Level::chunkHash($x, $z)])){
			return true;
		}

		$this->timings->syncChunkLoadTimer->startTiming();

		$this->cancelUnloadChunkRequest($x, $z);

		if($this->provider === null){
			return false;
		}
		$chunk = $this->provider->getChunk($x, $z, $generate);
		if($chunk === null){
			if($generate){
				throw new \InvalidStateException("Could not create new Chunk");
			}
			return false;
		}

		$this->chunks[$index] = $chunk;
		$chunk->initChunk($this);

		(new ChunkLoadEvent($this, $chunk, !$chunk->isGenerated()))->call();
		
		if(!$chunk->isLightPopulated() and $chunk->isPopulated() and $this->getServer()->getProperty("chunk-ticking.light-updates", false)){
			$this->getServer()->getScheduler()->scheduleAsyncTask(new LightPopulationTask($this, $chunk));
		}

		if($this->isChunkInUse($x, $z)){
			foreach($this->getChunkListeners($x, $z) as $listener){
				$listener->onChunkLoaded($chunk);
			}
		}else{
			$this->unloadChunkRequest($x, $z);
		}

		$this->timings->syncChunkLoadTimer->stopTiming();

		return true;
	}

	private function queueUnloadChunk(int $x, int $z){
		$this->unloadQueue[$index = Level::chunkHash($x, $z)] = microtime(true);
		unset($this->chunkTickList[$index]);
	}

	public function unloadChunkRequest(int $x, int $z, bool $safe = true){
		if(($safe === true and $this->isChunkInUse($x, $z)) or $this->isSpawnChunk($x, $z)){
			return false;
		}

		$this->queueUnloadChunk($x, $z);

		return true;
	}

	public function cancelUnloadChunkRequest(int $x, int $z){
		unset($this->unloadQueue[Level::chunkHash($x, $z)]);
	}

	public function unloadChunk(int $x, int $z, bool $safe = true, bool $trySave = true) : bool{
		if($safe === true and $this->isChunkInUse($x, $z)){
			return false;
		}

		if(!$this->isChunkLoaded($x, $z)){
			return true;
		}

		$this->timings->doChunkUnload->startTiming();

		$index = Level::chunkHash($x, $z);

		$chunk = $this->chunks[$index] ?? null;

		if($chunk !== null){

			if ($chunk->isProtected()) {
				$this->cancelUnloadChunkRequest($x, $z);
				return false;
			}

			$ev = new ChunkUnloadEvent($this, $chunk);
			$ev->call();
			if($ev->isCancelled()){
				$this->timings->doChunkUnload->stopTiming();
				return false;
			}
		}

		try{
			if($chunk !== null){
				if($trySave and $this->getAutoSave() and $chunk->isGenerated()){
					$entities = 0;
					foreach($chunk->getEntities() as $e){
						if($e instanceof Player){
							continue;
						}
						++$entities;
					}

					if($chunk->hasChanged() or count($chunk->getTiles()) > 0 or $entities > 0){
						$this->provider->setChunk($x, $z, $chunk);
						$this->provider->saveChunk($x, $z);
					}
				}

				foreach($this->getChunkListeners($x, $z) as $listener){
					$listener->onChunkUnloaded($chunk);
				}
			}
			$this->provider->unloadChunk($x, $z, $safe);
		}catch(\Throwable $e){
			$logger = $this->server->getLogger();
			$logger->error($this->server->getLanguage()->translateString("pocketmine.level.chunkUnloadError", [$e->getMessage()]));
			$logger->logException($e);
		}

		unset($this->chunks[$index]);
		unset($this->chunkTickList[$index]);

		$this->timings->doChunkUnload->stopTiming();

		return true;
	}

	/**
	 * Returns whether the chunk at the specified coordinates is a spawn chunk
	 *
	 * @param int $X
	 * @param int $Z
	 *
	 * @return bool
	 */
	public function isSpawnChunk(int $X, int $Z) : bool{
		$spawnX = (int)$this->provider->getSpawn()->getX() >> 4;
		$spawnZ = (int)$this->provider->getSpawn()->getZ() >> 4;

		return abs($X - $spawnX) <= 1 and abs($Z - $spawnZ) <= 1;
	}

	/**
	 * @param Vector3 $spawn default null
	 *
	 * @return bool|Position
	 */
	public function getSafeSpawn($spawn = null){
		if(!($spawn instanceof Vector3) or $spawn->y < 1){
			$spawn = $this->getSpawnPosition();
		}
		if($spawn instanceof Vector3){
			$max = $this->provider->getWorldHeight();
			$v = $spawn->floor();
			$chunk = $this->getChunk((int)$v->x >> 4, (int)$v->z >> 4, false);
			$x = $v->x & 0x0f;
			$z = $v->z & 0x0f;
			if($chunk !== null){
				$y = (int) min($max - 2, $v->y);
				$wasAir = ($chunk->getBlockId($x, $y - 1, $z) === 0);
				for(; $y > 0; --$y){
					$b = $chunk->getFullBlock($x, $y, $z);
					$block = Block::get((int)$b >> 4, $b & 0x0f);
					if($this->isFullBlock($block)){
						if($wasAir){
							$y++;
						}
                        break;
					}else{
						$wasAir = true;
					}
				}

				for(; $y >= 0 and $y < $max; ++$y){
					$b = $chunk->getFullBlock($x, $y + 1, $z);
					$block = Block::get((int)$b >> 4, $b & 0x0f);
					if(!$this->isFullBlock($block)){
						$b = $chunk->getFullBlock($x, $y, $z);
						$block = Block::get((int)$b >> 4, $b & 0x0f);
						if(!$this->isFullBlock($block)){
							return new Position($spawn->x, $y === (int) $spawn->y ? $spawn->y : $y, $spawn->z, $this);
						}
					}else{
						++$y;
					}
				}

				$v->y = $y;
			}

			return new Position($spawn->x, $v->y, $spawn->z, $this);
		}

		return false;
	}

	/**
	 * Gets the current time
	 *
	 * @return int
	 */
	public function getTime() : int{
		return (int) $this->time;
	}

    /**
     * Returns the current time of day
     */
    public function getTimeOfDay() : int{
        return $this->time % self::TIME_FULL;
    }

	/**
	 * Returns the Level name
	 *
	 * @return string
	 */
	public function getName() : string{
		return $this->provider->getName();
	}

	/**
	 * Returns the Level folder name
	 *
	 * @return string
	 */
	public function getFolderName() : string{
		return $this->folderName;
	}

	/**
	 * Sets the current time on the level
	 *
	 * @param int $time
	 */
	public function setTime(int $time){
		$this->time = $time;
		$this->sendTime();
	}

	/**
	 * Stops the time for the level, will not save the lock state to disk
	 */
	public function stopTime(){
		$this->stopTime = true;
		$this->sendTime();
	}

	/**
	 * Start the time again, if it was stopped
	 */
	public function startTime(){
		$this->stopTime = false;
		$this->sendTime();
	}

	/**
	 * Gets the level seed
	 *
	 * @return int
	 */
	public function getSeed() : int{
		return $this->provider->getSeed();
	}

	/**
	 * Sets the seed for the level
	 *
	 * @param int $seed
	 */
	public function setSeed(int $seed){
		$this->provider->setSeed($seed);
	}

    /**
     * @param string $str
     * @return int
     */
    public static function getDifficultyFromString(string $str) : int{
        return match (strtolower(trim($str))) {
            "0", "peaceful", "p" => Level::DIFFICULTY_PEACEFUL,
            "1", "easy", "e" => Level::DIFFICULTY_EASY,
            "2", "normal", "n" => Level::DIFFICULTY_NORMAL,
            "3", "hard", "h" => Level::DIFFICULTY_HARD,
            default => -1,
        };

    }

	public function getWorldHeight() : int{
		return $this->provider->getWorldHeight();
	}

    /**
     * @return int
     */
    public function getDifficulty() : int{
        return $this->provider->getDifficulty();
    }

    /**
     * @param int $difficulty
     */
    public function setDifficulty(int $difficulty): void{
        if($difficulty < 0 or $difficulty > 3){
            throw new \InvalidArgumentException("Invalid difficulty level $difficulty");
        }
        $this->provider->setDifficulty($difficulty);

        $this->sendDifficulty();
    }

    /**
     * @param Player[] ...$targets
     */
    public function sendDifficulty(Player ...$targets): void{
        if(count($targets) === 0){
            $targets = $this->getPlayers();
        }

        $pk = new SetDifficultyPacket();
        $pk->difficulty = $this->getDifficulty();
        $this->server->broadcastPacket($targets, $pk);
    }

	public function populateChunk(int $x, int $z, bool $force = false) : bool{
		if(isset($this->chunkPopulationQueue[$index = Level::chunkHash($x, $z)]) or (count($this->chunkPopulationQueue) >= $this->chunkPopulationQueueSize and !$force)){
			return false;
		}

		for($xx = -1; $xx <= 1; ++$xx){
			for($zz = -1; $zz <= 1; ++$zz){
				if($this->isChunkLocked($x + $xx, $z + $zz)){
					return false;
				}
			}
		}

		$chunk = $this->getChunk($x, $z, true);
		if(!$chunk->isPopulated()){
			Timings::$populationTimer->startTiming();
			$this->chunkPopulationQueue[$index] = true;
			for($xx = -1; $xx <= 1; ++$xx){
				for($zz = -1; $zz <= 1; ++$zz){
					$this->lockChunk($x + $xx, $z + $zz);
				}
			}

			$task = new PopulationTask($this, $chunk);
			$workerId = $this->server->getScheduler()->getAsyncPool()->selectWorker();
			if(!isset($this->generatorRegisteredWorkers[$workerId])){
				$this->registerGeneratorToWorker($workerId);
			}
			$this->server->getScheduler()->scheduleAsyncTaskToWorker($task, $workerId);

			Timings::$populationTimer->stopTiming();
			return false;
		}

		return true;
	}

	public function collectArrays() : void{
		Utils::reallocateArray($this->entities);
		Utils::reallocateArray($this->updateEntities);

		Utils::reallocateArray($this->tiles);
		Utils::reallocateArray($this->updateTiles);

		Utils::reallocateArray($this->players);

		foreach($this->players as $player){
			$player->collectArrays();
		}
	}

	public function doChunkGarbageCollection(){
		$this->timings->doChunkGC->startTiming();

		foreach($this->chunks as $index => $chunk){
			if(!isset($this->unloadQueue[$index])){
				Level::getXZ($index, $X, $Z);
				if(!$this->isSpawnChunk($X, $Z)){
					$this->unloadChunkRequest($X, $Z, true);
				}
			}
			$chunk->collectGarbage();
		}

		foreach($this->provider->getLoadedChunks() as $chunk){
			if(!isset($this->chunks[Level::chunkHash($chunk->getX(), $chunk->getZ())])){
				$this->provider->unloadChunk($chunk->getX(), $chunk->getZ(), false);
			}
		}

		$this->provider->doGarbageCollection();

		$this->timings->doChunkGC->stopTiming();
	}

	public function unloadChunks(bool $force = false){
		if(count($this->unloadQueue) > 0){
			$maxUnload = 96;
			$now = microtime(true);
			foreach($this->unloadQueue as $index => $time){
				Level::getXZ($index, $X, $Z);

				if(!$force){
					if($maxUnload <= 0){
						break;
					}elseif($time > ($now - 30)){
						continue;
					}
				}

				//If the chunk can't be unloaded, it stays on the queue
				if($this->unloadChunk($X, $Z, true)){
					unset($this->unloadQueue[$index]);
					--$maxUnload;
				}
			}
		}
	}

	public function setMetadata(string $metadataKey, MetadataValue $newMetadataValue){
		$this->server->getLevelMetadata()->setMetadata($this, $metadataKey, $newMetadataValue);
	}

	public function getMetadata(string $metadataKey){
		return $this->server->getLevelMetadata()->getMetadata($this, $metadataKey);
	}

	public function hasMetadata(string $metadataKey) : bool{
		return $this->server->getLevelMetadata()->hasMetadata($this, $metadataKey);
	}

	public function removeMetadata(string $metadataKey, Plugin $owningPlugin){
		$this->server->getLevelMetadata()->removeMetadata($this, $metadataKey, $owningPlugin);
	}

    public function addEntityMotion(int $chunkX, int $chunkZ, int $entityId, float $x, float $y, float $z):void{
        $pk = new SetEntityMotionPacket();
        $pk->entityRuntimeId = $entityId;
        $pk->motionX = $x;
        $pk->motionY = $y;
        $pk->motionZ = $z;
        $this->addChunkPacket($chunkX, $chunkZ, $pk);
    }

    public function addEntityMovement(int $chunkX, int $chunkZ, int $entityId, float $x, float $y, float $z, float $yaw, float $pitch, $headYaw = null):void{
        $pk = new MoveEntityPacket();
        $pk->entityRuntimeId = $entityId;
        $pk->x = $x;
        $pk->y = $y;
        $pk->z = $z;
        $pk->yaw = $yaw;
        $pk->pitch = $pitch;
        $pk->headYaw = $headYaw ?? $yaw;
        $this->addChunkPacket($chunkX, $chunkZ, $pk);
    }
}


