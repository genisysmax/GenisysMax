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
 * Network-related classes
 */
namespace pocketmine\network;

use pocketmine\network\bedrock\adapter\ProtocolAdapterFactory;
use pocketmine\network\bedrock\palette\ActorMapping as BedrockActorMapping;
use pocketmine\network\bedrock\palette\block\BlockPalette as BedrockBlockPalette;
use pocketmine\network\bedrock\palette\entry\BlockPaletteEntry;
use pocketmine\network\bedrock\palette\entry\ItemPaletteEntry;
use pocketmine\network\bedrock\palette\entry\PaletteEntry;
use pocketmine\network\bedrock\palette\item\ItemPalette as BedrockItemPalette;
use pocketmine\network\bedrock\palette\PaletteTask;
use pocketmine\network\bedrock\protocol\PacketPool as BedrockPacketPool;
use pocketmine\network\bedrock\skin\SkinConverter as BedrockSkinConverter;
use pocketmine\network\bedrock\StaticPacketCache;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\Server;
use function get_class;
use function spl_object_id;

class Network{

	/** @var Server */
	private $server;

	/** @var NetworkInterface[] */
	private $interfaces = [];

	/** @var AdvancedNetworkInterface[] */
	private $advancedInterfaces = [];

	/** @var int|float */
	private $upload = 0;
	/** @var int|float */
	private $download = 0;

    private string $name = '';

    public function __construct(Server $server){
        PacketPool::init();
        BedrockPacketPool::init();

        BedrockActorMapping::init();
        self::registerPalette(new BlockPaletteEntry(new BedrockBlockPalette()));
        self::registerPalette(new ItemPaletteEntry(new BedrockItemPalette()));
        BedrockSkinConverter::init();
        ProtocolAdapterFactory::init();
        StaticPacketCache::init();

        $this->server = $server;
    }

    public static function registerPalette(PaletteEntry $entry):void{
         if(($entry->getPalette())::getEncodedPalette() !== null){
            return; //why do it again
        }
        if(Server::getInstance() === null || !Server::getInstance()->getAdvancedProperty("server.fast-boot", false)){
            $entry->process(); //TODO: the result for the thread is done
        }else{
            Server::getInstance()->getScheduler()->scheduleAsyncTask(new PaletteTask($entry));
        }
    }

    /**
     * Sets the server name shown on each interface Query
     *
     * @param string $name
     */
    public function setName(string $name): void{
        $this->name = (string) $name;
        foreach($this->interfaces as $interface){
            $interface->setName($this->name);
        }
    }

    public function getName(): string{
        return $this->name;
    }

    public function updateName(): void{
        foreach($this->interfaces as $interface){
            $interface->setName($this->name);
        }
    }

	public function addStatistics($upload, $download){
		$this->upload += $upload;
		$this->download += $download;
	}

	/**
	 * @return float|int
	 */
	public function getUpload(){
		return $this->upload;
	}

	/**
	 * @return float|int
	 */
	public function getDownload(){
		return $this->download;
	}

	public function resetStatistics(){
		$this->upload = 0;
		$this->download = 0;
	}

	/**
	 * @return NetworkInterface[]
	 */
	public function getInterfaces() : array{
		return $this->interfaces;
	}

	public function processInterfaces(){
		foreach($this->interfaces as $interface){
			$this->processInterface($interface);
		}
	}

	/**
	 * @param NetworkInterface $interface
	 */
	public function processInterface(NetworkInterface $interface) : void{
		try{
			$interface->process();
		}catch(\Throwable $e){
			$logger = $this->server->getLogger();
			if(\pocketmine\DEBUG > 1){
				$logger->logException($e);
			}

			$interface->emergencyShutdown();
			$this->unregisterInterface($interface);
			$logger->critical($this->server->getLanguage()->translateString("pocketmine.server.networkError", [get_class($interface), $e->getMessage()]));
		}
	}

	/**
	 * @param NetworkInterface $interface
	 */
	public function registerInterface(NetworkInterface $interface){
		$interface->start();
		$this->interfaces[$hash = spl_object_id($interface)] = $interface;
		if($interface instanceof AdvancedNetworkInterface){
			$this->advancedInterfaces[$hash] = $interface;
			$interface->setNetwork($this);
		}
	}

	/**
	 * @param NetworkInterface $interface
	 */
	public function unregisterInterface(NetworkInterface $interface){
		unset($this->interfaces[$hash = spl_object_id($interface)],
			$this->advancedInterfaces[$hash]);
	}

	/**
	 * @return Server
	 */
	public function getServer() : Server{
		return $this->server;
	}

	/**
	 * @param string $address
	 * @param int    $port
	 * @param string $payload
	 */
	public function sendPacket(string $address, int $port, string $payload){
		foreach($this->advancedInterfaces as $interface){
			$interface->sendRawPacket($address, $port, $payload);
		}
	}

	/**
	 * Blocks an IP address from the main interface. Setting timeout to -1 will block it forever
	 *
	 * @param string $address
	 * @param int    $timeout
	 */
	public function blockAddress(string $address, int $timeout = 300){
		foreach($this->advancedInterfaces as $interface){
			$interface->blockAddress($address, $timeout);
		}
	}
}


