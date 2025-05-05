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


use pocketmine\network\bedrock\protocol\types\inventory\ItemInstance;
use pocketmine\network\NetworkSession;

class MobArmorEquipmentPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET;

	/** @var int */
	public $actorRuntimeId;
    /** @var ItemInstance  */
    public $head;
    /** @var ItemInstance  */
    public $chest;
    /** @var ItemInstance  */
    public $legs;
    /** @var ItemInstance  */
    public $feet;

	public function decodePayload(){
		$this->actorRuntimeId = $this->getActorRuntimeId();
        $this->head = $this->getItemInstance();
        $this->chest = $this->getItemInstance();
        $this->legs = $this->getItemInstance();
        $this->feet = $this->getItemInstance();
	}

	public function encodePayload(){
		$this->putActorRuntimeId($this->actorRuntimeId);
        $this->putItemInstance($this->head);
        $this->putItemInstance($this->chest);
        $this->putItemInstance($this->legs);
        $this->putItemInstance($this->feet);
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleMobArmorEquipment($this);
	}
}


