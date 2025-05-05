<?php

namespace pocketmine\network\bedrock\adapter\v440;

use Closure;
use pocketmine\network\bedrock\adapter\mapper\CommandArgTypeMapper;
use pocketmine\network\bedrock\adapter\v440\palette\BlockPalette as BlockPalette440;
use pocketmine\network\bedrock\adapter\v440\palette\ItemPalette as ItemPalette440;
use pocketmine\network\bedrock\adapter\v440\protocol\AddItemActorPacket as AddItemActorPacket440;
use pocketmine\network\bedrock\adapter\v440\protocol\AddPlayerPacket as AddPlayerPacket440;
use pocketmine\network\bedrock\adapter\v440\protocol\AvailableCommandsPacket as AvailableCommandsPacket440;
use pocketmine\network\bedrock\adapter\v440\protocol\CraftingDataPacket as CraftingDataPacket440;
use pocketmine\network\bedrock\adapter\v440\protocol\CraftingEventPacket as CraftingEventPacket440;
use pocketmine\network\bedrock\adapter\v440\protocol\CreativeContentPacket as CreativeContentPacket440;
use pocketmine\network\bedrock\adapter\v440\protocol\InventoryContentPacket as InventoryContentPacket440;
use pocketmine\network\bedrock\adapter\v440\protocol\InventorySlotPacket as InventorySlotPacket440;
use pocketmine\network\bedrock\adapter\v440\protocol\InventoryTransactionPacket as InventoryTransactionPacket440;
use pocketmine\network\bedrock\adapter\v440\protocol\LoginPacket as LoginPacket440;
use pocketmine\network\bedrock\adapter\v440\protocol\MobArmorEquipmentPacket as MobArmorEquipmentPacket440;
use pocketmine\network\bedrock\adapter\v440\protocol\MobEquipmentPacket as MobEquipmentPacket440;
use pocketmine\network\bedrock\adapter\v440\protocol\NpcRequestPacket as NpcRequestPacket440;
use pocketmine\network\bedrock\adapter\v440\protocol\ProtocolInfo as ProtocolInfo440;
use pocketmine\network\bedrock\adapter\v440\protocol\ResourcePacksInfoPacket as ResourcePacksInfoPacket440;
use pocketmine\network\bedrock\adapter\v440\protocol\SetTitlePacket as SetTitlePacket440;
use pocketmine\network\bedrock\adapter\v440\protocol\StartGamePacket as StartGamePacket440;
use pocketmine\network\bedrock\adapter\v440\protocol\types\LevelEventParticleIds as LevelEventParticleIds440;
use pocketmine\network\bedrock\adapter\v448\Protocol448Adapter;
use pocketmine\network\bedrock\palette\block\BlockPalette;
use pocketmine\network\bedrock\palette\entry\BlockPaletteEntry;
use pocketmine\network\bedrock\palette\entry\ItemPaletteEntry;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\LevelEventPacket;
use pocketmine\network\bedrock\protocol\LevelSoundEventPacket;
use pocketmine\network\bedrock\protocol\ProtocolInfo;
use pocketmine\network\bedrock\protocol\types\LevelEventParticleIds as NewLevelEventParticleIds;
use pocketmine\network\bedrock\protocol\UpdateBlockPacket;
use pocketmine\network\Network;
use pocketmine\utils\Binary;

class Protocol440Adapter extends Protocol448Adapter {
    public const PROTOCOL_VERSION = 440;

    protected const NEW_PACKETS = [
        ProtocolInfo::SIMULATION_TYPE_PACKET => true,
        ProtocolInfo::NPC_DIALOGUE_PACKET => true,
    ];

    protected const PACKET_MAP = [
        ProtocolInfo440::LOGIN_PACKET => LoginPacket440::class,
        ProtocolInfo440::NPC_REQUEST_PACKET => NpcRequestPacket440::class,
        ProtocolInfo440::RESOURCE_PACKS_INFO_PACKET => ResourcePacksInfoPacket440::class,
        ProtocolInfo440::SET_TITLE_PACKET => SetTitlePacket440::class,
        ProtocolInfo440::START_GAME_PACKET => StartGamePacket440::class,
        ProtocolInfo440::INVENTORY_CONTENT_PACKET => InventoryContentPacket440::class,
        ProtocolInfo440::INVENTORY_SLOT_PACKET => InventorySlotPacket440::class,
        ProtocolInfo440::INVENTORY_TRANSACTION_PACKET => InventoryTransactionPacket440::class,
        ProtocolInfo440::ADD_ITEM_ACTOR_PACKET => AddItemActorPacket440::class,
        ProtocolInfo440::ADD_PLAYER_PACKET => AddPlayerPacket440::class,
        ProtocolInfo440::CRAFTING_DATA_PACKET => CraftingDataPacket440::class,
        ProtocolInfo440::CRAFTING_EVENT_PACKET => CraftingEventPacket440::class,
        ProtocolInfo440::CREATIVE_CONTENT_PACKET => CreativeContentPacket440::class,
        ProtocolInfo440::MOB_ARMOR_EQUIPMENT_PACKET => MobArmorEquipmentPacket440::class,
        ProtocolInfo440::MOB_EQUIPMENT_PACKET => MobEquipmentPacket440::class
    ];

    public function __construct(){
        Network::registerPalette(new BlockPaletteEntry(new BlockPalette440()));
        Network::registerPalette(new ItemPaletteEntry(new ItemPalette440()));
    }

	public function initArgTypeMapper() : void{
		$this->argTypeMapper = new CommandArgTypeMapper(AvailableCommandsPacket440::class);
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

        return BlockPalette440::getRuntimeFromLegacyId($id, $meta);
    }

    protected function processBlocks(DataPacket $packet, bool $clientToServer) : void{
        if($clientToServer){
            $c = function(int $runtimeId) : int{
                BlockPalette440::getLegacyFromRuntimeId($runtimeId, $id, $meta);
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

    public function translateParticleId(int $particleId) : ?int {
        if($particleId < NewLevelEventParticleIds::CANDLE_FLAME) {
            return $particleId;
        }else if($particleId === NewLevelEventParticleIds::CANDLE_FLAME){
            return null;
        } else if ($particleId - 1 > LevelEventParticleIds440::ELECTRIC_SPARK){
            return null;
        }

        return $particleId - 1;
    }

    public function getChunkProtocol() : int{
        return self::PROTOCOL_VERSION;
    }

    public function getProtocolVersion() : int{
        return self::PROTOCOL_VERSION;
    }
}