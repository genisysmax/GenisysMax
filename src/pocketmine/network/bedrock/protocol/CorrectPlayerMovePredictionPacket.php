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


use pocketmine\math\Vector3;
use pocketmine\network\NetworkSession;

class CorrectPlayerMovePredictionPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CORRECT_PLAYER_MOVE_PREDICTION_PACKET;

	/** @var Vector3 */
	public $position;
	/** @var Vector3 */
	public $delta;
	/** @var bool */
	public $onGround;
	/** @var int */
	public $tick;

	public function decodePayload(){
		$this->position = $this->getVector3();
		$this->delta = $this->getVector3();
		$this->onGround = $this->getBool();
		$this->tick = $this->getUnsignedVarLong();
	}

	public function encodePayload(){
		$this->putVector3($this->position);
		$this->putVector3($this->delta);
		$this->putBool($this->onGround);
		$this->putUnsignedVarLong($this->tick);
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCorrectPlayerMovePrediction($this);
	}
}


