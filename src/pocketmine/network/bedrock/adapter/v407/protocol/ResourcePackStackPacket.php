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

namespace pocketmine\network\bedrock\adapter\v407\protocol;

#include <rules/DataPacket.h>


use pocketmine\network\bedrock\protocol\types\Experiments;
use pocketmine\network\bedrock\protocol\types\resourcepacks\ResourcePackStackEntry;
use function count;

class ResourcePackStackPacket extends \pocketmine\network\bedrock\adapter\v662\protocol\ResourcePackStackPacket {
	use PacketTrait;

	public function decodePayload(){
		$this->mustAccept = $this->getBool();
		$behaviorPackCount = $this->getUnsignedVarInt();
		while($behaviorPackCount-- > 0){
            $this->behaviorPackStack[] = ResourcePackStackEntry::read($this);
		}

		$resourcePackCount = $this->getUnsignedVarInt();
		while($resourcePackCount-- > 0){
            $this->resourcePackStack[] = ResourcePackStackEntry::read($this);
		}

		$this->experiments = new Experiments([], $this->getBool());
		$this->baseGameVersion = $this->getString();
	}

	public function encodePayload(){
		$this->putBool($this->mustAccept);

		$this->putUnsignedVarInt(count($this->behaviorPackStack));
		foreach($this->behaviorPackStack as $entry){
            $entry->write($this);
		}

		$this->putUnsignedVarInt(count($this->resourcePackStack));
		foreach($this->resourcePackStack as $entry){
            $entry->write($this);
		}

		$this->putBool(count($this->experiments->experiments) > 0);
		$this->putString($this->baseGameVersion);
	}
}

