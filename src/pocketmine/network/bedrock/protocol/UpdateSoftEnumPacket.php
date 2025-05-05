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
use function count;

class UpdateSoftEnumPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::UPDATE_SOFT_ENUM_PACKET;

	public const TYPE_ADD = 0;
	public const TYPE_REMOVE = 1;
	public const TYPE_SET = 2;

	/** @var string */
	public $enumName;
	/** @var string[] */
	public $values = [];
	/** @var int */
	public $type;

	public function decodePayload(){
		$this->enumName = $this->getString();
		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$this->values[] = $this->getString();
		}
		$this->type = $this->getByte();
	}

	public function encodePayload(){
		$this->putString($this->enumName);
		$this->putUnsignedVarInt(count($this->values));
		foreach($this->values as $v){
			$this->putString($v);
		}
		$this->putByte($this->type);
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleUpdateSoftEnum($this);
	}
}


