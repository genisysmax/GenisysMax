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

use raklib\utils\InternetAddress;

class OpenConnectionReply2 extends OfflineMessage{
	public static $ID = MessageIdentifiers::ID_OPEN_CONNECTION_REPLY_2;

	/** @var int */
	public $serverID;
	/** @var InternetAddress */
	public $clientAddress;
	/** @var int */
	public $mtuSize;
	/** @var bool */
	public $serverSecurity = false;

	protected function encodePayload() : void{
		$this->writeMagic();
		$this->putLong($this->serverID);
		$this->putAddress($this->clientAddress);
		$this->putShort($this->mtuSize);
		$this->putByte($this->serverSecurity ? 1 : 0);
	}

	protected function decodePayload() : void{
		$this->readMagic();
		$this->serverID = $this->getLong();
		$this->clientAddress = $this->getAddress();
		$this->mtuSize = $this->getShort();
		$this->serverSecurity = $this->getByte() !== 0;
	}
}


