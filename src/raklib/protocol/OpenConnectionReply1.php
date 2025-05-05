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

class OpenConnectionReply1 extends OfflineMessage{
	public static $ID = MessageIdentifiers::ID_OPEN_CONNECTION_REPLY_1;

	/** @var int */
	public $serverID;
	/** @var bool */
	public $serverSecurity = false;
	/** @var int */
	public $mtuSize;

	protected function encodePayload() : void{
		$this->writeMagic();
		$this->putLong($this->serverID);
		$this->putByte($this->serverSecurity ? 1 : 0);
		$this->putShort($this->mtuSize);

		$this->put(str_repeat("\x00", $this->mtuSize - strlen($this->buffer) - 28)); //a hack to avoid getting the wrong mtu size
	}

	protected function decodePayload() : void{
		$this->readMagic();
		$this->serverID = $this->getLong();
		$this->serverSecurity = $this->getByte() !== 0;
		$this->mtuSize = $this->getShort();
	}
}


