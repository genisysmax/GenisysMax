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


use pocketmine\network\mcpe\protocol\types\Skin;
use pocketmine\network\NetworkSession;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\Utils;
use function base64_decode;
use function json_decode;

class LoginPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::LOGIN_PACKET;

	public const EDITION_POCKET = 0;

	/** @var string */
	public $username;
	/** @var int */
	public $protocol;
	/** @var int */
	public $gameEdition;
	/** @var string (raw UUID) */
	public $clientUUID;
	/** @var int */
	public $clientId;
	/** @var string */
	public $identityPublicKey;
	/** @var string */
	public $serverAddress;

	/** @var Skin */
	public $skin;

	/** @var array */
	public $chainData;
	/** @var array */
	public $clientData;
	/** @var string */
	public $clientDataJwt;

	/** @var string */
	public $languageCode;
	/** @var string */
	public $clientVersion;
	/** @var string */
	public $deviceModel;
	/** @var int */
	public $deviceOS;
	/** @var int */
	public $currentInputMode;
	/** @var int */
	public $defaultInputMode;
	/** @var int */
	public $uiProfile;


	/**
	 * This field may be used by plugins to bypass keychain verification. It should only be used for plugins such as
	 * Specter where passing verification would take too much time and not be worth it.
	 *
	 * @var bool
	 */
	public $skipVerification = false;

	public function canBeSentBeforeLogin() : bool{
		return true;
	}

	public function mayHaveUnreadBytes() : bool{
		return $this->protocol !== null and $this->protocol !== ProtocolInfo::CURRENT_PROTOCOL;
	}

	public function decodePayload(){
		$this->protocol = $this->getInt();
		if($this->protocol === ProtocolInfo::CURRENT_PROTOCOL){
			$this->gameEdition = $this->getByte();
			$this->decodeConnectionRequest();
		}
	}

	/**
	 * @throws \OutOfBoundsException
	 * @throws \UnexpectedValueException
	 */
	protected function decodeConnectionRequest() : void{
		$buffer = new BinaryStream($this->getString());

		$this->chainData = json_decode($buffer->get($buffer->getLInt()), true);

		$hasExtraData = false;
		foreach($this->chainData["chain"] as $chain){
			$webtoken = Utils::decodeJWT($chain);
			if(isset($webtoken["extraData"])){
				if($hasExtraData){
					throw new \RuntimeException("Found 'extraData' multiple times in key chain");
				}
				$hasExtraData = true;
				if(isset($webtoken["extraData"]["displayName"])){
					$this->username = $webtoken["extraData"]["displayName"];
				}
				if(isset($webtoken["extraData"]["identity"])){
					$this->clientUUID = $webtoken["extraData"]["identity"];
				}
			}

			if(isset($webtoken["identityPublicKey"])){
				$this->identityPublicKey = $webtoken["identityPublicKey"];
			}
		}

		$this->clientDataJwt = $buffer->get($buffer->getLInt());
		$this->clientData = Utils::decodeJWT($this->clientDataJwt);

		$this->clientId = $this->clientData["ClientRandomId"] ?? 0;
		$this->serverAddress = $this->clientData["ServerAddress"] ?? "";

		$this->skin = new Skin($this->clientData["SkinId"] ?? "", base64_decode($this->clientData["SkinData"] ?? ""));
		if(isset($this->clientData["LanguageCode"])){
			$this->languageCode = $this->clientData["LanguageCode"];
		}
		if(isset($this->clientData["DeviceModel"])){
			$this->deviceModel = $this->clientData["DeviceModel"];
		}
		if(isset($this->clientData["DeviceOS"])){
			$this->deviceOS = $this->clientData["DeviceOS"];
		}
		if(isset($this->clientData["CurrentInputMode"])){
			$this->currentInputMode = $this->clientData["CurrentInputMode"];
		}
		if(isset($this->clientData["DefaultInputMode"])){
			$this->defaultInputMode = $this->clientData["DefaultInputMode"];
		}
		if(isset($this->clientData["UIProfile"])){
			$this->uiProfile = $this->clientData["UIProfile"];
		}
		$this->clientVersion = $this->clientData["GameVersion"] ?? "";
 	}

	public function encodePayload(){
		//TODO
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleLogin($this);
	}
}


