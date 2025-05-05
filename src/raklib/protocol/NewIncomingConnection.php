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

namespace raklib\protocol;

#include <rules/RakLibPacket.h>

use raklib\RakLib;
use raklib\server\SessionManager;
use raklib\utils\InternetAddress;
use function filter_var;
use function strlen;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;

class NewIncomingConnection extends Packet{
	public static $ID = MessageIdentifiers::ID_NEW_INCOMING_CONNECTION;

	/** @var InternetAddress */
	public $address;

	/** @var InternetAddress[] */
	public $systemAddresses = [];

	/** @var InternetAddress[] */
	public $realSystemAdresses = [];

	/** @var int */
	public $sendPingTime;
	/** @var int */
	public $sendPongTime;

	protected function encodePayload() : void{
		$this->putAddress($this->address);
		foreach($this->systemAddresses as $address){
			$this->putAddress($address);
		}
		$this->putLong($this->sendPingTime);
		$this->putLong($this->sendPongTime);
	}

	protected function decodePayload() : void{
		$this->address = $this->getAddress();

		//TODO: HACK!
		$stopOffset = strlen($this->buffer) - 16; //buffer length - sizeof(sendPingTime) - sizeof(sendPongTime)
		$dummy = new InternetAddress("0.0.0.0", 0, 4);
		for($i = 0; $i < RakLib::$SYSTEM_ADDRESS_COUNT; ++$i){
			if($this->offset >= $stopOffset){
				$this->systemAddresses[$i] = clone $dummy;
			}else{
				$this->systemAddresses[$i] = $this->realSystemAdresses[$i] = $this->getAddress();
			}
		}

		$this->sendPingTime = $this->getLong();
		$this->sendPongTime = $this->getLong();
	}

	public function checkValid(SessionManager $manager) : bool{
		if (count($this->realSystemAdresses) !== RakLib::$SYSTEM_ADDRESS_COUNT) {
			return false;
		}
		$ipv6 = $this->realSystemAdresses[0];
		$ipv4 = $this->realSystemAdresses[1];
		if ($ipv6->equals($this->address) || $ipv4->equals($this->address)) {
			return false;
		}
		if (!filter_var($ipv6->getIp(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			return false;
		}
		if ($this->sendPongTime < $manager->getRakNetTimeMS()) {
			return false;
		}
		return true;
	}
}


