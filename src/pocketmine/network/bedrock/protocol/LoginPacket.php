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

use pocketmine\network\bedrock\protocol\types\OS;
use pocketmine\network\bedrock\protocol\types\skin\PersonaPiece;
use pocketmine\network\bedrock\protocol\types\skin\PieceTintColor;
use pocketmine\network\bedrock\protocol\types\skin\SerializedSkinImage;
use pocketmine\network\bedrock\protocol\types\skin\Skin;
use pocketmine\network\bedrock\protocol\types\skin\SkinAnimation;
use pocketmine\network\NetworkSession;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\Utils;
use function base64_decode;
use function json_decode;

class LoginPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::LOGIN_PACKET;

	/** @var string */
	public $username;
	/** @var int */
	public $protocol;
	/** @var string */
	public $clientUUID;
	/** @var int */
	public $clientId;
	/** @var string */
	public $xuid = "";
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
	/** @var string */
	public $deviceId;

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
				if(isset($webtoken["extraData"]["XUID"])){
					$this->xuid = $webtoken["extraData"]["XUID"];
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

		$this->decodeSkin();

		$this->languageCode = $this->clientData["LanguageCode"] ?? "";
		$this->deviceModel = $this->clientData["DeviceModel"] ?? "";
		$this->deviceOS = $this->clientData["DeviceOS"] ?? OS::UNKNOWN;
		$this->currentInputMode = $this->clientData["CurrentInputMode"] ?? 0;
		$this->defaultInputMode = $this->clientData["DefaultInputMode"] ?? 0;
		$this->deviceId = $this->clientData["DeviceId"] ?? "";
		$this->uiProfile = $this->clientData["UIProfile"] ?? 0;
		$this->clientVersion = $this->clientData["GameVersion"] ?? "";
	}

    protected function decodeSkin() : void{
        if(isset($this->clientData["SkinData"])){
            $data = base64_decode($this->clientData["SkinData"]);
            if(isset($this->clientData["SkinImageWidth"]) and isset($this->clientData["SkinImageHeight"])){
                $skinImage = new SerializedSkinImage((int) $this->clientData["SkinImageWidth"], (int) $this->clientData["SkinImageHeight"], $data);
            }else{
                $skinImage = SerializedSkinImage::fromLegacyImageData($data);
            }
        }else{
            $skinImage = SerializedSkinImage::empty();
        }

        if(isset($this->clientData["CapeData"])){
            $data = base64_decode($this->clientData["CapeData"]);
            if(isset($this->clientData["CapeImageWidth"]) and isset($this->clientData["CapeImageHeight"])){
                $capeImage = new SerializedSkinImage((int) $this->clientData["CapeImageWidth"], (int) $this->clientData["CapeImageHeight"], $data);
            }else{
                $capeImage = SerializedSkinImage::fromLegacyImageData($data);
            }
        }else{
            $capeImage = SerializedSkinImage::empty();
        }

        $animations = [];
        if(isset($this->clientData["AnimatedImageData"])){
            foreach($this->clientData["AnimatedImageData"] as $data){
                $animations[] = new SkinAnimation(
                    new SerializedSkinImage($data["ImageWidth"], $data["ImageHeight"], base64_decode($data["Image"])),
                    $data["Type"],
                    $data["Frames"],
                    $data["AnimationExpression"]
                );
            }
        }

        $personaPieces = [];
        if(isset($this->clientData["PersonaPieces"])){
            foreach($this->clientData["PersonaPieces"] as $data){
                $personaPieces[] = new PersonaPiece($data["PieceId"], $data["PieceType"], $data["PackId"], $data["IsDefault"], $data["ProductId"]);
            }
        }

        $pieceTintColors = [];
        if(isset($this->clientData["PieceTintColors"])){
            foreach($this->clientData["PieceTintColors"] as $data){
                $pieceTintColors[] = new PieceTintColor($data["PieceType"], $data["Colors"]);
            }
        }

        $this->skin = new Skin(
            $this->clientData["SkinId"] ?? "",
            $this->clientData["PlayFabId"] ?? "",
            base64_decode($this->clientData["SkinResourcePatch"] ?? ""),
            $skinImage,
            $animations,
            $capeImage,
            base64_decode($this->clientData["SkinGeometryData"] ?? ""),
            base64_decode($this->clientData["AnimationData"] ?? ""),
            (bool) ($this->clientData["PremiumSkin"] ?? false),
            (bool) ($this->clientData["PersonaSkin"] ?? false),
            (bool) ($this->clientData["CapeOnClassicSkin"] ?? false),
            $this->clientData["CapeId"] ?? "",
            null,
            $this->clientData["ArmSize"] ?? "wide",
            $this->clientData["SkinColor"] ?? "#0",
            $personaPieces,
            $pieceTintColors,
            true,
            base64_decode($this->clientData["SkinGeometryDataEngineVersion"] ?? ""),
            true,
            $this->clientData["OverrideSkin"] ?? true
        );
    }

	public function encodePayload(){
		//TODO
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleLogin($this);
	}
}


