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

namespace pocketmine\network\mcpe\chunk;

use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\network\mcpe\MCPEPacketBatch;
use pocketmine\network\mcpe\NetworkCompression;
use pocketmine\network\mcpe\protocol\FullChunkDataPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class MCPEChunkRequestTask extends AsyncTask{

	/** @var int */
	protected $levelId;

	/** @var int */
	protected $chunkX;
	/** @var int */
	protected $chunkZ;
	/** @var string */
	protected $chunkData;

	/** @var int */
	protected $compressionLevel;

	public function __construct(Level $level, Chunk $chunk){
		$this->levelId = $level->getId();
		$this->compressionLevel = NetworkCompression::$LEVEL;

		$this->chunkX = $chunk->getX();
		$this->chunkZ = $chunk->getZ();
		$this->chunkData = MCPEChunkSerializer::serialize($chunk);
	}

	public function onRun(){
		$pk = new FullChunkDataPacket();
		$pk->chunkX = $this->chunkX;
		$pk->chunkZ = $this->chunkZ;
		$pk->data = $this->chunkData;

		$stream = new MCPEPacketBatch();
		$stream->putPacket($pk);

		$this->setResult(ProtocolInfo::MCPE_RAKNET_PACKET_ID . NetworkCompression::compress($stream->buffer, $this->compressionLevel), false);
	}

	public function onCompletion(Server $server){
		$level = $server->getLevel($this->levelId);
		if($level instanceof Level){
			if($this->hasResult()){
				MCPEChunkCache::getInstance($level)->requestCallback($this->chunkX, $this->chunkZ, $this->getResult());
			}else{
				$server->getLogger()->error("Chunk request for level #" . $this->levelId . ", x=" . $this->chunkX . ", z=" . $this->chunkZ . " doesn't have any result data");
			}
		}else{
			$server->getLogger()->debug("Dropped chunk task due to level not loaded");
		}
	}
}

