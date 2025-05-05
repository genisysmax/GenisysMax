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


use InvalidArgumentException;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\bedrock\protocol\types\ItemComponentEntry;
use pocketmine\network\mcpe\NetworkNbtSerializer;
use pocketmine\network\NetworkSession;
use function count;

class ItemComponentPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ITEM_COMPONENT_PACKET;

	/** @var ItemComponentEntry[] */
	public $entries = [];

	public function decodePayload(){
		$count = $this->getUnsignedVarInt();
		if($count > 8192){
			throw new InvalidArgumentException("Too many item components: $count");
		}
		for($i = 0; $i < $count; ++$i){
			$name = $this->getString();
			$tag = $this->getNbtCompoundRoot();

			$this->entries[] = new ItemComponentEntry($name, $tag);
		}
	}

	public function encodePayload(){
		$this->putUnsignedVarInt(count($this->entries));

		$nbt = new NetworkNbtSerializer();
		foreach($this->entries as $entry){
			$this->putString($entry->name);
			$this->put($nbt->write(new TreeRoot($entry->tag)));
		}
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleItemComponent($this);
	}
}


