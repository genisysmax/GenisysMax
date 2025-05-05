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

namespace pocketmine\network\bedrock\adapter\v431;

use Closure;
use pocketmine\network\bedrock\adapter\v431\palette\BlockPalette as BlockPalette431;
use pocketmine\network\bedrock\adapter\v431\palette\ItemPalette as ItemPalette431;
use pocketmine\network\bedrock\adapter\v431\protocol\AddItemActorPacket as AddItemActorPacket431;
use pocketmine\network\bedrock\adapter\v431\protocol\AddPlayerPacket as AddPlayerPacket431;
use pocketmine\network\bedrock\adapter\v431\protocol\CraftingDataPacket as CraftingDataPacket431;
use pocketmine\network\bedrock\adapter\v431\protocol\CraftingEventPacket as CraftingEventPacket431;
use pocketmine\network\bedrock\adapter\v431\protocol\CreativeContentPacket as CreativeContentPacket431;
use pocketmine\network\bedrock\adapter\v431\protocol\GameRulesChangedPacket as GameRulesChangedPacket431;
use pocketmine\network\bedrock\adapter\v431\protocol\InventoryContentPacket as InventoryContentPacket431;
use pocketmine\network\bedrock\adapter\v431\protocol\InventorySlotPacket as InventorySlotPacket431;
use pocketmine\network\bedrock\adapter\v431\protocol\InventoryTransactionPacket as InventoryTransactionPacket431;
use pocketmine\network\bedrock\adapter\v431\protocol\LoginPacket as LoginPacket431;
use pocketmine\network\bedrock\adapter\v431\protocol\MobArmorEquipmentPacket as MobArmorEquipmentPacket431;
use pocketmine\network\bedrock\adapter\v431\protocol\MobEquipmentPacket as MobEquipmentPacket431;
use pocketmine\network\bedrock\adapter\v431\protocol\ProtocolInfo as ProtocolInfo431;
use pocketmine\network\bedrock\adapter\v431\protocol\StartGamePacket as StartGamePacket431;
use pocketmine\network\bedrock\adapter\v440\Protocol440Adapter;
use pocketmine\network\bedrock\palette\block\BlockPalette;
use pocketmine\network\bedrock\palette\entry\BlockPaletteEntry;
use pocketmine\network\bedrock\palette\entry\ItemPaletteEntry;
use pocketmine\network\bedrock\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\LevelEventPacket;
use pocketmine\network\bedrock\protocol\LevelSoundEventPacket;
use pocketmine\network\bedrock\protocol\ProtocolInfo;
use pocketmine\network\bedrock\protocol\UpdateBlockPacket;
use pocketmine\network\Network;
use pocketmine\utils\Binary;

class Protocol431Adapter extends Protocol440Adapter {
    public const PROTOCOL_VERSION = 431;

    protected const NEW_PACKETS = [
        ProtocolInfo::SYNC_ACTOR_PROPERTY_PACKET => true,
        ProtocolInfo::ADD_VOLUME_ENTITY_PACKET => true,
        ProtocolInfo::REMOVE_VOLUME_ENTITY_PACKET => true,
    ];

    protected const PACKET_MAP = [
        ProtocolInfo431::START_GAME_PACKET => StartGamePacket431::class,
        ProtocolInfo431::GAME_RULES_CHANGED_PACKET => GameRulesChangedPacket431::class,
        ProtocolInfo431::LOGIN_PACKET => LoginPacket431::class,
        ProtocolInfo431::ADD_ITEM_ACTOR_PACKET => AddItemActorPacket431::class,
        ProtocolInfo431::ADD_PLAYER_PACKET => AddPlayerPacket431::class,
        ProtocolInfo431::CRAFTING_DATA_PACKET => CraftingDataPacket431::class,
        ProtocolInfo431::CRAFTING_EVENT_PACKET => CraftingEventPacket431::class,
        ProtocolInfo431::CREATIVE_CONTENT_PACKET => CreativeContentPacket431::class,
        ProtocolInfo431::INVENTORY_CONTENT_PACKET => InventoryContentPacket431::class,
        ProtocolInfo431::INVENTORY_SLOT_PACKET => InventorySlotPacket431::class,
        ProtocolInfo431::MOB_ARMOR_EQUIPMENT_PACKET => MobArmorEquipmentPacket431::class,
        ProtocolInfo431::MOB_EQUIPMENT_PACKET => MobEquipmentPacket431::class,
        ProtocolInfo431::INVENTORY_TRANSACTION_PACKET => InventoryTransactionPacket431::class,
    ];

    public function __construct(){
        Network::registerPalette(new BlockPaletteEntry(new BlockPalette431()));
        Network::registerPalette(new ItemPaletteEntry(new ItemPalette431()));

        $this->actorIdentifiers = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v431/entity_identifiers.nbt");
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

            if($packet instanceof AvailableActorIdentifiersPacket){
                $packet->namedtag = $this->actorIdentifiers;
                return $packet;
            }

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

        return BlockPalette431::getRuntimeFromLegacyId($id, $meta);
    }

    protected function processBlocks(DataPacket $packet, bool $clientToServer) : void{
        if($clientToServer){
            $c = function(int $runtimeId) : int{
                BlockPalette431::getLegacyFromRuntimeId($runtimeId, $id, $meta);
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

