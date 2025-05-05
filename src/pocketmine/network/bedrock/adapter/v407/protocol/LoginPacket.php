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

namespace pocketmine\network\bedrock\adapter\v407\protocol;

use pocketmine\network\bedrock\protocol\types\skin\PersonaPiece;
use pocketmine\network\bedrock\protocol\types\skin\PieceTintColor;
use pocketmine\network\bedrock\protocol\types\skin\SerializedSkinImage;
use pocketmine\network\bedrock\protocol\types\skin\Skin;
use pocketmine\network\bedrock\protocol\types\skin\SkinAnimation;
use function base64_decode;

class LoginPacket extends \pocketmine\network\bedrock\protocol\LoginPacket{
	use PacketTrait;

	public function mayHaveUnreadBytes() : bool{
		return $this->protocol !== null and $this->protocol !== ProtocolInfo::CURRENT_PROTOCOL;
	}

	public function decodePayload(){
		$this->protocol = $this->getInt();
		if($this->protocol === ProtocolInfo::CURRENT_PROTOCOL){
			$this->decodeConnectionRequest();
		}
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
                    SkinAnimation::EXPRESSION_LINEAR
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
            "",
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
            true
        );
    }
}

