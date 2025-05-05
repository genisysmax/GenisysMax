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
use function str_pad;
use function strlen;

class OpenConnectionRequest1 extends OfflineMessage{
	public static $ID = MessageIdentifiers::ID_OPEN_CONNECTION_REQUEST_1;

	/** @var int */
	public $protocol = RakLib::DEFAULT_PROTOCOL_VERSION;
	/** @var int */
	public $mtuSize;

	protected function encodePayload() : void{
		$this->writeMagic();
		$this->putByte($this->protocol);
		$this->buffer = str_pad($this->buffer, $this->mtuSize, "\x00");
	}

	protected function decodePayload() : void{
		$this->readMagic();
		$this->protocol = $this->getByte();
		$this->mtuSize = strlen($this->buffer);
		$this->getRemaining(); //silence unread warnings
	}
}


