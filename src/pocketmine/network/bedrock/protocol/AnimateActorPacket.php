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
use UnexpectedValueException;
use function count;

class AnimateActorPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ANIMATE_ACTOR_PACKET;

	/** @var string */
	public $animation;
	/** @var string */
	public $nextState;
	/** @var string */
	public $stopExpression;
	/** @var string */
	public $controller;
	/** @var float */
	public $blendOutTime;
	/** @var int[] */
	public $actorRuntimeIds = [];

	public function decodePayload(){
		$this->animation = $this->getString();
		$this->nextState = $this->getString();
		$this->stopExpression = $this->getString();
		$this->controller = $this->getString();
		$this->blendOutTime = $this->getLFloat();

		$count = $this->getUnsignedVarInt();
		if($count > 128){
			throw new UnexpectedValueException("Too many actor runtime ID in AnimateEntity: $count");
		}
		for($i = 0; $i < $count; ++$i){
			$this->actorRuntimeIds[] = $this->getActorRuntimeId();
		}
	}

	public function encodePayload(){
		$this->putString($this->animation);
		$this->putString($this->nextState);
		$this->putString($this->stopExpression);
		$this->putString($this->controller);
		$this->putLFloat($this->blendOutTime);
		$this->putUnsignedVarInt(count($this->actorRuntimeIds));
		foreach($this->actorRuntimeIds as $id){
			$this->putEntityRuntimeId($id);
		}
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAnimateActor($this);
	}
}


