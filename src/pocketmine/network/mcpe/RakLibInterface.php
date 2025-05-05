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

namespace pocketmine\network\mcpe;

use pmmp\thread\Thread as NativeThread;
use pocketmine\BedrockPlayer;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\network\AdvancedNetworkInterface;
use pocketmine\network\bedrock\adapter\ProtocolAdapterFactory;
use pocketmine\network\bedrock\adapter\v545\Protocol545Adapter;
use pocketmine\network\bedrock\BedrockPacketBatch;
use pocketmine\network\bedrock\NetworkCompression as BedrockNetworkCompression;
use pocketmine\network\bedrock\protocol\PacketPool as BedrockPacketPool;
use pocketmine\network\bedrock\protocol\ProtocolInfo as BedrockProtocolInfo;
use pocketmine\network\bedrock\protocol\types\CompressionAlgorithm;
use pocketmine\network\mcpe\encryption\DecryptionException;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\Network;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\thread\ThreadCrashException;
use raklib\protocol\EncapsulatedPacket;
use raklib\protocol\PacketReliability;
use raklib\RakLib;
use raklib\server\RakLibServer;
use raklib\server\ServerHandler;
use raklib\server\ServerInstance;
use raklib\utils\InternetAddress;
use function bin2hex;
use function get_class;
use function igbinary_unserialize;
use function ord;
use function spl_object_id;
use function substr;

class RakLibInterface implements ServerInstance, AdvancedNetworkInterface{

    /**
     * Sometimes this gets changed when the MCPE-layer protocol gets broken to the point where old and new can't
     * communicate. It's important that we check this to avoid catastrophes.
     */
    private const int MCBE_RAKNET_PROTOCOL_VERSION_WITHOUT_ZLIB_COMPRESSION = 11;
    private const int MCBE_RAKNET_PROTOCOL_VERSION_WITH_RAW_ZLIB_ENCODING = 10;
    private const int MCBE_RAKNET_PROTOCOL_VERSION = 9;
    private const int MCPE_RAKNET_PROTOCOL_VERSION = 8;

	private const MCPE_RAKNET_PACKET_ID = "\xfe";

	/** @var Server */
	private $server;

	/** @var Network */
	private $network;

	/** @var RakLibServer */
	private $rakLib;

	/** @var int */
	private $port;

	/** @var Player[] */
	private $players = [];

	/** @var int[] */
	private $identifiers = [];

	/** @var int[] */
	private $identifiersACK = [];

	/** @var bool[] */
	private $ignorePing = [];

	/** @var ServerHandler */
	private $interface;

	/** @var SleeperNotifier */
	private $sleeper;

	public function __construct(Server $server, ?int $port = null){
		$this->server = $server;
		$this->port = $port ?? $server->getPort();

		$this->sleeper = new SleeperNotifier();

		$rakNetProtocols = [
            self::MCBE_RAKNET_PROTOCOL_VERSION_WITHOUT_ZLIB_COMPRESSION => true,
            self::MCBE_RAKNET_PROTOCOL_VERSION_WITH_RAW_ZLIB_ENCODING => true,
            self::MCBE_RAKNET_PROTOCOL_VERSION => true,
            self::MCPE_RAKNET_PROTOCOL_VERSION => true,
		];
		$this->rakLib = new RakLibServer($server->getLogger(), $server->getLoader(), new InternetAddress($server->getIp() === "" ? "0.0.0.0" : $server->getIp(), $this->port, 4), (int) $server->getProperty("network.max-mtu-size", 1492), array_keys($rakNetProtocols), $this->sleeper);
		$this->interface = new ServerHandler($this->rakLib, $this);
	}

	public function start() : void{
		$this->server->getTickSleeper()->addNotifier($this->sleeper, function() : void{
			$this->process();
		});
		$this->server->getLogger()->debug("Waiting for RakLib to start...");
		$this->rakLib->startAndWait(NativeThread::INHERIT_CONSTANTS); //HACK: MainLogger needs constants for exception logging
		$this->server->getLogger()->debug("RakLib booted successfully");

		$this->setPortChecking($this->server->getAdvancedProperty("network.port-checking", true));
		$this->setPacketLimit($this->server->getAdvancedProperty("network.packet-limit", 300));
	}

	public function setNetwork(Network $network) : void{
		$this->network = $network;
	}

	public function process() : bool{
		$work = false;
		if($this->interface->handlePacket()){
			$work = true;
			while($this->interface->handlePacket()){}
		}

		if($this->rakLib->isTerminated()){
			$this->network->unregisterInterface($this);

			$e = $this->rakLib->getCrashInfo();
			if($e !== null){
				throw new ThreadCrashException("RakLib crashed", $e);
			}
			throw new \Exception("RakLib Thread crashed without crash information");
		}

		return $work;
	}

