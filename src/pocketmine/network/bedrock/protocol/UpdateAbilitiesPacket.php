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


use pocketmine\network\bedrock\protocol\types\command\CommandPermissions;
use pocketmine\network\bedrock\protocol\types\PlayerPermissions;
use pocketmine\network\bedrock\protocol\types\UpdateAbilitiesPacketLayer;
use pocketmine\network\NetworkSession;

/**
 * Updates player abilities and permissions, such as command permissions, flying/noclip, fly speed, walk speed etc.
 * Abilities may be layered in order to combine different ability sets into a resulting set.
 */
class UpdateAbilitiesPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::UPDATE_ABILITIES_PACKET;

	/** @var int */
	public $commandPermission = CommandPermissions::NORMAL;
	/** @var int */
	public $playerPermission = PlayerPermissions::MEMBER;
	/** @var int */
	public $targetActorUniqueId; //This is a little-endian long, NOT a var-long. (WTF Mojang)
	/** @var UpdateAbilitiesPacketLayer[]> */
	public $abilityLayers;

	public function decodePayload(){
		$this->targetActorUniqueId = $this->getLLong(); //WHY IS THIS NON-STANDARD?
		$this->playerPermission = $this->getByte();
		$this->commandPermission = $this->getByte();

		$this->abilityLayers = [];
		for($i = 0, $len = $this->getByte(); $i < $len; $i++){
			$this->abilityLayers[] = UpdateAbilitiesPacketLayer::decode($this);
		}
	}

	public function encodePayload(){
		$this->putLLong($this->targetActorUniqueId);
		$this->putByte($this->playerPermission);
		$this->putByte($this->commandPermission);

		$this->putByte(count($this->abilityLayers));
		foreach($this->abilityLayers as $layer){
			$layer->encode($this);
		}
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleUpdateAbilities($this);
	}
}


