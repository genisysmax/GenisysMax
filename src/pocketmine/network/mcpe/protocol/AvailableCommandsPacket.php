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

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use InvalidArgumentException;
use pocketmine\network\NetworkSession;
use function gettype;
use function is_array;

class AvailableCommandsPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::AVAILABLE_COMMANDS_PACKET;

	/** @var array */
	public $commandData;
	/** @var string */
	public $unknown = "";

	public function decodePayload(){
		$this->commandData = $this->getJson();
		if(!is_array($this->commandData)){
			throw new InvalidArgumentException("Commands expected to be array, got " . gettype($this->commands));
		}
		$this->unknown = $this->getString();
	}

	public function encodePayload(){
		$this->putJson($this->commandData);
		$this->putString($this->unknown);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAvailableCommands($this);
	}

}

