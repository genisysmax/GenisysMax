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

use pocketmine\network\NetworkSession;

class MapInfoRequestPacket extends DataPacket{
    public const NETWORK_ID = ProtocolInfo::MAP_INFO_REQUEST_PACKET;

    public $mapId;

    public function decodePayload(){
        $this->mapId = $this->getEntityUniqueId();
    }

    public function encodePayload(){
        $this->putEntityUniqueId($this->mapId);
    }

    public function mustBeDecoded() : bool{
        return false;
    }

    public function handle(NetworkSession $session) : bool{
        return $session->handleMapInfoRequest($this);
    }
}

