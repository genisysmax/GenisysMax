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


use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\NetworkSession;
use function count;

class ContainerSetContentPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CONTAINER_SET_CONTENT_PACKET;

	public $windowId;
	public $targetEid;
	public $slots = [];
	public $hotbar = [];

	public function clean(){
		$this->slots = [];
		$this->hotbar = [];
		return parent::clean();
	}

	public function decodePayload(){
		$this->windowId = $this->getUnsignedVarInt();
		$this->targetEid = $this->getEntityUniqueId();
		$count = $this->getUnsignedVarInt();
		for($s = 0; $s < $count and !$this->feof(); ++$s){
			$this->slots[$s] = $this->getSlot();
		}

		$hotbarCount = $this->getUnsignedVarInt(); //MCPE always sends this, even when it's not a player inventory
		for($s = 0; $s < $hotbarCount and !$this->feof(); ++$s){
			$this->hotbar[$s] = $this->getVarInt();
		}
	}

	public function encodePayload(){
		$this->putUnsignedVarInt($this->windowId);
		$this->putEntityUniqueId($this->targetEid);
		$this->putUnsignedVarInt(count($this->slots));
		foreach($this->slots as $slot){
			$this->putSlot($slot);
		}
		if($this->windowId === ContainerIds::INVENTORY and count($this->hotbar) > 0){
			$this->putUnsignedVarInt(count($this->hotbar));
			foreach($this->hotbar as $slot){
				$this->putVarInt($slot);
			}
		}else{
			$this->putUnsignedVarInt(0);
		}
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleContainerSetContent($this);
	}

}

