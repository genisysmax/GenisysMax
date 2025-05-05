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

class PlayerActionPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_ACTION_PACKET;

	public const ACTION_START_BREAK = 0;
	public const ACTION_ABORT_BREAK = 1;
	public const ACTION_STOP_BREAK = 2;
	public const ACTION_GET_UPDATED_BLOCK = 3;
	public const ACTION_DROP_ITEM = 4;
	public const ACTION_START_SLEEPING = 5;
	public const ACTION_STOP_SLEEPING = 6;
	public const ACTION_RESPAWN = 7;
	public const ACTION_JUMP = 8;
	public const ACTION_START_SPRINT = 9;
	public const ACTION_STOP_SPRINT = 10;
	public const ACTION_START_SNEAK = 11;
	public const ACTION_STOP_SNEAK = 12;
	public const ACTION_CREATIVE_PLAYER_DESTROY_BLOCK = 13;
	public const ACTION_DIMENSION_CHANGE_ACK = 14; //sent when spawning in a different dimension to tell the server we spawned
	public const ACTION_START_GLIDE = 15;
	public const ACTION_STOP_GLIDE = 16;
	public const ACTION_BUILD_DENIED = 17;
	public const ACTION_CRACK_BREAK = 18;
	public const ACTION_CHANGE_SKIN = 19;
	public const ACTION_SET_ENCHANTMENT_SEED = 20;
	public const ACTION_START_SWIMMING = 21;
	public const ACTION_STOP_SWIMMING = 22;
	public const ACTION_START_SPIN_ATTACK = 23;
	public const ACTION_STOP_SPIN_ATTACK = 24;
	public const ACTION_INTERACT_BLOCK = 25;
	public const ACTION_PREDICT_DESTROY_BLOCK = 26;
	public const ACTION_CONTINUE_DESTROY_BLOCK = 27;
	public const ACTION_START_ITEM_USE_ON = 28;
	public const ACTION_STOP_ITEM_USE_ON = 29;
	public const HANDLED_TELEPORT = 30;
	public const MISSED_SWING = 31;
	public const START_CRAWLING = 32;
	public const STOP_CRAWLING = 33;

	/** @var int */
	public $actorRuntimeId;
	/** @var int */
	public $action;
	/** @var int */
	public $x;
	/** @var int */
	public $y;
	/** @var int */
	public $z;
	/** @var int */
	public $resultX;
	/** @var int */
	public $resultY;
	/** @var int */
	public $resultZ;
	/** @var int */
	public $face;

	public function decodePayload(){
		$this->actorRuntimeId = $this->getActorRuntimeId();
		$this->action = $this->getVarInt();
		$this->getBlockPosition($this->x, $this->y, $this->z);
		$this->getBlockPosition($this->resultX, $this->resultY, $this->resultZ);
		$this->face = $this->getVarInt();
	}

	public function encodePayload(){
		$this->putActorRuntimeId($this->actorRuntimeId);
		$this->putVarInt($this->action);
		$this->putBlockPosition($this->x, $this->y, $this->z);
		$this->putBlockPosition($this->resultX, $this->resultY, $this->resultZ);
		$this->putVarInt($this->face);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayerAction($this);
	}
}


