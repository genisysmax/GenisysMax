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

use pocketmine\network\bedrock\protocol\types\inventory\stackresponse\ItemStackResponse;
use pocketmine\network\bedrock\protocol\types\itemStack\StackResponseContainerInfo;
use pocketmine\network\bedrock\protocol\types\itemStack\StackResponseSlotInfo;
use pocketmine\network\NetworkSession;
use function count;

class ItemStackResponsePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ITEM_STACK_RESPONSE_PACKET;

	public const RESULT_OK = 0;
	public const RESULT_ERROR = 1;
	//TODO: there are a ton more possible result types but we don't need them yet and they are wayyyyyy too many for me
	//to waste my time on right now...

	/** @var int */
	public $result;
	/** @var int */
	public $requestId;
	/** @var StackResponseContainerInfo[] */
	public $containerInfo = [];

    /** @var ItemStackResponse[] */
    public array $responses;

	public function decodePayload(){
        $this->responses = [];
        for($i = 0, $len = $this->getUnsignedVarInt(); $i < $len; ++$i){
            $this->responses[] = ItemStackResponse::read($this);
        }
	}

	public function encodePayload(){
        $this->putUnsignedVarInt(count($this->responses));
        foreach($this->responses as $response){
            $response->write($this);
        }
	}

	protected function getStackResponseContainerInfo() : StackResponseContainerInfo{
		$info = new StackResponseContainerInfo();
		$info->containerId = $this->getByte();
		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$info->slotInfo[] = $this->getStackResponseSlotInfo();
		}
		return $info;
	}

	protected function putStackResponseContainerInfo(StackResponseContainerInfo $info) : void{
		$this->putByte($info->containerId);

		$this->putUnsignedVarInt(count($info->slotInfo));
		foreach($info->slotInfo as $info){
			$this->putStackResponseSlotInfo($info);
		}
	}

	protected function getStackResponseSlotInfo() : StackResponseSlotInfo{
		$info = new StackResponseSlotInfo();
		$info->slot = $this->getByte();
		$info->hotbarSlot = $this->getByte();
		$info->count = $this->getByte();
		$info->stackNetworkId = $this->getVarInt();
		$info->customName = $this->getString();
		$info->durabilityCorrection = $this->getVarInt();
		return $info;
	}

	protected function putStackResponseSlotInfo(StackResponseSlotInfo $info) : void{
		$this->putByte($info->slot);
		$this->putByte($info->hotbarSlot);
		$this->putByte($info->count);
		$this->putVarInt($info->stackNetworkId);
		$this->putString($info->customName);
		$this->putVarInt($info->durabilityCorrection);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleItemStackResponse($this);
	}
}

