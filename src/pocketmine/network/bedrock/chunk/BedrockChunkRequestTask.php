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
use pocketmine\level\Level;
use pocketmine\network\bedrock\adapter\ProtocolAdapterFactory;
use pocketmine\network\bedrock\adapter\v475\Protocol475Adapter;
use pocketmine\network\bedrock\adapter\v503\Protocol503Adapter;
use pocketmine\network\bedrock\BedrockPacketBatch;
use pocketmine\network\bedrock\NetworkCompression;
use pocketmine\network\bedrock\palette\block\BlockPalette;
use pocketmine\network\bedrock\protocol\LevelChunkPacket;
use pocketmine\network\bedrock\protocol\ProtocolInfo;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use function assert;

class BedrockChunkRequestTask extends AsyncTask{

	/** @var int */
	protected $levelId;
    /** @var int */
    protected $dimensionId;

	/** @var int */
	protected $chunkX;
	/** @var int */
	protected $chunkZ;
	/** @var string */
	protected $tileData = "";
	/** @var string */
	protected $chunk;

	/** @var int */
	protected $compressionLevel;

	/** @var int */
	protected $protocolVersion;

	public function __construct(Level $level, Chunk $chunk, int $protocolVersion){
		$this->levelId = $level->getId();
        $this->dimensionId = $level->getDimension();
		$this->compressionLevel = NetworkCompression::$LEVEL;

		$this->chunkX = $chunk->getX();
		$this->chunkZ = $chunk->getZ();

		$this->tileData = BedrockChunkSerializer::serializeTiles($chunk);
		$this->chunk = $chunk->fastSerialize();

		$this->protocolVersion = $protocolVersion;
	}

	public function onRun(){
		BlockPalette::lazyInit();
		ProtocolAdapterFactory::lazyInit();

		$chunk = Chunk::fastDeserialize($this->chunk);

		$pk = new LevelChunkPacket();
		$pk->chunkX = $this->chunkX;
		$pk->chunkZ = $this->chunkZ;
        $pk->dimensionId = $this->dimensionId;
		$pk->subChunkCount = $chunk->getSubChunkSendCount();
		if($this->protocolVersion >= Protocol475Adapter::PROTOCOL_VERSION){
			$pk->subChunkCount += BedrockChunkSerializer::LOWER_PADDING_SIZE;
		}

		$protocolAdapter = ProtocolAdapterFactory::get($this->protocolVersion);
		if($protocolAdapter !== null){
			$c = function(int $blockId, int $meta) use ($protocolAdapter) : int{
				return $protocolAdapter->translateBlockId(BlockPalette::getRuntimeFromLegacyId($blockId, $meta));
			};
		}else{
			$c = Closure::fromCallable([BlockPalette::class, "getRuntimeFromLegacyId"]);
		}
		if($this->protocolVersion >= Protocol503Adapter::PROTOCOL_VERSION){
			$pk->data = BedrockChunkSerializer::serialize($chunk, $c, $this->tileData);
		}elseif($this->protocolVersion >= Protocol475Adapter::PROTOCOL_VERSION){
			$pk->data = Pre503ChunkSerializer::serialize($chunk, $c, $this->tileData);
		}else{
			$pk->data = Pre475ChunkSerializer::serialize($chunk, $c, $this->tileData);
		}

		if($protocolAdapter !== null){
			$pk = $protocolAdapter->processServerToClient($pk);
			assert($pk !== null);
		}

		$stream = new BedrockPacketBatch();
		$stream->putPacket($pk);

		$this->setResult(ProtocolInfo::MCPE_RAKNET_PACKET_ID . NetworkCompression::compress($stream->buffer, $this->compressionLevel), false);
	}

	public function onCompletion(Server $server){
		$level = $server->getLevel($this->levelId);
		if($level instanceof Level){
			if($this->hasResult()){
				BedrockChunkCache::getInstance($level, $this->protocolVersion)->requestCallback($this->chunkX, $this->chunkZ, $this->getResult());
			}else{
				$server->getLogger()->error("Chunk request for level #" . $this->levelId . ", x=" . $this->chunkX . ", z=" . $this->chunkZ . " doesn't have any result data");
			}
		}else{
			$server->getLogger()->debug("Dropped chunk task due to level not loaded");
		}
	}
}


