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

use pocketmine\network\NetworkSession;
use pocketmine\utils\Color;
use function assert;
use function count;

class ClientboundMapItemDataPacket extends DataPacket{
    public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_MAP_ITEM_DATA_PACKET;

    public const BITFLAG_TEXTURE_UPDATE = 0x02;
    public const BITFLAG_DECORATION_UPDATE = 0x04;

    public $mapId;
    public $type;

    public $eids = [];
    public $scale;
    public $decorations = [];

    public $width;
    public $height;
    public $xOffset = 0;
    public $yOffset = 0;
    /** @var Color[][] */
    public $colors = [];

    public function decodePayload(){
        $this->mapId = $this->getEntityUniqueId();
        $this->type = $this->getUnsignedVarInt();

        if(($this->type & 0x08) !== 0){
            $count = $this->getUnsignedVarInt();
            for($i = 0; $i < $count; ++$i){
                $this->eids[] = $this->getEntityUniqueId();
            }
        }

        if(($this->type & (self::BITFLAG_DECORATION_UPDATE | self::BITFLAG_TEXTURE_UPDATE)) !== 0){ //Decoration bitflag or colour bitflag
            $this->scale = $this->getByte();
        }

        if(($this->type & self::BITFLAG_DECORATION_UPDATE) !== 0){
            $count = $this->getUnsignedVarInt();
            for($i = 0; $i < $count; ++$i){
                $weird = $this->getVarInt();
                $this->decorations[$i]["rot"] = $weird & 0x0f;
                $this->decorations[$i]["img"] = $weird >> 4;

                $this->decorations[$i]["xOffset"] = $this->getByte();
                $this->decorations[$i]["yOffset"] = $this->getByte();
                $this->decorations[$i]["label"] = $this->getString();

                $this->decorations[$i]["color"] = Color::fromARGB($this->getLInt()); //already BE, don't need to reverse it again
            }
        }

        if(($this->type & self::BITFLAG_TEXTURE_UPDATE) !== 0){
            $this->width = $this->getVarInt();
            $this->height = $this->getVarInt();
            $this->xOffset = $this->getVarInt();
            $this->yOffset = $this->getVarInt();
            for($y = 0; $y < $this->height; ++$y){
                for($x = 0; $x < $this->width; ++$x){
                    $this->colors[$y][$x] = Color::fromABGR($this->getUnsignedVarInt());
                }
            }
        }
    }

    public function encodePayload(){
        $this->putEntityUniqueId($this->mapId);

        $type = 0;
        if(($eidsCount = count($this->eids)) > 0){
            $type |= 0x08;
        }
        if(($decorationCount = count($this->decorations)) > 0){
            $type |= self::BITFLAG_DECORATION_UPDATE;
        }
        if(count($this->colors) > 0){
            $type |= self::BITFLAG_TEXTURE_UPDATE;
        }

        $this->putUnsignedVarInt($type);

        if(($type & 0x08) !== 0){ //TODO: find out what these are for
            $this->putUnsignedVarInt($eidsCount);
            foreach($this->eids as $eid){
                $this->putEntityUniqueId($eid);
            }
        }

        if(($type & (self::BITFLAG_TEXTURE_UPDATE | self::BITFLAG_DECORATION_UPDATE)) !== 0){
            $this->putByte($this->scale);
        }

        if(($type & self::BITFLAG_DECORATION_UPDATE) !== 0){
            $this->putUnsignedVarInt($decorationCount);
            foreach($this->decorations as $decoration){
                $this->putVarInt(($decoration["rot"] & 0x0f) | ($decoration["img"] << 4));
                $this->putByte($decoration["xOffset"]);
                $this->putByte($decoration["yOffset"]);
                $this->putString($decoration["label"]);
                assert($decoration["color"] instanceof Color);
                $this->putLInt($decoration["color"]->toARGB());
            }
        }

        if(($type & self::BITFLAG_TEXTURE_UPDATE) !== 0){
            $this->putVarInt($this->width);
            $this->putVarInt($this->height);
            $this->putVarInt($this->xOffset);
            $this->putVarInt($this->yOffset);
            for($y = 0; $y < $this->height; ++$y){
                for($x = 0; $x < $this->width; ++$x){
                    $this->putUnsignedVarInt($this->colors[$y][$x]->toABGR());
                }
            }
        }
    }

    public function mustBeDecoded() : bool{
        return false;
    }

    public function handle(NetworkSession $session) : bool{
        return $session->handleClientboundMapItemData($this);
    }
}

