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


use pocketmine\math\Vector3;
use pocketmine\network\NetworkSession;
use function count;

class ExplodePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::EXPLODE_PACKET;

	public $x;
	public $y;
	public $z;
	/** @var float */
	public $radius;
	/** @var Vector3[] */
	public $records = [];

	public function clean(){
		$this->records = [];
		return parent::clean();
	}

	public function decodePayload(){
		$this->getVector3f($this->x, $this->y, $this->z);
		$this->radius = (float) ($this->getVarInt() / 32);
		$count = $this->getUnsignedVarInt();
		for($i = 0; $i < $count; ++$i){
			$x = $y = $z = null;
			$this->getSignedBlockPosition($x, $y, $z);
			$this->records[$i] = new Vector3($x, $y, $z);
		}
	}

	public function encodePayload(){
		$this->putVector3f($this->x, $this->y, $this->z);
		$this->putVarInt((int) ($this->radius * 32));
		$this->putUnsignedVarInt(count($this->records));
		if(count($this->records) > 0){
			foreach($this->records as $record){
				$this->putSignedBlockPosition((int) $record->x, (int) $record->y, (int) $record->z);
			}
		}
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleExplode($this);
	}

}

