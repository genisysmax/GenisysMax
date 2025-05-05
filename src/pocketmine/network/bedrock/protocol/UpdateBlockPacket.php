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

class UpdateBlockPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::UPDATE_BLOCK_PACKET;

	public const DATA_LAYER_NORMAL = 0;
	public const DATA_LAYER_LIQUID = 1;

	/** @var int */
	public $x;
	/** @var int */
	public $z;
	/** @var int */
	public $y;
	/** @var int */
	public $blockRuntimeId;
	/**
	 * @var int
	 * Flags are used by MCPE internally for block setting, but only flag 2 (network flag) is relevant for network.
	 * This field is pointless really.
	 */
	public $flags = 0x02;
	/** @var int */
	public $dataLayerId = self::DATA_LAYER_NORMAL;

	public function decodePayload(){
		$this->getBlockPosition($this->x, $this->y, $this->z);
		$this->blockRuntimeId = $this->getUnsignedVarInt();
		$this->flags = $this->getUnsignedVarInt();
		$this->dataLayerId = $this->getUnsignedVarInt();
	}

	public function encodePayload(){
		$this->putBlockPosition($this->x, $this->y, $this->z);
		$this->putUnsignedVarInt($this->blockRuntimeId);
		$this->putUnsignedVarInt($this->flags);
		$this->putUnsignedVarInt($this->dataLayerId);
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleUpdateBlock($this);
	}
}


