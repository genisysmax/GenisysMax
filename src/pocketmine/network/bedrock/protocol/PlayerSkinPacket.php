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

use pocketmine\network\bedrock\protocol\types\skin\Skin;
use pocketmine\network\NetworkSession;
use pocketmine\utils\UUID;

class PlayerSkinPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_SKIN_PACKET;

	public UUID $uuid;
	public Skin $skin;
	public string $oldSkinName = "";
	public string $newSkinName = "";

	public function decodePayload(){
		$this->uuid = $this->getUUID();
		$this->skin = $this->getSkin();
		$this->newSkinName = $this->getString();
		$this->oldSkinName = $this->getString();

		$verified = $this->getBool();
		$this->skin->setVerified($verified);
	}

	public function encodePayload(){
		$this->putUUID($this->uuid);
		$this->putSkin($this->skin);
		$this->putString($this->newSkinName);
		$this->putString($this->oldSkinName);
		$this->putBool($this->skin->isVerified());
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handlePlayerSkin($this);
	}
}


