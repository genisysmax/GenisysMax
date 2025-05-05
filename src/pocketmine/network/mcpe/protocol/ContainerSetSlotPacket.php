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

use pocketmine\item\Item;
use pocketmine\network\NetworkSession;

class ContainerSetSlotPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CONTAINER_SET_SLOT_PACKET;

	public $windowId;
	public $slot;
	public $hotbarSlot = 0;
	/** @var Item */
	public $item;
	public $selectSlot = 0;

	public function decodePayload(){
		$this->windowId = $this->getByte();
		$this->slot = $this->getVarInt();
		$this->hotbarSlot = $this->getVarInt();
		$this->item = $this->getSlot();
		$this->selectSlot = $this->getByte();
	}

	public function encodePayload(){
		$this->putByte($this->windowId);
		$this->putVarInt($this->slot);
		$this->putVarInt($this->hotbarSlot);
		$this->putSlot($this->item);
		$this->putByte($this->selectSlot);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleContainerSetSlot($this);
	}

}


