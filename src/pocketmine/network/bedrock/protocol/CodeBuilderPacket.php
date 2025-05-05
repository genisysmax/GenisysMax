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

class CodeBuilderPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CODE_BUILDER_PACKET;

	/** @var string */
	public $url;
	/** @var bool */
	public $shouldOpenCodeBuilder;

	public function decodePayload(){
		$this->url = $this->getString();
		$this->shouldOpenCodeBuilder = $this->getBool();
	}

	public function encodePayload(){
		$this->putString($this->url);
		$this->putBool($this->shouldOpenCodeBuilder);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCodeBuilder($this);
	}
}

