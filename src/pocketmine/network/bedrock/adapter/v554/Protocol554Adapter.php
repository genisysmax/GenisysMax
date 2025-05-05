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

namespace pocketmine\network\bedrock\adapter\v554;

use pocketmine\network\bedrock\adapter\v554\protocol\AddActorPacket as AddActorPacket554;
use pocketmine\network\bedrock\adapter\v554\protocol\AddPlayerPacket as AddPlayerPacket554;
use pocketmine\network\bedrock\adapter\v554\protocol\LoginPacket as LoginPacket554;
use pocketmine\network\bedrock\adapter\v554\protocol\ProtocolInfo as ProtocolInfo554;
use pocketmine\network\bedrock\adapter\v554\protocol\SetActorDataPacket as SetActorDataPacket554;
use pocketmine\network\bedrock\adapter\v557\Protocol557Adapter;
use pocketmine\network\bedrock\protocol\BiomeDefinitionListPacket;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\utils\Binary;
use function file_get_contents;
use function property_exists;

class Protocol554Adapter extends Protocol557Adapter{
	public const PROTOCOL_VERSION = 554;

	protected const PACKET_MAP = [
		ProtocolInfo554::ADD_ACTOR_PACKET => AddActorPacket554::class,
		ProtocolInfo554::ADD_PLAYER_PACKET => AddPlayerPacket554::class,
		ProtocolInfo554::SET_ACTOR_DATA_PACKET => SetActorDataPacket554::class,
		ProtocolInfo554::LOGIN_PACKET => LoginPacket554::class,
	];

    public function __construct(){
        $this->biomeDefinitions = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v554/biome_definitions.nbt");
	}

	public function processClientToServer(string $buf) : ?DataPacket{
		$offset = 0;
		$pid = Binary::readUnsignedVarInt($buf, $offset);

		if(isset(self::PACKET_MAP[$pid])){
			$class = self::PACKET_MAP[$pid];

			return new $class($buf);
		}

		return parent::processClientToServer($buf);
	}

	public function processServerToClient(DataPacket $packet) : ?DataPacket{
		if($packet instanceof BiomeDefinitionListPacket){
			$pk = clone $packet;
			$pk->namedtag = $this->biomeDefinitions;
			return $pk;
		}

		$pid = $packet->pid();
		if(isset(self::PACKET_MAP[$pid])){
			$class = self::PACKET_MAP[$pid];

			$pk = new $class();
			foreach($packet as $k => $v){
				if($k === "isEncoded" or $k === "wasDecoded" or $k === "buffer" or $k === "offset"){
					continue;
				}

				if(property_exists($pk, $k)){
					$pk->{$k} = $v;
				}
			}

			return $pk;
		}

		return parent::processServerToClient($packet);
	}

	public function getProtocolVersion() : int{
		return self::PROTOCOL_VERSION;
	}
}

