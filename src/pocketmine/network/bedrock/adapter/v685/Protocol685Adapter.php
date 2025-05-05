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

namespace pocketmine\network\bedrock\adapter\v685;

use pocketmine\item\Item;
use pocketmine\network\bedrock\adapter\mapper\CommandArgTypeMapper;
use pocketmine\network\bedrock\adapter\ProtocolAdapter;
use pocketmine\network\bedrock\adapter\v685\protocol as v685;
use pocketmine\network\bedrock\adapter\v685\protocol\ProtocolInfo;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\PacketPool;
use pocketmine\utils\Binary;

class Protocol685Adapter implements ProtocolAdapter{
	public const PROTOCOL_VERSION = 685;

    /** @var string */
    protected $actorIdentifiers;
    /** @var string */
    protected $biomeDefinitions;
    /** @var CommandArgTypeMapper */
    protected $argTypeMapper;

	public function __construct(){}

    public function initArgTypeMapper() : void{}

    public function getCreativeItems(): array
    {
        $creativeItemsJson = json_decode(file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/creativeitems.json"), true);
        $creativeItems = [];
        foreach($creativeItemsJson as $data){
            $item = Item::jsonDeserialize($data);
            if ($item->getName() === "Unknown") {
                continue;
            }
            $creativeItems[] = clone $item;
        }
        return $creativeItems;
    }

	public function processClientToServer(string $buf) : ?DataPacket{
		$offset = 0;
		$pid = Binary::readUnsignedVarInt($buf, $offset);

        if($pid === ProtocolInfo::LOGIN_PACKET){
            return new v685\LoginPacket($buf);
        }

		$packet = PacketPool::getPacket($buf);

		$this->processBlocks($packet, true);
		return $packet;
	}

	public function processServerToClient(DataPacket $packet) : ?DataPacket{
		$packet = clone $packet;
		$this->processBlocks($packet, false);

		$packet->isEncoded = false;
		return $packet;
	}

	public function translateBlockId(int $runtimeId) : int{
		return $runtimeId;
	}

	protected function processBlocks(DataPacket $packet, bool $clientToServer) : void{
        //TODO: изменений в BlockPalette не было!
	}

	public function getChunkProtocol() : int{
		return self::PROTOCOL_VERSION;
	}

	public function getProtocolVersion() : int{
		return self::PROTOCOL_VERSION;
	}
}

