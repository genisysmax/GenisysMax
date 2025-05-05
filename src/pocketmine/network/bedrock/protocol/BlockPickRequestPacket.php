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

class BlockPickRequestPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::BLOCK_PICK_REQUEST_PACKET;

	/** @var int */
	public $blockX;
	/** @var int */
	public $blockY;
	/** @var int */
	public $blockZ;
	/** @var bool */
	public $addUserData = false;
	/** @var int */
	public $hotbarSlot;

	public function decodePayload(){
		$this->getSignedBlockPosition($this->blockX, $this->blockY, $this->blockZ);
		$this->addUserData = $this->getBool();
		$this->hotbarSlot = $this->getByte();
	}

	public function encodePayload(){
		$this->putSignedBlockPosition($this->blockX, $this->blockY, $this->blockZ);
		$this->putBool($this->addUserData);
		$this->putByte($this->hotbarSlot);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleBlockPickRequest($this);
	}
}


