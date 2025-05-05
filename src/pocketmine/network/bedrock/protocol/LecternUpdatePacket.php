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

class LecternUpdatePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::LECTERN_UPDATE_PACKET;

	/** @var int */
	public $page;
	/** @var int */
	public $totalPages;
	/** @var int */
	public $x;
	/** @var int */
	public $z;
	/** @var int */
	public $y;
	/** @var bool */
	public $dropBook;

	public function decodePayload(){
		$this->page = $this->getByte();
		$this->totalPages = $this->getByte();
		$this->getBlockPosition($this->x, $this->y, $this->z);
		$this->dropBook = $this->getBool();
	}

	public function encodePayload(){
		$this->putByte($this->page);
		$this->putByte($this->totalPages);
		$this->putBlockPosition($this->x, $this->y, $this->z);
		$this->putBool($this->dropBook);
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleLecternUpdate($this);
	}
}


