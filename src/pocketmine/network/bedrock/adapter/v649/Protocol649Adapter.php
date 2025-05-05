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

namespace pocketmine\network\bedrock\adapter\v649;

use Closure;
use pocketmine\network\bedrock\adapter\mapper\CommandArgTypeMapper;
use pocketmine\network\bedrock\adapter\v649\palette\BlockPalette as BlockPalette649;
use pocketmine\network\bedrock\adapter\v649\palette\ItemPalette as ItemPalette649;
use pocketmine\network\bedrock\adapter\v649\protocol as v649;
use pocketmine\network\bedrock\adapter\v662\Protocol662Adapter;
use pocketmine\network\bedrock\palette\block\BlockPalette;
use pocketmine\network\bedrock\palette\entry\BlockPaletteEntry;
use pocketmine\network\bedrock\palette\entry\ItemPaletteEntry;
use pocketmine\network\bedrock\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\bedrock\protocol\AvailableCommandsPacket;
use pocketmine\network\bedrock\protocol\BiomeDefinitionListPacket;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\LevelEventPacket;
use pocketmine\network\bedrock\protocol\LevelSoundEventPacket;
use pocketmine\network\bedrock\protocol\UpdateBlockPacket;
use pocketmine\network\Network;
use pocketmine\utils\Binary;
use function property_exists;

class Protocol649Adapter extends Protocol662Adapter {
	public const PROTOCOL_VERSION = 649;

	protected const PACKET_MAP = [
        v649\ProtocolInfo::ADD_ACTOR_PACKET => v649\AddActorPacket::class,
        v649\ProtocolInfo::ADD_ITEM_ACTOR_PACKET => v649\AddItemActorPacket::class,
        v649\ProtocolInfo::CRAFTING_EVENT_PACKET => v649\CraftingEventPacket::class,
        v649\ProtocolInfo::CREATIVE_CONTENT_PACKET => v649\CreativeContentPacket::class,
        v649\ProtocolInfo::CRAFTING_DATA_PACKET => v649\CraftingDataPacket::class,
        v649\ProtocolInfo::INVENTORY_TRANSACTION_PACKET => v649\InventoryTransactionPacket::class,
        v649\ProtocolInfo::INVENTORY_SLOT_PACKET => v649\InventorySlotPacket::class,
        v649\ProtocolInfo::INVENTORY_CONTENT_PACKET => v649\InventoryContentPacket::class,
        v649\ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET => v649\MobArmorEquipmentPacket::class,
        v649\ProtocolInfo::MOB_EQUIPMENT_PACKET => v649\MobEquipmentPacket::class,
        v649\ProtocolInfo::START_GAME_PACKET => v649\StartGamePacket::class,

		v649\ProtocolInfo::LOGIN_PACKET => v649\LoginPacket::class,
        v649\ProtocolInfo::MOB_EFFECT_PACKET => v649\MobEffectPacket::class,
        v649\ProtocolInfo::PLAYER_AUTH_INPUT_PACKET => v649\PlayerAuthInputPacket::class,
        v649\ProtocolInfo::RESOURCE_PACKS_INFO_PACKET => v649\ResourcePacksInfoPacket::class,
        v649\ProtocolInfo::SET_ACTOR_MOTION_PACKET => v649\SetActorMotionPacket::class,
	];

    public function __construct(){
        Network::registerPalette(new BlockPaletteEntry(new BlockPalette649()));
        Network::registerPalette(new ItemPaletteEntry(new ItemPalette649()));

        $this->actorIdentifiers = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v649/entity_identifiers.nbt");
        $this->biomeDefinitions = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v649/biome_definitions.nbt");
    }

    public function initArgTypeMapper() : void{
        $this->argTypeMapper = new CommandArgTypeMapper(v649\AvailableCommandsPacket::class);
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

        $pk = parent::processServerToClient($packet);
        if($pk instanceof AvailableCommandsPacket){
            $pk = $this->argTypeMapper->map($pk);
        }
        return $pk;
	}

	public function translateBlockId(int $runtimeId) : int{
		BlockPalette::getLegacyFromRuntimeId($runtimeId, $id, $meta);
		return BlockPalette649::getRuntimeFromLegacyId($id, $meta);
	}

	protected function processBlocks(DataPacket $packet, bool $clientToServer) : void{
		if($clientToServer){
			$c = function(int $runtimeId) : int{
				BlockPalette649::getLegacyFromRuntimeId($runtimeId, $id, $meta);
				return BlockPalette::getRuntimeFromLegacyId($id, $meta);
			};

			if(!$packet->wasDecoded){
				$packet->decode();
			}
		}else{
			$c = Closure::fromCallable([$this, 'translateBlockId']);
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

