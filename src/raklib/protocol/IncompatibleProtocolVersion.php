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

class IncompatibleProtocolVersion extends OfflineMessage{
	public static $ID = MessageIdentifiers::ID_INCOMPATIBLE_PROTOCOL_VERSION;

	/** @var int */
	public $protocolVersion;
	/** @var int */
	public $serverId;

	protected function encodePayload() : void{
		$this->putByte($this->protocolVersion);
		$this->writeMagic();
		$this->putLong($this->serverId);
	}

	protected function decodePayload() : void{
		$this->protocolVersion = $this->getByte();
		$this->readMagic();
		$this->serverId = $this->getLong();
	}
}


