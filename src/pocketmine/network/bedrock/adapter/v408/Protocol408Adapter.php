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

namespace pocketmine\network\bedrock\adapter\v408;

use pocketmine\network\bedrock\adapter\v407\protocol\ProtocolInfo;
use pocketmine\network\bedrock\adapter\v407\Protocol407Adapter;
use pocketmine\network\bedrock\adapter\v408\protocol\LoginPacket as LoginPacket408;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\utils\Binary;

class Protocol408Adapter extends Protocol407Adapter{

	public const PROTOCOL_VERSION = 408;

	public function processClientToServer(string $buf) : ?DataPacket{
		$offset = 0;
		$pid = Binary::readUnsignedVarInt($buf, $offset);
		if($pid === ProtocolInfo::LOGIN_PACKET){
			return new LoginPacket408($buf);
		}

		return parent::processClientToServer($buf);
	}

	public function getProtocolVersion() : int{
		return self::PROTOCOL_VERSION;
	}
}

