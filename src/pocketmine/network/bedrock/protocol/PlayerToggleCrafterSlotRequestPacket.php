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

use pocketmine\network\NetworkSession;

class PlayerToggleCrafterSlotRequestPacket extends DataPacket{
    public const NETWORK_ID = ProtocolInfo::PLAYER_TOGGLE_CRAFTER_SLOT_REQUEST_PACKET;

    private int $x;
    private int $y;
    private int $z;
    private int $slot;
	private int $disabled;

    public static function create(int $x, int $y, int $z, int $slot, int $disabled) : self{
		$result = new self;
		$result->x = $x;
        $result->y = $y;
        $result->z = $z;
		$result->slot = $slot;
		$result->disabled = $disabled;
		return $result;
	}

    public function decodePayload() {
        $this->getBlockPosition($this->x, $this->y, $this->z);
        $this->slot = $this->getByte();
        $this->disabled = $this->getByte();
    }

    public function encodePayload() {
        $this->putBlockPosition($this->x, $this->y, $this->z);
        $this->putByte($this->slot);
        $this->putByte($this->disabled);
    }

    public function mustBeDecoded() : bool{
		return false;
	}

    public function handle(NetworkSession $session) : bool{
        return $session->handlePlayerToggleCrafterSlotRequest($this);
    }
}

