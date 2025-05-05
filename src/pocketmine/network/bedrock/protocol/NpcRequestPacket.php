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

class NpcRequestPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::NPC_REQUEST_PACKET;

    public const REQUEST_SET_ACTIONS = 0;
    public const REQUEST_EXECUTE_ACTION = 1;
    public const REQUEST_EXECUTE_CLOSING_COMMANDS = 2;
    public const REQUEST_SET_NAME = 3;
    public const REQUEST_SET_SKIN = 4;
    public const REQUEST_SET_INTERACTION_TEXT = 5;
    public const REQUEST_EXECUTE_OPENING_COMMANDS = 6; // v448+

	/** @var int */
	public $actorRuntimeId;
	/** @var int */
	public $requestType;
	/** @var string */
	public $commandString;
	/** @var int */
	public $actionType;
    /** @var string */
    public $sceneName;

	public function decodePayload(){
		$this->actorRuntimeId = $this->getActorRuntimeId();
		$this->requestType = $this->getByte();
		$this->commandString = $this->getString();
		$this->actionType = $this->getByte();
        $this->sceneName = $this->getString();
    }

	public function encodePayload(){
		$this->putActorRuntimeId($this->actorRuntimeId);
		$this->putByte($this->requestType);
		$this->putString($this->commandString);
		$this->putByte($this->actionType);
        $this->putString($this->sceneName);
    }

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleNpcRequest($this);
	}
}


