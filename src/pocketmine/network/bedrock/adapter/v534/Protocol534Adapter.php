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

namespace pocketmine\network\bedrock\adapter\v534;

use Closure;
use pocketmine\network\bedrock\adapter\v534\palette\BlockPalette as BlockPalette534;
use pocketmine\network\bedrock\adapter\v534\protocol as v534;
use pocketmine\network\bedrock\adapter\v544\Protocol544Adapter;
use pocketmine\network\bedrock\palette\block\BlockPalette;
use pocketmine\network\bedrock\palette\entry\BlockPaletteEntry;
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

class Protocol534Adapter extends Protocol544Adapter{
	public const PROTOCOL_VERSION = 534;

	protected const PACKET_MAP = [
		v534\ProtocolInfo::ADD_ITEM_ACTOR_PACKET => v534\AddItemActorPacket::class,
		v534\ProtocolInfo::ADD_PLAYER_PACKET => v534\AddPlayerPacket::class,
		v534\ProtocolInfo::CLIENTBOUND_MAP_ITEM_DATA_PACKET => v534\ClientboundMapItemDataPacket::class,
		v534\ProtocolInfo::CRAFTING_DATA_PACKET => v534\CraftingDataPacket::class,
		v534\ProtocolInfo::CRAFTING_EVENT_PACKET => v534\CraftingEventPacket::class,
		v534\ProtocolInfo::CREATIVE_CONTENT_PACKET => v534\CreativeContentPacket::class,
		v534\ProtocolInfo::INVENTORY_CONTENT_PACKET => v534\InventoryContentPacket::class,
		v534\ProtocolInfo::INVENTORY_SLOT_PACKET => v534\InventorySlotPacket::class,
		v534\ProtocolInfo::INVENTORY_TRANSACTION_PACKET => v534\InventoryTransactionPacket::class,
		v534\ProtocolInfo::LOGIN_PACKET => v534\LoginPacket::class,
		v534\ProtocolInfo::MAP_INFO_REQUEST_PACKET => v534\MapInfoRequestPacket::class,
		v534\ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET => v534\MobArmorEquipmentPacket::class,
		v534\ProtocolInfo::MOB_EQUIPMENT_PACKET => v534\MobEquipmentPacket::class,
		v534\ProtocolInfo::MODAL_FORM_RESPONSE_PACKET => v534\ModalFormResponsePacket::class,
		v534\ProtocolInfo::NETWORK_CHUNK_PUBLISHER_UPDATE_PACKET => v534\NetworkChunkPublisherUpdatePacket::class,
		v534\ProtocolInfo::START_GAME_PACKET => v534\StartGamePacket::class,
		v534\ProtocolInfo::UPDATE_ATTRIBUTES_PACKET => v534\UpdateAttributesPacket::class,
	];

	protected const NEW_PACKETS = [
		ProtocolInfo::FEATURE_REGISTRY_PACKET => true,
	];

    public function __construct(){
        Network::registerPalette(new BlockPaletteEntry(new BlockPalette534()));

		$this->biomeDefinitions = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v534/biome_definitions.nbt");
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
		if($packet instanceof BiomeDefinitionListPacket){
			$packet->namedtag = $this->biomeDefinitions;
			return $packet;
		}

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
		return BlockPalette534::getRuntimeFromLegacyId($id, $meta);
	}

	protected function processBlocks(DataPacket $packet, bool $clientToServer) : void{
		if($clientToServer){
			$c = function(int $runtimeId) : int{
				BlockPalette534::getLegacyFromRuntimeId($runtimeId, $id, $meta);
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

