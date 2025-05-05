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

class AdventureSettingsPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::ADVENTURE_SETTINGS_PACKET;

	public const PERMISSION_NORMAL = 0;
	public const PERMISSION_OPERATOR = 1;
	public const PERMISSION_HOST = 2;
	public const PERMISSION_AUTOMATION = 3;
	public const PERMISSION_ADMIN = 4;

	public $worldImmutable = false;
	public $noPvp = false;
	public $noPvm = false;
	public $noMvp = false;

	public $autoJump = true;
	public $allowFlight = false;
	public $noClip = false;
	public $worldBuilder = false;
	public $isFlying = false;
	public $muted = false;

	public $flags = 0;
	public $userPermission;

	public function decodePayload(){
		$this->flags = $this->getUnsignedVarInt();
		$this->userPermission = $this->getUnsignedVarInt();

		$this->worldImmutable = (bool) ($this->flags & 1);
		$this->noPvp          = (bool) ($this->flags & (1 << 1));
		$this->noPvm          = (bool) ($this->flags & (1 << 2));
		$this->noMvp          = (bool) ($this->flags & (1 << 3));

		$this->autoJump       = (bool) ($this->flags & (1 << 5));
		$this->allowFlight    = (bool) ($this->flags & (1 << 6));
		$this->noClip         = (bool) ($this->flags & (1 << 7));
		$this->worldBuilder   = (bool) ($this->flags & (1 << 8));
		$this->isFlying       = (bool) ($this->flags & (1 << 9));
		$this->muted          = (bool) ($this->flags & (1 << 10));
	}

	public function encodePayload(){
		$this->flags |= ((int) $this->worldImmutable);
		$this->flags |= ((int) $this->noPvp)        << 1;
		$this->flags |= ((int) $this->noPvm)        << 2;
		$this->flags |= ((int) $this->noMvp)        << 3;

		$this->flags |= ((int) $this->autoJump)     << 5;
		$this->flags |= ((int) $this->allowFlight)  << 6;
		$this->flags |= ((int) $this->noClip)       << 7;
		$this->flags |= ((int) $this->worldBuilder) << 8;
		$this->flags |= ((int) $this->isFlying)     << 9;
		$this->flags |= ((int) $this->muted)        << 10;

		$this->putUnsignedVarInt($this->flags);
		$this->putUnsignedVarInt($this->userPermission);
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleAdventureSettings($this);
	}

}

