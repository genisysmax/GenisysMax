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

class NetworkSettingsPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::NETWORK_SETTINGS_PACKET;

	public const COMPRESS_NOTHING = 0;
	public const COMPRESS_EVERYTHING = 1;

	/** @var int */
	public $compressionThreshold;
	/** @var int */
	public $compressionAlgorithm;
	/** @var bool */
	public $enableClientThrottling;
	/** @var int */
	public $clientThrottleThreshold;
	/** @var float */
	public $clientThrottleScalar;

	public function decodePayload(){
		$this->compressionThreshold = $this->getLShort();
		$this->compressionAlgorithm = $this->getLShort();
		$this->enableClientThrottling = $this->getBool();
		$this->clientThrottleThreshold = $this->getByte();
		$this->clientThrottleScalar = $this->getLFloat();
	}

	public function encodePayload(){
		$this->putLShort($this->compressionThreshold);
		$this->putLShort($this->compressionAlgorithm);
		$this->putBool($this->enableClientThrottling);
		$this->putByte($this->clientThrottleThreshold);
		$this->putLFloat($this->clientThrottleScalar);
	}

	public function canBeSentBeforeLogin() : bool{
		return true;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleNetworkSettings($this);
	}
}

