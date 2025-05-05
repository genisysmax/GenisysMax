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

use pocketmine\level\ChunkListener;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\LevelListener;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\NetworkChunkCache;
use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\tile\Spawnable;
use pocketmine\tile\Tile;
use function spl_object_id;
use function strlen;

/**
 * This class is used by the current Bedrock protocol system to store cached chunk packets for fast resending.
 *
 * TODO: make MemoryManager aware of this so the cache can be destroyed when memory is low
 * TODO: this needs a hook for level unloading
 */
class BedrockChunkCache implements NetworkChunkCache, LevelListener, ChunkListener{
	/** @var self[] */
	private static $instances = [];

	/**
	 * Fetches the ChunkCache instance for the given level. This lazily creates cache systems as needed.
	 *
	 * @param Level $level
	 * @param int $protocol
	 *
	 * @return BedrockChunkCache
	 */
	public static function getInstance(Level $level, int $protocol) : BedrockChunkCache{
		$idx = spl_object_id($level);
		return self::$instances[$idx][$protocol] ?? (self::$instances[$idx][$protocol] = new BedrockChunkCache($level, $protocol));
	}
	
	/**
	 * Removes the ChunkCache instance for the given level.
	 *
	 * @param Level $level
	 */
	public static function removeInstance(Level $level) : void{
		unset(self::$instances[spl_object_id($level)]);
	}

	/** @var Level */
	private $level;

	/** @var int */
	private $protocol;

	/** @var string[] */
	private $caches = [];

	/** @var Player[][] */
	private $pendingRequests = [];

	/** @var Vector3[][] */
	private $changedBlocks = [];

	/** @var Spawnable[][] */
	private $changedTiles = [];

	/** @var AsyncTask[] */
	private $tasks = [];

	/**
	 * @param Level $level
	 */
	private function __construct(Level $level, int $protocol){
		$this->level = $level;
		$this->level->registerLevelListener($this);

		$this->protocol = $protocol;
	}

	public function __destruct(){
		$this->level = null;
		$this->caches = [];
		$this->pendingRequests = [];
		$this->changedBlocks = [];
		$this->changedTiles = [];
		$this->tasks = [];
	}

	public function request(Player $player, int $chunkX, int $chunkZ) : void{
		$this->level->registerChunkListener($this, $chunkX, $chunkZ);
		$chunkHash = Level::chunkHash($chunkX, $chunkZ);

		if($this->sendFromCache($player, $chunkX, $chunkZ)){
			return;
		}

		$this->pendingRequests[$chunkHash][$player->getLoaderId()] = $player;

		if(isset($this->tasks[$chunkHash]) and !$this->tasks[$chunkHash]->isCrashed()){ //Already preparing
			return;
		}

		$this->tasks[$chunkHash] = $task = new BedrockChunkRequestTask($this->level, $this->level->getChunk($chunkX, $chunkZ), $this->protocol);
		$this->level->getServer()->getScheduler()->scheduleAsyncTask($task);
	}

	public function unregister(Player $player, int $chunkX, int $chunkZ) : void{
		$chunkHash = Level::chunkHash($chunkX, $chunkZ);

		if(isset($this->pendingRequests[$chunkHash])){
			unset($this->pendingRequests[$chunkHash][$player->getLoaderId()]);

			if(empty($this->pendingRequests[$chunkHash])){
				unset($this->pendingRequests[$chunkHash]);

				if(!isset($this->caches[$chunkHash]) and isset($this->tasks[$chunkHash])){
					$this->tasks[$chunkHash]->cancelRun();
					unset($this->tasks[$chunkHash]);
				}
			}
		}
	}

	/**
	 * @internal
	 * 
	 * @param int    $chunkX
	 * @param int    $chunkZ
	 * @param string $payload
	 */
	public function requestCallback(int $chunkX, int $chunkZ, string $payload) : void{
		$chunkHash = Level::chunkHash($chunkX, $chunkZ);

		$this->caches[$chunkHash] = $payload;
		unset($this->tasks[$chunkHash]);
		if(isset($this->pendingRequests[$chunkHash])){
			foreach($this->pendingRequests[$chunkHash] as $player){
				$this->sendFromCache($player, $chunkX, $chunkZ);
			}

			if(isset($this->changedTiles[$chunkHash])){
				foreach($this->changedTiles[$chunkHash] as $tile){
					foreach($this->pendingRequests[$chunkHash] as $player){
						$tile->spawnTo($player);
					}
				}
				unset($this->changedTiles[$chunkHash]);
			}
			if(isset($this->changedBlocks[$chunkHash])){
				$this->level->sendBlocks($this->pendingRequests[$chunkHash], $this->changedBlocks[$chunkHash], UpdateBlockPacket::FLAG_ALL);
				unset($this->changedBlocks[$chunkHash]);
			}

			unset($this->pendingRequests[$chunkHash]);
		}
	}

