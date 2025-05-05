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

namespace pocketmine\network\bedrock\adapter\v448;

use Closure;
use pocketmine\network\bedrock\adapter\v448\palette\BlockPalette as BlockPalette448;
use pocketmine\network\bedrock\adapter\v448\palette\ItemPalette as ItemPalette448;
use pocketmine\network\bedrock\adapter\v448\protocol\ActorPickRequestPacket;
use pocketmine\network\bedrock\adapter\v448\protocol\AddPlayerPacket;
use pocketmine\network\bedrock\adapter\v448\protocol\CraftingDataPacket;
use pocketmine\network\bedrock\adapter\v448\protocol\CraftingEventPacket;
use pocketmine\network\bedrock\adapter\v448\protocol\CreativeContentPacket;
use pocketmine\network\bedrock\adapter\v448\protocol\HurtArmorPacket;
use pocketmine\network\bedrock\adapter\v448\protocol\InventoryContentPacket;
use pocketmine\network\bedrock\adapter\v448\protocol\InventorySlotPacket;
use pocketmine\network\bedrock\adapter\v448\protocol\InventoryTransactionPacket;
use pocketmine\network\bedrock\adapter\v448\protocol\LoginPacket;
use pocketmine\network\bedrock\adapter\v448\protocol\MobArmorEquipmentPacket;
use pocketmine\network\bedrock\adapter\v448\protocol\MobEquipmentPacket;
use pocketmine\network\bedrock\adapter\v448\protocol\PhotoTransferPacket;
use pocketmine\network\bedrock\adapter\v448\protocol\PlayerListPacket;
use pocketmine\network\bedrock\adapter\v448\protocol\PlayerSkinPacket;
use pocketmine\network\bedrock\adapter\v448\protocol\ProtocolInfo as ProtocolInfo448;
use pocketmine\network\bedrock\adapter\v448\protocol\StartGamePacket;
use pocketmine\network\bedrock\adapter\v465\protocol\ProtocolInfo as ProtocolInfo465;
use pocketmine\network\bedrock\adapter\v465\Protocol465Adapter;
use pocketmine\network\bedrock\palette\block\BlockPalette;
use pocketmine\network\bedrock\palette\entry\BlockPaletteEntry;
use pocketmine\network\bedrock\palette\entry\ItemPaletteEntry;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\LevelEventPacket;
use pocketmine\network\bedrock\protocol\LevelSoundEventPacket;
use pocketmine\network\bedrock\protocol\ProtocolInfo;
use pocketmine\network\bedrock\protocol\UpdateBlockPacket;
use pocketmine\network\Network;
use pocketmine\utils\Binary;

class Protocol448Adapter extends Protocol465Adapter {
    public const PROTOCOL_VERSION = 448;

    protected const NEW_PACKETS = [
        ProtocolInfo::EDU_URI_RESOURCE_PACKET => true,
        ProtocolInfo::CREATE_PHOTO_PACKET => true,
        ProtocolInfo::UPDATE_SUB_CHUNK_BLOCKS_PACKET => true,
		ProtocolInfo465::PHOTO_INFO_REQUEST_PACKET => true
    ];

