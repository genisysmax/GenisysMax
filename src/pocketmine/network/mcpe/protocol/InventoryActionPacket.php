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

class InventoryActionPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::INVENTORY_ACTION_PACKET;

	public const ACTION_GIVE_ITEM = 0;
	public const ACTION_ENCHANT_ITEM = 2;

	public $actionId;
	public $item;
	public $enchantmentId = 0;
	public $enchantmentLevel = 0;

	public function decodePayload(){
		$this->actionId = $this->getUnsignedVarInt();
		$this->item = $this->getSlot();
		$this->enchantmentId = $this->getVarInt();
		$this->enchantmentLevel = $this->getVarInt();
	}

	public function encodePayload(){
		$this->putUnsignedVarInt($this->actionId);
		$this->putSlot($this->item);
		$this->putVarInt($this->enchantmentId);
		$this->putVarInt($this->enchantmentLevel);
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleInventoryAction($this);
	}
}

