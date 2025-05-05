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

use pocketmine\network\bedrock\protocol\types\DimensionIds;
use pocketmine\network\bedrock\protocol\types\MapTrackedObject;
use pocketmine\network\NetworkSession;
use pocketmine\utils\Color;
use function assert;
use function count;

class ClientboundMapItemDataPacket extends DataPacket{
    public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_MAP_ITEM_DATA_PACKET;

    public const BITFLAG_TEXTURE_UPDATE = 0x02;
    public const BITFLAG_DECORATION_UPDATE = 0x04;

    /** @var int */
    public $mapId;
    /** @var int */
    public $type;
    /** @var int */
    public $dimensionId = DimensionIds::OVERWORLD;
    /** @var bool */
    public $isLocked = false;
    /** @var int */
    public $originX = 0, $originY = 0, $originZ = 0;

    /** @var int[] */
    public $actorUniqueIds = [];
    /** @var int */
    public $scale;

    /** @var MapTrackedObject[] */
    public $trackedObjects = [];
    /** @var array */
    public $decorations = [];

    /** @var int */
    public $width;
    /** @var int */
    public $height;
    /** @var int */
    public $xOffset = 0;
    /** @var int */
    public $yOffset = 0;
    /** @var Color[][] */
    public $colors = [];

    public function decodePayload(){
        $this->mapId = $this->getActorUniqueId();
        $this->type = $this->getUnsignedVarInt();
        $this->dimensionId = $this->getByte();
        $this->isLocked = $this->getBool();
        $this->getSignedBlockPosition($this->originX, $this->originY, $this->originZ);

        if(($this->type & 0x08) !== 0){
            $count = $this->getUnsignedVarInt();
            for($i = 0; $i < $count; ++$i){
                $this->actorUniqueIds[] = $this->getActorUniqueId();
            }
        }

        if(($this->type & (0x08 | self::BITFLAG_DECORATION_UPDATE | self::BITFLAG_TEXTURE_UPDATE)) !== 0){ //Decoration bitflag or colour bitflag
            $this->scale = $this->getByte();
        }

        if(($this->type & self::BITFLAG_DECORATION_UPDATE) !== 0){
            for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
                $object = new MapTrackedObject();
                $object->type = $this->getLInt();
                if($object->type === MapTrackedObject::TYPE_BLOCK){
                    $this->getBlockPosition($object->x, $object->y, $object->z);
                }elseif($object->type === MapTrackedObject::TYPE_ACTOR){
                    $object->actorUniqueId = $this->getActorUniqueId();
                }else{
                    throw new \UnexpectedValueException("Unknown map object type $object->type");
                }
                $this->trackedObjects[] = $object;
            }

            for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
                $this->decorations[$i]["img"] = $this->getByte();
                $this->decorations[$i]["rot"] = $this->getByte();
                $this->decorations[$i]["xOffset"] = $this->getByte();
                $this->decorations[$i]["yOffset"] = $this->getByte();
                $this->decorations[$i]["label"] = $this->getString();

                $this->decorations[$i]["color"] = Color::fromABGR($this->getUnsignedVarInt());
            }
        }

        if(($this->type & self::BITFLAG_TEXTURE_UPDATE) !== 0){
            $this->width = $this->getVarInt();
            $this->height = $this->getVarInt();
            $this->xOffset = $this->getVarInt();
            $this->yOffset = $this->getVarInt();

            $count = $this->getUnsignedVarInt();
            if($count !== $this->width * $this->height){
                throw new \UnexpectedValueException("Expected colour count of " . ($this->height * $this->width) . " (height $this->height * width $this->width), got $count");
            }

            for($y = 0; $y < $this->height; ++$y){
                for($x = 0; $x < $this->width; ++$x){
                    $this->colors[$y][$x] = Color::fromABGR($this->getUnsignedVarInt());
                }
            }
        }
    }

    public function encodePayload(){
        $this->putActorUniqueId($this->mapId);

        $type = 0;
        if(($eidsCount = count($this->actorUniqueIds)) > 0){
            $type |= 0x08;
        }
        if(($decorationCount = count($this->decorations)) > 0){
            $type |= self::BITFLAG_DECORATION_UPDATE;
        }
        if(count($this->colors) > 0){
            $type |= self::BITFLAG_TEXTURE_UPDATE;
        }

        $this->putUnsignedVarInt($type);
        $this->putByte($this->dimensionId);
        $this->putBool($this->isLocked);
        $this->putSignedBlockPosition($this->originX, $this->originY, $this->originZ);

        if(($type & 0x08) !== 0){ //TODO: find out what these are for
            $this->putUnsignedVarInt($eidsCount);
            foreach($this->actorUniqueIds as $actorUniqueId){
                $this->putActorUniqueId($actorUniqueId);
            }
        }

        if(($type & (0x08 | self::BITFLAG_TEXTURE_UPDATE | self::BITFLAG_DECORATION_UPDATE)) !== 0){
            $this->putByte($this->scale);
        }

        if(($type & self::BITFLAG_DECORATION_UPDATE) !== 0){
            $this->putUnsignedVarInt(count($this->trackedObjects));
            foreach($this->trackedObjects as $object){
                $this->putLInt($object->type);
                if($object->type === MapTrackedObject::TYPE_BLOCK){
                    $this->putBlockPosition($object->x, $object->y, $object->z);
                }elseif($object->type === MapTrackedObject::TYPE_ACTOR){
                    $this->putActorUniqueId($object->actorUniqueId);
                }else{
                    throw new \InvalidArgumentException("Unknown map object type $object->type");
                }
            }

            $this->putUnsignedVarInt($decorationCount);
            foreach($this->decorations as $decoration){
                $this->putByte($decoration["img"]);
                $this->putByte($decoration["rot"]);
                $this->putByte($decoration["xOffset"]);
                $this->putByte($decoration["yOffset"]);
                $this->putString($decoration["label"]);

                assert($decoration["color"] instanceof Color);
                $this->putUnsignedVarInt($decoration["color"]->toABGR());
            }
        }

        if(($type & self::BITFLAG_TEXTURE_UPDATE) !== 0){
            $this->putVarInt($this->width);
            $this->putVarInt($this->height);
            $this->putVarInt($this->xOffset);
            $this->putVarInt($this->yOffset);

            $this->putUnsignedVarInt($this->width * $this->height); //list count, but we handle it as a 2D array... thanks for the confusion mojang

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


