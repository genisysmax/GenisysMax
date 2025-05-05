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

namespace pocketmine\network\bedrock\adapter\v390;

use Closure;
use pocketmine\item\Item;
use pocketmine\network\bedrock\adapter\v390\palette\BlockPalette as BlockPalette390;
use pocketmine\network\bedrock\adapter\v390\palette\ItemPalette as ItemPalette390;
use pocketmine\network\bedrock\adapter\v390\protocol as v390;
use pocketmine\network\bedrock\adapter\v407\Protocol407Adapter;
use pocketmine\network\bedrock\palette\block\BlockPalette;
use pocketmine\network\bedrock\palette\entry\BlockPaletteEntry;
use pocketmine\network\bedrock\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\bedrock\protocol\BiomeDefinitionListPacket;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\LevelEventPacket;
use pocketmine\network\bedrock\protocol\LevelSoundEventPacket;
use pocketmine\network\bedrock\protocol\ProtocolInfo;
use pocketmine\network\bedrock\protocol\UpdateBlockPacket;
use pocketmine\network\Network;
use pocketmine\utils\Binary;
use function file_get_contents;
use function property_exists;

class Protocol390Adapter extends Protocol407Adapter{

	public const PROTOCOL_VERSION = 390; // 1.14.60

    protected const NEW_PACKETS = [
        ProtocolInfo::CREATIVE_CONTENT_PACKET => true,
        ProtocolInfo::PLAYER_ENCHANT_OPTIONS_PACKET => true,
        ProtocolInfo::ITEM_STACK_REQUEST_PACKET => true,
        ProtocolInfo::ITEM_STACK_RESPONSE_PACKET => true,
        ProtocolInfo::PLAYER_ARMOR_DAMAGE_PACKET => true,
        ProtocolInfo::CODE_BUILDER_PACKET => true,
        ProtocolInfo::UPDATE_PLAYER_GAME_TYPE_PACKET => true,
        ProtocolInfo::EMOTE_LIST_PACKET => true,
        ProtocolInfo::DEBUG_INFO_PACKET => true,
        ProtocolInfo::PACKET_VIOLATION_WARNING_PACKET => true,
    ];

	protected const OLD_PACKETS = [
		v390\ProtocolInfo::ACTOR_FALL_PACKET => true,
		v390\ProtocolInfo::UPDATE_BLOCK_PROPERTIES_PACKET => true
	];

	protected const PACKET_MAP = [
		v390\ProtocolInfo::ADD_ITEM_ACTOR_PACKET => v390\AddItemActorPacket::class,
		v390\ProtocolInfo::ADD_PLAYER_PACKET => v390\AddPlayerPacket::class,
		v390\ProtocolInfo::CONTAINER_CLOSE_PACKET => v390\ContainerClosePacket::class,
		v390\ProtocolInfo::CRAFTING_DATA_PACKET => v390\CraftingDataPacket::class,
		v390\ProtocolInfo::CRAFTING_EVENT_PACKET => v390\CraftingEventPacket::class,
        v390\ProtocolInfo::HURT_ARMOR_PACKET => v390\HurtArmorPacket::class,
        v390\ProtocolInfo::INVENTORY_CONTENT_PACKET => v390\InventoryContentPacket::class,
        v390\ProtocolInfo::INVENTORY_SLOT_PACKET => v390\InventorySlotPacket::class,
		v390\ProtocolInfo::INVENTORY_TRANSACTION_PACKET => v390\InventoryTransactionPacket::class,
		v390\ProtocolInfo::LOGIN_PACKET => v390\LoginPacket::class,
		v390\ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET => v390\MobArmorEquipmentPacket::class,
		v390\ProtocolInfo::MOB_EQUIPMENT_PACKET => v390\MobEquipmentPacket::class,
		v390\ProtocolInfo::SET_ACTOR_DATA_PACKET => v390\SetActorDataPacket::class,
        v390\ProtocolInfo::SET_SPAWN_POSITION_PACKET => v390\SetSpawnPositionPacket::class,
        v390\ProtocolInfo::SET_ACTOR_LINK_PACKET => v390\SetActorLinkPacket::class,
		v390\ProtocolInfo::START_GAME_PACKET => v390\StartGamePacket::class,
        v390\ProtocolInfo::TEXT_PACKET => v390\TextPacket::class,
	];

    public function __construct(){
        Network::registerPalette(new BlockPaletteEntry(new BlockPalette390()));
		ItemPalette390::lazyInit();

		$this->actorIdentifiers = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v390/entity_identifiers.nbt");
        $this->biomeDefinitions = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v390/biome_definitions.nbt");
	}

    public function getCreativeItems() : array{
        $creativeItemsJson = json_decode(file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v390/creativeitems.json"), true);
        $creativeItems = [];
        foreach($creativeItemsJson as $data){
            $item = Item::jsonDeserialize($data);
            if ($item->getName() === "Unknown") {
                continue;
            }
            $creativeItems[] = clone $item;
        }
        return $creativeItems;
    }

	public function processClientToServer(string $buf) : ?DataPacket{
		$offset = 0;
		$pid = Binary::readUnsignedVarInt($buf, $offset);
		if(isset(self::OLD_PACKETS[$pid])){
			return null;
		}

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
		return BlockPalette390::getRuntimeFromLegacyId($id, $meta);
	}

	protected function processBlocks(DataPacket $packet, bool $clientToServer) : void{
		if($clientToServer){
			$c = function(int $runtimeId) : int{
				BlockPalette390::getLegacyFromRuntimeId($runtimeId, $id, $meta);
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

