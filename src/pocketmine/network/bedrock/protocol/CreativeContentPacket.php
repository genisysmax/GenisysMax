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

use pocketmine\network\bedrock\protocol\types\inventory\CreativeItem;
use pocketmine\network\NetworkSession;
use function count;

class CreativeContentPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CREATIVE_CONTENT_PACKET;

	/** @var CreativeItem[] */
	public $items = [];

	public function decodePayload(){
		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$this->items[] = $this->getCreativeItem();
		}
	}

	public function encodePayload(){
		$this->putUnsignedVarInt(count($this->items));
		foreach($this->items as $item){
			$this->putCreativeItem($item);
		}
	}

	protected function getCreativeItem() : CreativeItem{
		$creativeItem = new CreativeItem();
		$creativeItem->creativeItemNetworkId = $this->getUnsignedVarInt();
		$creativeItem->item = $this->getItemStackWithoutStackId();
		return $creativeItem;
	}

	protected function putCreativeItem(CreativeItem $creativeItem) : void{
		$this->putUnsignedVarInt($creativeItem->creativeItemNetworkId);
		$this->putItemStackWithoutStackId($creativeItem->item);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCreativeContent($this);
	}
}

