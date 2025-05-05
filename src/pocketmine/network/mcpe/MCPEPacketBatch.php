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

use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\utils\BinaryDataException;

class MCPEPacketBatch extends NetworkBinaryStream{

	public function putPacket(DataPacket $packet) : void{
		if(!$packet->isEncoded){
			$packet->encode();
		}
		$this->putString($packet->getBuffer());
	}

	/**
	 * @return DataPacket
	 * @throws BinaryDataException
	 */
	public function getPacket() : DataPacket{
		return PacketPool::getPacket($this->getString());
	}

	/**
	 * Constructs a packet batch from the given list of packets.
	 *
	 * @param DataPacket ...$packets
	 *
	 * @return MCPEPacketBatch
	 */
	public static function fromPackets(DataPacket ...$packets) : MCPEPacketBatch{
		$result = new MCPEPacketBatch();
		foreach($packets as $packet){
			$result->putPacket($packet);
		}
		return $result;
	}
}


