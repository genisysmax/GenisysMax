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

use pocketmine\network\bedrock\protocol\types\inventory\ItemInstance;
use pocketmine\network\NetworkSession;
use pocketmine\utils\UUID;
use function count;

class CraftingEventPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CRAFTING_EVENT_PACKET;

	/** @var int */
	public $windowId;
	/** @var int */
	public $type;
	/** @var UUID */
	public $id;
	/** @var ItemInstance[] */
	public $input = [];
	/** @var ItemInstance[] */
	public $output = [];

	public function decodePayload(){
		$this->windowId = $this->getByte();
		$this->type = $this->getVarInt();
		$this->id = $this->getUUID();

		$size = $this->getUnsignedVarInt();
		for($i = 0; $i < $size and $i < 128; ++$i){
			$this->input[] = $this->getItemInstance();
		}

		$size = $this->getUnsignedVarInt();
		for($i = 0; $i < $size and $i < 128; ++$i){
			$this->output[] = $this->getItemInstance();
		}
	}

	public function encodePayload(){
		$this->putByte($this->windowId);
		$this->putVarInt($this->type);
		$this->putUUID($this->id);

		$this->putUnsignedVarInt(count($this->input));
		foreach($this->input as $item){
			$this->putItemInstance($item);
		}

		$this->putUnsignedVarInt(count($this->output));
		foreach($this->output as $item){
			$this->putItemInstance($item);
		}
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCraftingEvent($this);
	}
}


