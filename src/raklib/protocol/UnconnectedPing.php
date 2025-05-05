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

class UnconnectedPing extends OfflineMessage{
	public static $ID = MessageIdentifiers::ID_UNCONNECTED_PING;

	/** @var int */
	public $sendPingTime;
	/** @var int */
	public $clientId;

	protected function encodePayload() : void{
		$this->putLong($this->sendPingTime);
		$this->writeMagic();
		$this->putLong($this->clientId);
	}

	protected function decodePayload() : void{
		$this->sendPingTime = $this->getLong();
		$this->readMagic();
		$this->clientId = $this->getLong();
	}
}


