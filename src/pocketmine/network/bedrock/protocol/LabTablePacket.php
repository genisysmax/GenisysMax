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

class LabTablePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::LAB_TABLE_PACKET;

	/** @var int */
	public $uselessByte; //0 for client -> server, 1 for server -> client. Seems useless.

	/** @var int */
	public $x;
	/** @var int */
	public $y;
	/** @var int */
	public $z;

	/** @var int */
	public $reactionType;

	public function decodePayload(){
		$this->uselessByte = $this->getByte();
		$this->getSignedBlockPosition($this->x, $this->y, $this->z);
		$this->reactionType = $this->getByte();
	}

	public function encodePayload(){
		$this->putByte($this->uselessByte);
		$this->putSignedBlockPosition($this->x, $this->y, $this->z);
		$this->putByte($this->reactionType);
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleLabTable($this);
	}
}


