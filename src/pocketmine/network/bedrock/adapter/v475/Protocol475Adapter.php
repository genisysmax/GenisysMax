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

namespace pocketmine\network\bedrock\adapter\v475;

use Closure;
use pocketmine\network\bedrock\adapter\v475\palette\BlockPalette as BlockPalette475;
use pocketmine\network\bedrock\adapter\v475\palette\ItemPalette as ItemPalette475;
use pocketmine\network\bedrock\adapter\v475\protocol\AddItemActorPacket as AddItemActorPacket475;
use pocketmine\network\bedrock\adapter\v475\protocol\AddPlayerPacket as AddPlayerPacket475;
use pocketmine\network\bedrock\adapter\v475\protocol\CraftingDataPacket as CraftingDataPacket475;
use pocketmine\network\bedrock\adapter\v475\protocol\CraftingEventPacket as CraftingEventPacket475;
use pocketmine\network\bedrock\adapter\v475\protocol\CreativeContentPacket as CreativeContentPacket475;
use pocketmine\network\bedrock\adapter\v475\protocol\InventoryContentPacket as InventoryContentPacket475;
use pocketmine\network\bedrock\adapter\v475\protocol\InventorySlotPacket as InventorySlotPacket475;
use pocketmine\network\bedrock\adapter\v475\protocol\InventoryTransactionPacket as InventoryTransactionPacket475;
use pocketmine\network\bedrock\adapter\v475\protocol\LevelChunkPacket as LevelChunkPacket475;
use pocketmine\network\bedrock\adapter\v475\protocol\LoginPacket as LoginPacket475;
use pocketmine\network\bedrock\adapter\v475\protocol\MobArmorEquipmentPacket as MobArmorEquipmentPacket475;
use pocketmine\network\bedrock\adapter\v475\protocol\MobEquipmentPacket as MobEquipmentPacket475;
use pocketmine\network\bedrock\adapter\v475\protocol\ProtocolInfo as ProtocolInfo475;
use pocketmine\network\bedrock\adapter\v486\Protocol486Adapter;
use pocketmine\network\bedrock\palette\block\BlockPalette;
use pocketmine\network\bedrock\palette\entry\BlockPaletteEntry;
use pocketmine\network\bedrock\palette\entry\ItemPaletteEntry;
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

class Protocol475Adapter extends Protocol486Adapter{
	public const PROTOCOL_VERSION = 475;

	protected const PACKET_MAP = [
		ProtocolInfo475::LOGIN_PACKET => LoginPacket475::class,
		ProtocolInfo475::LEVEL_CHUNK_PACKET => LevelChunkPacket475::class,
		ProtocolInfo475::INVENTORY_CONTENT_PACKET => InventoryContentPacket475::class,
		ProtocolInfo475::INVENTORY_SLOT_PACKET => InventorySlotPacket475::class,
		ProtocolInfo475::INVENTORY_TRANSACTION_PACKET => InventoryTransactionPacket475::class,
		ProtocolInfo475::ADD_ITEM_ACTOR_PACKET => AddItemActorPacket475::class,
		ProtocolInfo475::ADD_PLAYER_PACKET => AddPlayerPacket475::class,
		ProtocolInfo475::CRAFTING_DATA_PACKET => CraftingDataPacket475::class,
		ProtocolInfo475::CRAFTING_EVENT_PACKET => CraftingEventPacket475::class,
		ProtocolInfo475::CREATIVE_CONTENT_PACKET => CreativeContentPacket475::class,
		ProtocolInfo475::MOB_ARMOR_EQUIPMENT_PACKET => MobArmorEquipmentPacket475::class,
		ProtocolInfo475::MOB_EQUIPMENT_PACKET => MobEquipmentPacket475::class
	];

	protected const NEW_PACKETS = [
		ProtocolInfo::PLAYER_START_ITEM_COOLDOWN_PACKET => true,
		ProtocolInfo::SCRIPT_MESSAGE_PACKET => true,
		ProtocolInfo::CODE_BUILDER_SOURCE_PACKET => true
	];

    public function __construct(){
        Network::registerPalette(new BlockPaletteEntry(new BlockPalette475()));
        Network::registerPalette(new ItemPaletteEntry(new ItemPalette475()));

		$this->biomeDefinitions = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v475/biome_definitions.nbt");
	}

	public function processClientToServer(string $buf) : ?DataPacket{
		$offset = 0;
		$pid = Binary::readUnsignedVarInt($buf, $offset);

		if($pid === ProtocolInfo475::LOGIN_PACKET){
			return new LoginPacket475($buf);
		}

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

		return parent::processServerToClient($packet);
	}

	public function translateBlockId(int $runtimeId) : int{
		BlockPalette::getLegacyFromRuntimeId($runtimeId, $id, $meta);
		return BlockPalette475::getRuntimeFromLegacyId($id, $meta);
	}

	protected function processBlocks(DataPacket $packet, bool $clientToServer) : void{
		if($clientToServer){
			$c = function(int $runtimeId) : int{
				BlockPalette475::getLegacyFromRuntimeId($runtimeId, $id, $meta);
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

