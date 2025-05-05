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


use pocketmine\network\NetworkSession;

class PlayerActionPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_ACTION_PACKET;

	public const ACTION_START_BREAK = 0;
	public const ACTION_ABORT_BREAK = 1;
	public const ACTION_STOP_BREAK = 2;


	public const ACTION_RELEASE_ITEM = 5;
	public const ACTION_STOP_SLEEPING = 6;
	public const ACTION_RESPAWN = 7;
	public const ACTION_JUMP = 8;
	public const ACTION_START_SPRINT = 9;
	public const ACTION_STOP_SPRINT = 10;
	public const ACTION_START_SNEAK = 11;
	public const ACTION_STOP_SNEAK = 12;
	public const ACTION_DIMENSION_CHANGE_REQUEST = 13; //sent when dying in different dimension
	public const ACTION_DIMENSION_CHANGE_ACK = 14; //sent when spawning in a different dimension to tell the server we spawned
	public const ACTION_START_GLIDE = 15;
	public const ACTION_STOP_GLIDE = 16;
	public const ACTION_BUILD_DENIED = 17;
	public const ACTION_CONTINUE_BREAK = 18;

	public $entityRuntimeId;
	public $action;
	public $x;
	public $y;
	public $z;
	public $face;

	public function decodePayload(){
		$this->entityRuntimeId = $this->getEntityRuntimeId();
		$this->action = $this->getVarInt();
		$this->getBlockPosition($this->x, $this->y, $this->z);
		$this->face = $this->getVarInt();
	}

	public function encodePayload(){
		$this->putEntityRuntimeId($this->entityRuntimeId);
		$this->putVarInt($this->action);
		$this->putBlockPosition($this->x, $this->y, $this->z);
		$this->putVarInt($this->face);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayerAction($this);
	}

}


