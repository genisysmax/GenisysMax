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

class CommandBlockUpdatePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::COMMAND_BLOCK_UPDATE_PACKET;

	/** @var bool */
	public $isBlock;

	/** @var int */
	public $x;
	/** @var int */
	public $y;
	/** @var int */
	public $z;
	/** @var int */
	public $commandBlockMode;
	/** @var bool */
	public $isRedstoneMode;
	/** @var bool */
	public $isConditional;

	/** @var int */
	public $minecartRuntimeId;

	/** @var string */
	public $command;
	/** @var string */
	public $lastOutput;
	/** @var string */
	public $name;
	/** @var bool */
	public $shouldTrackOutput;
	/** @var int */
	public $tickDelay;
	/** @var bool */
	public $executeOnFirstTick;

	public function decodePayload(){
		$this->isBlock = $this->getBool();

		if($this->isBlock){
			$this->getBlockPosition($this->x, $this->y, $this->z);
			$this->commandBlockMode = $this->getUnsignedVarInt();
			$this->isRedstoneMode = $this->getBool();
			$this->isConditional = $this->getBool();
		}else{
			//Minecart with command block
			$this->minecartRuntimeId = $this->getActorRuntimeId();
		}

		$this->command = $this->getString();
		$this->lastOutput = $this->getString();
		$this->name = $this->getString();

		$this->shouldTrackOutput = $this->getBool();
		$this->tickDelay = $this->getLInt();
		$this->executeOnFirstTick = $this->getBool();
	}

	public function encodePayload(){
		$this->putBool($this->isBlock);

		if($this->isBlock){
			$this->putBlockPosition($this->x, $this->y, $this->z);
			$this->putUnsignedVarInt($this->commandBlockMode);
			$this->putBool($this->isRedstoneMode);
			$this->putBool($this->isConditional);
		}else{
			$this->putActorRuntimeId($this->minecartRuntimeId);
		}

		$this->putString($this->command);
		$this->putString($this->lastOutput);
		$this->putString($this->name);

		$this->putBool($this->shouldTrackOutput);
		$this->putLInt($this->tickDelay);
		$this->putBool($this->executeOnFirstTick);
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCommandBlockUpdate($this);
	}
}


