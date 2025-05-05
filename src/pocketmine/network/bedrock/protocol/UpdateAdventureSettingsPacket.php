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

/**
 * Updates "adventure settings". In vanilla, these flags apply to the whole world. This differs from abilities, which
 * apply only to the local player itself.
 * In practice, there's no difference between the two for a custom server.
 * This includes flags such as worldImmutable (makes players unable to build), autoJump, showNameTags, noPvM, and noMvP.
 */
class UpdateAdventureSettingsPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::UPDATE_ADVENTURE_SETTINGS_PACKET;

	/** @var bool */
	public $noAttackingMobs;
	/** @var bool */
	public $noAttackingPlayers;
	/** @var bool */
	public $worldImmutable;
	/** @var bool */
	public $showNameTags;
	/** @var bool */
	public $autoJump;

	public function decodePayload(){
		$this->noAttackingMobs = $this->getBool();
		$this->noAttackingPlayers = $this->getBool();
		$this->worldImmutable = $this->getBool();
		$this->showNameTags = $this->getBool();
		$this->autoJump = $this->getBool();
	}

	public function encodePayload(){
		$this->putBool($this->noAttackingMobs);
		$this->putBool($this->noAttackingPlayers);
		$this->putBool($this->worldImmutable);
		$this->putBool($this->showNameTags);
		$this->putBool($this->autoJump);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleUpdateAdventureSettings($this);
	}
}