	/**
	 * @param Player $player
	 * @param int    $chunkX
	 * @param int    $chunkZ
	 * 
	 * @return bool
	 */
	private function sendFromCache(Player $player, int $chunkX, int $chunkZ) : bool{
		$chunkHash = Level::chunkHash($chunkX, $chunkZ);
		if(!isset($this->caches[$chunkHash])){
			return false;
		}

		if(isset($player->usedChunks[$chunkHash])){
			$player->sendChunk($chunkX, $chunkZ, $this->caches[$chunkHash]);
		}
		return true;
	}

	private function destroy(int $chunkX, int $chunkZ) : void{
		unset($this->caches[Level::chunkHash($chunkX, $chunkZ)]);
	}

	/**
	 * Restarts an async request for an unresolved chunk.
	 *
	 * @param int $chunkX
	 * @param int $chunkZ
	 */
	private function restartPendingRequest(int $chunkX, int $chunkZ) : void{
		$chunkHash = Level::chunkHash($chunkX, $chunkZ);

		if(isset($this->tasks[$chunkHash])){
			$this->tasks[$chunkHash]->cancelRun();
			unset($this->tasks[$chunkHash]);
		}

		$this->destroy($chunkX, $chunkZ);

		$this->tasks[$chunkHash] = $task = new BedrockChunkRequestTask($this->level, $this->level->getChunk($chunkX, $chunkZ), $this->protocol);
		$this->level->getServer()->getScheduler()->scheduleAsyncTask($task);
	}

	/**
	 * @param int $chunkX
	 * @param int $chunkZ
	 *
	 * @throws \InvalidArgumentException
	 */
	private function destroyOrRestart(int $chunkX, int $chunkZ) : void{
		$chunkHash = Level::chunkHash($chunkX, $chunkZ);
		if(isset($this->pendingRequests[$chunkHash]) and !empty($this->pendingRequests[$chunkHash])){
			//some requesters are waiting for this chunk, so their request needs to be fulfilled
			$this->restartPendingRequest($chunkX, $chunkZ);
		}else{
			//dump the cache, it'll be regenerated the next time it's requested
			$this->destroy($chunkX, $chunkZ);
		}
	}

	/**
	 * @see ChunkListener::onChunkChanged()
	 * @param Chunk $chunk
	 */
	public function onChunkChanged(Chunk $chunk) : void{
		$this->destroyOrRestart($chunk->getX(), $chunk->getZ());
	}

	/**
	 * @see ChunkListener::onBlockChanged()
	 * @param Vector3 $block
	 */
	public function onBlockChanged(Vector3 $block) : void{
		$blockX = $block->getFloorX();
		$blockY = $block->getFloorY();
		$blockZ = $block->getFloorZ();

		$this->destroy($blockX >> 4, $blockZ >> 4);

		$chunkHash = Level::chunkHash($blockX >> 4, $blockZ >> 4);

		if(isset($this->tasks[$chunkHash]) and !$this->tasks[$chunkHash]->isCrashed()){
			//some requesters will still get an outdated chunk. We must send changed blocks too
			$this->changedBlocks[$chunkHash][Level::blockHash($blockX, $blockY, $blockZ)] = $block;
		}
	}

	/**
	 * @see ChunkListener::onTileChanged()
	 * @param Tile $tile
	 */
	public function onTileChanged(Tile $tile){
		if($tile instanceof Spawnable){
			$this->destroy($tile->x >> 4, $tile->z >> 4);

			$chunkHash = Level::chunkHash($tile->x >> 4, $tile->z >> 4);

			if(isset($this->tasks[$chunkHash]) and !$this->tasks[$chunkHash]->isCrashed()){
				//some requesters will still get an outdated chunk. We must send changed tiles too
				$this->changedTiles[$chunkHash][Level::blockHash($tile->x, $tile->y, $tile->z)] = $tile;
			}
		}
	}

	/**
	 * @see ChunkListener::onChunkUnloaded()
	 * @param Chunk $chunk
	 */
	public function onChunkUnloaded(Chunk $chunk) : void{
		$this->destroy($chunk->getX(), $chunk->getZ());
		$this->level->unregisterChunkListener($this, $chunk->getX(), $chunk->getZ());

		$chunkHash = Level::chunkHash($chunk->getX(), $chunk->getZ());
		if(isset($this->tasks[$chunkHash])){
			$this->tasks[$chunkHash]->cancelRun();
			unset($this->tasks[$chunkHash]);
		}
		unset($this->pendingRequests[$chunkHash]);
		unset($this->changedBlocks[$chunkHash]);
		unset($this->changedTiles[$chunkHash]);
	}

	/**
	 * @see LevelListener::onLevelUnloaded()
	 * @param Level $level
	 */
	public function onLevelUnloaded(Level $level) : void{
		self::removeInstance($level);
	}

	/**
	 * @see ChunkListener::onChunkLoaded()
	 * @param Chunk $chunk
	 */
	public function onChunkLoaded(Chunk $chunk) : void{
		//NOOP
	}

	/**
	 * @see ChunkListener::onChunkPopulated()
	 * @param Chunk $chunk
	 */
	public function onChunkPopulated(Chunk $chunk) : void{
		//NOOP - we also receive this in onChunkChanged, so we don't care here
	}

	public function calculateCacheSize() : int{
		$result = 0;
		foreach($this->caches as $cache){
			$result += strlen($cache);
		}
		return $result;
	}
}


