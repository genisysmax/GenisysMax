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


use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\network\NetworkSession;

class UpdateTradePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::UPDATE_TRADE_PACKET;

	//TODO: find fields
	public $windowId;
	public $windowType = WindowTypes::TRADING; //Mojang hardcoded this -_-
	public $varint1;
	public $varint2;
	public $isWilling;
	public $traderEid;
	public $playerEid;
	public $displayName;
	public $offers;

	public function decodePayload(){
		$this->windowId = $this->getByte();
		$this->windowType = $this->getByte();
		$this->varint1 = $this->getVarInt();
		$this->varint2 = $this->getVarInt();
		$this->isWilling = $this->getBool();
		$this->traderEid = $this->getEntityUniqueId();
		$this->playerEid = $this->getEntityUniqueId();
		$this->displayName = $this->getString();
		$this->offers = $this->getRemaining();
	}

	public function encodePayload(){
		$this->putByte($this->windowId);
		$this->putByte($this->windowType);
		$this->putVarInt($this->varint1);
		$this->putVarInt($this->varint2);
		$this->putBool($this->isWilling);
		$this->putEntityUniqueId($this->traderEid);
		$this->putEntityUniqueId($this->playerEid);
		$this->putString($this->displayName);
		$this->put($this->offers);
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleUpdateTrade($this);
	}
}

