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

namespace pocketmine\network\bedrock\adapter\v567\protocol;

use pocketmine\network\bedrock\protocol\types\skin\Skin;
use pocketmine\network\bedrock\protocol\types\skin\SkinAnimation;
use pocketmine\utils\UUID;
use UnexpectedValueException;
use function count;

trait PacketTrait{

    /**
     * @return Skin
     */
    public function getSkin() : Skin{
        $skinId = $this->getString();
        $skinPlayFabId = $this->getString();
        $skinResourcePatch = $this->getString();
        $skinImage = $this->getImage();

        $animations = [];
        $count = $this->getLInt();
        if($count > 128){
            throw new UnexpectedValueException("Too many skin animations: $count");
        }
        for($i = 0; $i < $count; ++$i){
            $image = $this->getImage();
            $type = $this->getLInt();
            $frames = $this->getLFloat();
            $expressionType = $this->getLInt();
            $animations[] = new SkinAnimation($image, $type, $frames, $expressionType);
        }

        $capeImage = $this->getImage();
        $geometryData = $this->getString();
        $geometryDataVersion = $this->getString();
        $animationData = $this->getString();
        $capeId = $this->getString();
        $fullSkinId = $this->getString();
        $armSize = $this->getString();
        $skinColor = $this->getString();

        $personaPieces = [];
        $count = $this->getLInt();
        if($count > 128){
            throw new UnexpectedValueException("Too many persona pieces: $count");
        }
        for($i = 0; $i < $count; ++$i){
            $personaPieces[] = $this->getPersonaPiece();
        }

        $pieceTintColors = [];
        $count = $this->getLInt();
        if($count > 128){
            throw new UnexpectedValueException("Too many piece tint colors: $count");
        }
        for($i = 0; $i < $count; ++$i){
            $pieceTintColors[] = $this->getPieceTintColor();
        }

        $isPremium = $this->getBool();
        $isPersona = $this->getBool();
        $isCapeOnClassic = $this->getBool();
        $isPrimaryUser = $this->getBool();

        return new Skin($skinId, $skinPlayFabId, $skinResourcePatch, $skinImage, $animations, $capeImage, $geometryData, $animationData, $isPremium, $isPersona, $isCapeOnClassic, $capeId, $fullSkinId, $armSize, $skinColor, $personaPieces, $pieceTintColors, true, $geometryDataVersion, $isPrimaryUser);
    }

    /**
     * @param Skin $skin
     */
    public function putSkin(Skin $skin) : void{
        $this->putString($skin->getSkinId());
        $this->putString($skin->getPlayFabId());
        $this->putString($skin->getSkinResourcePatch());
        $this->putImage($skin->getSkinImage());

        $animations = $skin->getAnimations();
        $this->putLInt(count($animations));
        foreach($animations as $animation){
            $this->putImage($animation->getImage());
            $this->putLInt($animation->getType());
            $this->putLFloat($animation->getFrames());
            $this->putLInt($animation->getExpressionType());
        }

        $this->putImage($skin->getCapeImage());
        $this->putString($skin->getGeometryData());
        $this->putString($skin->getGeometryDataEngineVersion());
        $this->putString($skin->getAnimationData());
        $this->putString($skin->getCapeId());
        $this->putString(UUID::fromRandom()->toString()); // TODO: different full skin ID every time, a hack for 1.19.x bug
        $this->putString($skin->getArmSize());
        $this->putString($skin->getSkinColor());

        $this->putLInt(count($skin->getPersonaPieces()));
        foreach($skin->getPersonaPieces() as $personaPiece){
            $this->putPersonaPiece($personaPiece);
        }

        $this->putLInt(count($skin->getPieceTintColors()));
        foreach($skin->getPieceTintColors() as $pieceTintColor){
            $this->putPieceTintColor($pieceTintColor);
        }

        $this->putBool($skin->isPremium());
        $this->putBool($skin->isPersona());
        $this->putBool($skin->isCapeOnClassic());
        $this->putBool($skin->isPrimaryUser());
    }
}

