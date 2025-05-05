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


use pocketmine\item\Item;
use pocketmine\network\NetworkSession;

class UseItemPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::USE_ITEM_PACKET;

	public $x;
	public $y;
	public $z;
	public $blockId;
	public $face;
	public $fx;
	public $fy;
	public $fz;
	public $posX;
	public $posY;
	public $posZ;
	public $slot;
	/** @var Item */
	public $item;

	public function decodePayload(){
		$this->getBlockPosition($this->x, $this->y, $this->z);
		$this->blockId = $this->getUnsignedVarInt();
		$this->face = $this->getVarInt();
		$this->getVector3f($this->fx, $this->fy, $this->fz);
		$this->getVector3f($this->posX, $this->posY, $this->posZ);
		$this->slot = $this->getVarInt();
		$this->item = $this->getSlot();
	}

	public function encodePayload(){
		$this->putUnsignedVarInt($this->blockId);
		$this->putUnsignedVarInt($this->face);
		$this->putVector3f($this->fx, $this->fy, $this->fz);
		$this->putVector3f($this->posX, $this->posY, $this->posZ);
		$this->putVarInt($this->slot);
		$this->putSlot($this->item);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleUseItem($this);
	}

}


