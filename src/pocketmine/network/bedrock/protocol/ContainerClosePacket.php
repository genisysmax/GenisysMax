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


use pocketmine\network\bedrock\protocol\types\inventory\WindowTypes;
use pocketmine\network\NetworkSession;

class ContainerClosePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CONTAINER_CLOSE_PACKET;

	/** @var int */
	public $windowId;
    /** @var int */
    public $windowType = WindowTypes::CONTAINER;
	/** @var bool */
	public $server = false;

	public function decodePayload(){
        $this->windowId = $this->getByte();
        $this->windowType = $this->getByte();
		$this->server = $this->getBool();
	}

	public function encodePayload(){
		$this->putByte($this->windowId);
        $this->putByte($this->windowType);
		$this->putBool($this->server);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleContainerClose($this);
	}
}


