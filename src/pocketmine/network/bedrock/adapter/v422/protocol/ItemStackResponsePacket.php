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

namespace pocketmine\network\bedrock\adapter\v422\protocol;

use pocketmine\network\bedrock\protocol\types\itemStack\StackResponseSlotInfo;

class ItemStackResponsePacket extends \pocketmine\network\bedrock\protocol\ItemStackResponsePacket{

	protected function getStackResponseSlotInfo() : StackResponseSlotInfo{
		$info = new StackResponseSlotInfo();
		$info->slot = $this->getByte();
		$info->hotbarSlot = $this->getByte();
		$info->count = $this->getByte();
		$info->stackNetworkId = $this->getVarInt();
		$info->customName = $this->getString();
		return $info;
	}

	protected function putStackResponseSlotInfo(StackResponseSlotInfo $info) : void{
		$this->putByte($info->slot);
		$this->putByte($info->hotbarSlot);
		$this->putByte($info->count);
		$this->putVarInt($info->stackNetworkId);
		$this->putString($info->customName);
	}
}