    protected const PACKET_MAP = [
		ProtocolInfo448::ADD_PLAYER_PACKET => AddPlayerPacket::class,
        ProtocolInfo448::LOGIN_PACKET => LoginPacket::class,
        ProtocolInfo448::START_GAME_PACKET => StartGamePacket::class,
        ProtocolInfo448::ACTOR_PICK_REQUEST_PACKET => ActorPickRequestPacket::class,
        ProtocolInfo448::CRAFTING_DATA_PACKET => CraftingDataPacket::class,
        ProtocolInfo448::HURT_ARMOR_PACKET => HurtArmorPacket::class,
        ProtocolInfo448::PHOTO_TRANSFER_PACKET => PhotoTransferPacket::class,
        ProtocolInfo448::PLAYER_LIST_PACKET => PlayerListPacket::class,
        ProtocolInfo448::PLAYER_SKIN_PACKET => PlayerSkinPacket::class,
        ProtocolInfo448::CRAFTING_EVENT_PACKET => CraftingEventPacket::class,
        ProtocolInfo448::CREATIVE_CONTENT_PACKET => CreativeContentPacket::class,
        ProtocolInfo448::INVENTORY_CONTENT_PACKET => InventoryContentPacket::class,
        ProtocolInfo448::INVENTORY_SLOT_PACKET => InventorySlotPacket::class,
        ProtocolInfo448::INVENTORY_TRANSACTION_PACKET => InventoryTransactionPacket::class,
        ProtocolInfo448::MOB_ARMOR_EQUIPMENT_PACKET => MobArmorEquipmentPacket::class,
        ProtocolInfo448::MOB_EQUIPMENT_PACKET => MobEquipmentPacket::class,
    ];

    public function __construct(){
        Network::registerPalette(new BlockPaletteEntry(new BlockPalette448()));
        Network::registerPalette(new ItemPaletteEntry(new ItemPalette448()));
    }

    public function processClientToServer(string $buf) : ?DataPacket{
        $offset = 0;
        $pid = Binary::readUnsignedVarInt($buf, $offset);

        if(isset(self::PACKET_MAP[$pid])){
            $class = self::PACKET_MAP[$pid];

            return new $class($buf);
        }
        return parent::processClientToServer($buf);
    }

    public function processServerToClient(DataPacket $packet) : ?DataPacket{
        $pid = $packet->pid();

        if(isset(self::NEW_PACKETS[$pid])){
            return null;
        }

        if(isset(self::PACKET_MAP[$pid])){
            $class = self::PACKET_MAP[$pid];

            $pk = new $class();
            foreach($packet as $k => $v){
                if($k === "isEncoded" or $k === "wasDecoded" or $k === "buffer" or $k === "offset"){
                    continue;
                }

                if(property_exists($pk, $k)){
                    $pk->{$k} = $v;
                }
            }

            return $pk;
        }

        return parent::processServerToClient($packet);
    }

    public function translateBlockId(int $runtimeId) : int{
        BlockPalette::getLegacyFromRuntimeId($runtimeId, $id, $meta);

        return BlockPalette448::getRuntimeFromLegacyId($id, $meta);
    }

    protected function processBlocks(DataPacket $packet, bool $clientToServer) : void{
        if($clientToServer){
            $c = function(int $runtimeId) : int{
                BlockPalette448::getLegacyFromRuntimeId($runtimeId, $id, $meta);
                return BlockPalette::getRuntimeFromLegacyId($id, $meta);
            };

            if(!$packet->wasDecoded){
                $packet->decode();
            }
        }else{
            $c = Closure::fromCallable([$this, "translateBlockId"]);
        }

        if($packet instanceof LevelSoundEventPacket){
            if($packet->sound === LevelSoundEventPacket::SOUND_HIT or $packet->sound === LevelSoundEventPacket::SOUND_PLACE){
                $packet->extraData = $c($packet->extraData);
            }
        }elseif($packet instanceof UpdateBlockPacket){
            $packet->blockRuntimeId = $c($packet->blockRuntimeId);
        }elseif($packet instanceof LevelEventPacket){
            if($packet->evid === LevelEventPacket::EVENT_PARTICLE_PUNCH_BLOCK){
                $face = $packet->data >> 24;
                $packet->data = $c($packet->data & 0xffffff) | ($face << 24);
            }elseif($packet->evid === LevelEventPacket::EVENT_PARTICLE_DESTROY){
                $packet->data = $c($packet->data);
            }
        }
    }

    public function getChunkProtocol() : int{
        return self::PROTOCOL_VERSION;
    }

    public function getProtocolVersion() : int{
        return self::PROTOCOL_VERSION;
    }
}

