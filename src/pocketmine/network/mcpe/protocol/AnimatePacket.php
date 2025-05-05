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

class AnimatePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ANIMATE_PACKET;

	public const ACTION_SWING_ARM = 1;

	public const ACTION_STOP_SLEEP = 3;
	public const ACTION_CRITICAL_HIT = 4;

	public $action;
	public $entityRuntimeId;
	public $rowingTime = 0.0; //TODO (Boat rowing time?)

	public function decodePayload(){
		$this->action = $this->getVarInt();
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		if($this->action & 0x80){
			$this->rowingTime = $this->getLFloat();
		}
	}

	public function encodePayload(){
		$this->putVarInt($this->action);
		$this->putEntityRuntimeId($this->entityRuntimeId);
		if($this->action & 0x80){
			$this->putLFloat($this->rowingTime);
		}
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAnimate($this);
	}

}