	public function closeSession(int $sessionId, string $reason) : void{
		if(isset($this->players[$sessionId])){
			$player = $this->players[$sessionId];
			unset($this->identifiers[spl_object_id($player)]);
			unset($this->players[$sessionId]);
			unset($this->identifiersACK[$sessionId]);
			unset($this->ignorePing[$sessionId]);
			$player->close($player->getLeaveMessage(), $reason);
		}
	}

	public function close(Player $player, string $reason = "unknown reason") : void{
		if(isset($this->identifiers[$h = spl_object_id($player)])){
			unset($this->players[$this->identifiers[$h]]);
			unset($this->identifiersACK[$this->identifiers[$h]]);
			unset($this->ignorePing[$this->identifiers[$h]]);
			$this->interface->closeSession($this->identifiers[$h], $reason);
			unset($this->identifiers[$h]);
		}
	}

	public function shutdown() : void{
		$this->server->getTickSleeper()->removeNotifier($this->sleeper);
		$this->interface->shutdown();
	}

	public function emergencyShutdown() : void{
		$this->server->getTickSleeper()->removeNotifier($this->sleeper);
		$this->interface->emergencyShutdown();
	}

	public function openSession(int $sessionId, string $address, int $port, int $clientID, int $protocolVersion, bool $isValid) : void{
		if($protocolVersion === self::MCPE_RAKNET_PROTOCOL_VERSION){
			$class = Player::class;
		}elsE{
			$class = BedrockPlayer::class;
		}
		$ev = new PlayerCreationEvent($this, $class, $class, null, $address, $port);
		$ev->call();
		$class = $ev->getPlayerClass();

		$player = new $class($this, $ev->getClientId(), $ev->getAddress(), $ev->getPort(), $isValid);
		if($player instanceof BedrockPlayer){
			$player->setEnableCompression($protocolVersion <= Protocol545Adapter::RAKNET_PROTOCOL_VERSION); // TODO
		}
		$this->players[$sessionId] = $player;
		$this->identifiersACK[$sessionId] = 0;
		$this->identifiers[spl_object_id($player)] = $sessionId;
		$this->server->addPlayer($sessionId, $player);
	}

	public function handleEncapsulated(int $sessionId, EncapsulatedPacket $packet, int $flags) : void{
		if(isset($this->players[$sessionId])){
			$player = $this->players[$sessionId];

			try{
				if($packet->buffer !== ""){
					if($packet->buffer[0] !== self::MCPE_RAKNET_PACKET_ID){
						throw new \UnexpectedValueException("Unexpected non-FE packet");
					}
					$cipher = $player->getCipher();
					$buffer = substr($packet->buffer, 1);

                    if($cipher != null) {
                        try {
                            if($player instanceof BedrockPlayer) {
                                $buffer = $cipher->decrypt($buffer, true);
                            } else {
                                $buffer = $cipher->decrypt($buffer);
                            }
                        } catch (DecryptionException $e) {
                            logger()->debug("Encrypted packet: " . base64_encode($buffer));
                            logger()->logException($e);
                        }
                    }

					if($player instanceof BedrockPlayer){
						if($packet->buffer[0] === BedrockProtocolInfo::MCPE_RAKNET_PACKET_ID){
							$protocolAdapter = $player->getProtocolAdapter();

                            if ($player->hasNetworkCompression()) {
                                $compressionType = ord($buffer[0]);
                                if ($compressionType == CompressionAlgorithm::NONE) {
                                    $compressed = substr($buffer, 1);
                                    $decompressed = $compressed;
                                } elseif($compressionType == CompressionAlgorithm::ZLIB) {
                                    $compressed = substr($buffer, 1);
                                    $decompressed = BedrockNetworkCompression::decompress($compressed);
                                } else {
                                    $decompressed = BedrockNetworkCompression::decompress($buffer);
                                }
                            }else{
                                $decompressed = $buffer;
                            }

							$stream = new BedrockPacketBatch($decompressed);
							$count = 0;
							while(!$stream->feof()){
								if(++$count > 1024){
									throw new \UnexpectedValueException("Too many batched packets!");
								}

								$buf = $stream->getString();
								if($protocolAdapter !== null){
									$pk = $protocolAdapter->processClientToServer($buf);
								}else{
									$pk = BedrockPacketPool::getPacket($buf);
								}

								if($pk !== null){
									$player->handleDataPacket($pk);
								}
							}
						}
					} else {
						$pk = $this->getPacket(self::MCPE_RAKNET_PACKET_ID.$buffer);
						$player->handleDataPacket($pk);
					}
				}
			}catch(\Throwable $e){
				$logger = $this->server->getLogger();
				$logger->debug("Packet " . (isset($pk) ? get_class($pk) : "unknown") . " 0x" . bin2hex($packet->buffer));
				$logger->logException($e);

				$player->close($player->getLeaveMessage(), "Internal server error");
				$this->interface->blockAddress($player->getAddress(), 5);
			}
		}
	}

