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

namespace pocketmine\network\bedrock\adapter\v567;

use pocketmine\network\bedrock\adapter\v567\protocol as v567;
use pocketmine\network\bedrock\adapter\v568\Protocol568Adapter;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\utils\Binary;
use function property_exists;

class Protocol567Adapter extends Protocol568Adapter{
	public const PROTOCOL_VERSION = 567;

	protected const PACKET_MAP = [
		v567\ProtocolInfo::LOGIN_PACKET => v567\LoginPacket::class,
		v567\ProtocolInfo::PLAYER_LIST_PACKET => v567\PlayerListPacket::class,
		v567\ProtocolInfo::PLAYER_SKIN_PACKET => v567\PlayerSkinPacket::class,
	];

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

