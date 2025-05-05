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


use pocketmine\network\NetworkSession;

class InteractPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::INTERACT_PACKET;

	public const ACTION_RIGHT_CLICK = 1;
	public const ACTION_LEFT_CLICK = 2;
	public const ACTION_LEAVE_VEHICLE = 3;
	public const ACTION_MOUSEOVER = 4;

	public const ACTION_OPEN_INVENTORY = 6;

	public $action;
	public $target;

	public function decodePayload(){
		$this->action = $this->getByte();
		$this->target = $this->getEntityRuntimeId();
	}

	public function encodePayload(){
		$this->putByte($this->action);
		$this->putEntityRuntimeId($this->target);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleInteract($this);
	}

}


