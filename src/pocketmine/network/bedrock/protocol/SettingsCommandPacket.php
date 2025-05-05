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

use pocketmine\network\NetworkSession;

class SettingsCommandPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SETTINGS_COMMAND_PACKET;

	/** @var string */
	public $commandString;
	/** @var bool */
	public $supressOutput;

	public function decodePayload(){
		$this->commandString = $this->getString();
		$this->supressOutput = $this->getBool();
	}

	public function encodePayload(){
		$this->putString($this->commandString);
		$this->putBool($this->supressOutput);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleSettingsCommand($this);
	}
}

