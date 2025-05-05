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



namespace pocketmine\network\bedrock\adapter\v465;

use Closure;
use pocketmine\network\bedrock\adapter\v465\palette\BlockPalette as BlockPalette465;
use pocketmine\network\bedrock\adapter\v465\palette\ItemPalette as ItemPalette465;
use pocketmine\network\bedrock\adapter\v465\protocol\AddItemActorPacket;
use pocketmine\network\bedrock\adapter\v465\protocol\AddPlayerPacket;
use pocketmine\network\bedrock\adapter\v465\protocol\CraftingDataPacket;
use pocketmine\network\bedrock\adapter\v465\protocol\CraftingEventPacket;
use pocketmine\network\bedrock\adapter\v465\protocol\CreativeContentPacket;
use pocketmine\network\bedrock\adapter\v465\protocol\InventoryContentPacket;
use pocketmine\network\bedrock\adapter\v465\protocol\InventorySlotPacket;
use pocketmine\network\bedrock\adapter\v465\protocol\InventoryTransactionPacket;
use pocketmine\network\bedrock\adapter\v465\protocol\LoginPacket;
use pocketmine\network\bedrock\adapter\v465\protocol\MobArmorEquipmentPacket;
use pocketmine\network\bedrock\adapter\v465\protocol\MobEquipmentPacket;
use pocketmine\network\bedrock\adapter\v465\protocol\ProtocolInfo as ProtocolInfo465;
use pocketmine\network\bedrock\adapter\v465\protocol\StartGamePacket;
use pocketmine\network\bedrock\adapter\v471\Protocol471Adapter;
use pocketmine\network\bedrock\palette\block\BlockPalette;
use pocketmine\network\bedrock\palette\entry\BlockPaletteEntry;
use pocketmine\network\bedrock\palette\entry\ItemPaletteEntry;
use pocketmine\network\bedrock\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\bedrock\protocol\BiomeDefinitionListPacket;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\LevelEventPacket;
use pocketmine\network\bedrock\protocol\LevelSoundEventPacket;
use pocketmine\network\bedrock\protocol\ProtocolInfo;
use pocketmine\network\bedrock\protocol\UpdateBlockPacket;
use pocketmine\network\Network;
use pocketmine\utils\Binary;

class Protocol465Adapter extends Protocol471Adapter{
    public const PROTOCOL_VERSION = 465;

    protected const NEW_PACKETS = [
        ProtocolInfo::SUB_CHUNK_PACKET => true,
        ProtocolInfo::SUB_CHUNK_REQUEST_PACKET => true,
    ];

    protected const PACKET_MAP = [
        ProtocolInfo465::LOGIN_PACKET => LoginPacket::class,
        ProtocolInfo465::ADD_ITEM_ACTOR_PACKET => AddItemActorPacket::class,
        ProtocolInfo465::ADD_PLAYER_PACKET => AddPlayerPacket::class,
        ProtocolInfo465::START_GAME_PACKET => StartGamePacket::class,
        ProtocolInfo465::CRAFTING_DATA_PACKET => CraftingDataPacket::class,
        ProtocolInfo465::CRAFTING_EVENT_PACKET => CraftingEventPacket::class,
        ProtocolInfo465::CREATIVE_CONTENT_PACKET => CreativeContentPacket::class,
        ProtocolInfo465::INVENTORY_CONTENT_PACKET => InventoryContentPacket::class,
        ProtocolInfo465::INVENTORY_SLOT_PACKET => InventorySlotPacket::class,
        ProtocolInfo465::INVENTORY_TRANSACTION_PACKET => InventoryTransactionPacket::class,
        ProtocolInfo465::MOB_ARMOR_EQUIPMENT_PACKET => MobArmorEquipmentPacket::class,
        ProtocolInfo465::MOB_EQUIPMENT_PACKET => MobEquipmentPacket::class,
    ];

    public function __construct(){
        Network::registerPalette(new BlockPaletteEntry(new BlockPalette465()));
        Network::registerPalette(new ItemPaletteEntry(new ItemPalette465()));

        $this->biomeDefinitions = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v465/biome_definitions.nbt");
        $this->actorIdentifiers = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v465/entity_identifiers.nbt");
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

    public function processServerToClient(DataPacket $packet): ?DataPacket {
        $pid = $packet->pid();

        if(isset(self::NEW_PACKETS[$pid])){
            return null;
        }

        if($packet instanceof AvailableActorIdentifiersPacket){
            $packet->namedtag = $this->actorIdentifiers;
            return $packet;
        }

        if($packet instanceof BiomeDefinitionListPacket){
            $packet->namedtag = $this->biomeDefinitions;
            return $packet;
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

        $packet = parent::processServerToClient($packet);

        if($packet instanceof LevelEventPacket){
            $isLegacyParticle = ($packet->evid & LevelEventPacket::EVENT_ADD_PARTICLE_LEGACY_MASK) > 0;
            $isGenericParticle = $packet->evid === LevelEventPacket::EVENT_PARTICLE_GENERIC_SPAWN;

            $particleId = null;
            if($isLegacyParticle){
                $particleId = $packet->evid & 0x3fff;
            }else if($isGenericParticle) {
                $particleId = $packet->data;
            }

            if($particleId !== null) {
                $particleId = $this->translateParticleId($particleId);

                if ($particleId === null) { // client unsupported particle
                    return null;
                }

                if ($isLegacyParticle) {
                    $packet->evid = LevelEventPacket::EVENT_ADD_PARTICLE_LEGACY_MASK | $particleId;
                } else {
                    $packet->data = $particleId;
                }
            }
        }

        return $packet;
    }

    public function translateParticleId(int $particleId) : ?int {
        return $particleId;
    }

    public function translateBlockId(int $runtimeId) : int{
        BlockPalette::getLegacyFromRuntimeId($runtimeId, $id, $meta);

        return BlockPalette465::getRuntimeFromLegacyId($id, $meta);
    }

    protected function processBlocks(DataPacket $packet, bool $clientToServer) : void{
        if($clientToServer){
            $c = function(int $runtimeId) : int{
                BlockPalette465::getLegacyFromRuntimeId($runtimeId, $id, $meta);
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

    public function getChunkProtocol(): int {
        return self::PROTOCOL_VERSION;
    }

    public function getProtocolVersion(): int {
        return self::PROTOCOL_VERSION;
    }
}

