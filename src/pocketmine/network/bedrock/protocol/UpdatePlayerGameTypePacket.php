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

class UpdatePlayerGameTypePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::UPDATE_PLAYER_GAME_TYPE_PACKET;

	/** @var int */
	public $gameType;
	/** @var int */
	public $playerUniqueId;
    public int $tick = 0;

	public function decodePayload(){
		$this->gameType = $this->getVarInt();
		$this->playerUniqueId = $this->getActorUniqueId();
        $this->tick = $this->getUnsignedVarInt();
	}

	public function encodePayload(){
		$this->putVarInt($this->gameType);
		$this->putActorUniqueId($this->playerUniqueId);
        $this->putUnsignedVarInt($this->tick);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleUpdatePlayerGameType($this);
	}
}