	public function blockAddress(string $address, int $timeout = 300) : void{
		$this->interface->blockAddress($address, $timeout);
	}

	public function handleRaw(string $address, int $port, string $payload) : void{
		$this->server->handlePacket($this, $address, $port, $payload);
	}

	public function sendRawPacket(string $address, int $port, string $payload) : void{
		$this->interface->sendRaw($address, $port, $payload);
	}

	public function unlimitAddress(string $address) : void{
		$this->interface->unlimitAddress($address);
	}

	public function notifyACK(int $sessionId, int $identifierACK) : void{

	}

    public function setName(string $name): void{
        $info = $this->server->getQueryInformation();

        $this->interface->sendOption("name", implode(";",
                [
                    "MCPE",
                    rtrim(addcslashes($name, ";"), '\\'),
                    BedrockProtocolInfo::CURRENT_PROTOCOL,
                    BedrockProtocolInfo::MINECRAFT_VERSION_NETWORK,
                    $info->getPlayerCount(),
                    $info->getMaxPlayerCount(),
                    $this->rakLib->getServerId(),
                    $this->server->getName(),
                    Server::getGamemodeName($this->server->getGamemode())
                ]) . ";"
        );
    }

	public function setPortChecking(bool $value) : void{
		$this->interface->sendOption("portChecking", $value);
	}

	public function setPacketLimit(int $packetLimit) : void{
		$this->interface->sendOption("packetLimit", $packetLimit);
	}

	public function handleOption(string $option, string $value) : void{
		if($option === "bandwidth"){
			$v = igbinary_unserialize($value);
			$this->network->addStatistics($v["up"], $v["down"]);
		}
	}

	public function ignorePing(Player $player, bool $value = true) : void{
		if(isset($this->identifiers[$h = spl_object_id($player)])){
			$sessionId = $this->identifiers[$h];

			if($value){
				$this->ignorePing[$sessionId] = true;
			}else{
				unset($this->ignorePing[$sessionId]);
			}
		}
	}

	public function updatePing(int $sessionId, int $pingMS) : void{
		if(isset($this->players[$sessionId]) and !isset($this->ignorePing[$sessionId])){
			$this->players[$sessionId]->setPing($pingMS);
		}
	}

	public function putPacket(Player $player, DataPacket $packet, bool $needACK = false, bool $immediate = true) : ?int{
		if(isset($this->identifiers[$h = spl_object_id($player)])){
			if(!$packet->isEncoded){
				$packet->encode();
			}

			if($packet instanceof BatchPacket){
				return $this->putBuffer($player, $packet->buffer, $needACK, $immediate);
			}else{
				$this->server->batchPackets([$player], [$packet], true, $immediate);
				return null;
			}
		}

		return null;
	}

	public function putBuffer(Player $player, string $buffer, bool $needACK = false, bool $immediate = true) : ?int{
		if(isset($this->identifiers[$h = spl_object_id($player)])){
			$sessionId = $this->identifiers[$h];

			$cipher = $player->getCipher();
			$rawBuffer = substr($buffer, 1);
			$buffer = self::MCPE_RAKNET_PACKET_ID . ($cipher !== null ? $cipher->encrypt($rawBuffer) : $rawBuffer);

			$pk = new EncapsulatedPacket();
			$pk->buffer = $buffer;
			$pk->reliability = $immediate ? PacketReliability::RELIABLE : PacketReliability::RELIABLE_ORDERED;
			$pk->orderChannel = 0;

			if($needACK === true){
				$pk->identifierACK = $this->identifiersACK[$sessionId]++;
			}

			$this->interface->sendEncapsulated($sessionId, $pk, ($needACK === true ? RakLib::FLAG_NEED_ACK : 0) | ($immediate === true ? RakLib::PRIORITY_IMMEDIATE : RakLib::PRIORITY_NORMAL));
			return $pk->identifierACK;
		}

		return null;
	}

	private function getPacket(string $buffer) : ?DataPacket{
		$pid = ord($buffer[0]);
		if(($data = PacketPool::getPacketById($pid)) === null){
			return null;
		}
		$data->setBuffer($buffer, 1);
		return $data;
	}
}


