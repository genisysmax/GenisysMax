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

namespace pocketmine\network\bedrock\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\bedrock\protocol\types\command\CommandOriginData;
use pocketmine\network\NetworkSession;

class CommandRequestPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::COMMAND_REQUEST_PACKET;

	/** @var string */
	public $command;
	/** @var CommandOriginData */
	public $originData;
	/** @var bool */
	public $isInternal;
	/** @var int */
	public $version;

	public function decodePayload(){
		$this->command = $this->getString();
		$this->originData = $this->getCommandOriginData();
		$this->isInternal = $this->getBool();
		$this->version = $this->getVarInt();
	}

	public function encodePayload(){
		$this->putString($this->command);
		$this->putCommandOriginData($this->originData);
		$this->putBool($this->isInternal);
		$this->putVarInt($this->version);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCommandRequest($this);
	}
}


