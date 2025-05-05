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

class PlayerInputPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_INPUT_PACKET;

	public $motionX;
	public $motionY;
	public $unknownBool1;
	public $unknownBool2;

	public function decodePayload(){
		$this->motionX = $this->getLFloat();
		$this->motionY = $this->getLFloat();
		$this->unknownBool1 = $this->getBool();
		$this->unknownBool2 = $this->getBool();
	}

	public function encodePayload(){
		$this->putLFloat($this->motionX);
		$this->putLFloat($this->motionY);
		$this->putBool($this->unknownBool1);
		$this->putBool($this->unknownBool2);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayerInput($this);
	}

}


