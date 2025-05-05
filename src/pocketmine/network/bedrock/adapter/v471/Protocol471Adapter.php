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

namespace pocketmine\network\bedrock\adapter\v471;

use pocketmine\network\bedrock\adapter\v471\palette\ItemPalette as ItemPalette471;
use pocketmine\network\bedrock\adapter\v471\protocol\AddItemActorPacket as AddItemActorPacket471;
use pocketmine\network\bedrock\adapter\v471\protocol\AddPlayerPacket as AddPlayerPacket471;
use pocketmine\network\bedrock\adapter\v471\protocol\CraftingDataPacket as CraftingDataPacket471;
use pocketmine\network\bedrock\adapter\v471\protocol\CraftingEventPacket as CraftingEventPacket471;
use pocketmine\network\bedrock\adapter\v471\protocol\CreativeContentPacket as CreativeContentPacket471;
use pocketmine\network\bedrock\adapter\v471\protocol\InventoryContentPacket as InventoryContentPacket471;
use pocketmine\network\bedrock\adapter\v471\protocol\InventorySlotPacket as InventorySlotPacket471;
use pocketmine\network\bedrock\adapter\v471\protocol\InventoryTransactionPacket as InventoryTransactionPacket471;
use pocketmine\network\bedrock\adapter\v471\protocol\LoginPacket as LoginPacket471;
use pocketmine\network\bedrock\adapter\v471\protocol\MobArmorEquipmentPacket as MobArmorEquipmentPacket471;
use pocketmine\network\bedrock\adapter\v471\protocol\MobEquipmentPacket as MobEquipmentPacket471;
use pocketmine\network\bedrock\adapter\v471\protocol\ProtocolInfo as ProtocolInfo471;
use pocketmine\network\bedrock\adapter\v471\protocol\StartGamePacket as StartGamePacket471;
use pocketmine\network\bedrock\adapter\v475\Protocol475Adapter;
use pocketmine\network\bedrock\palette\entry\ItemPaletteEntry;
use pocketmine\network\bedrock\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\bedrock\protocol\BiomeDefinitionListPacket;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\Network;
use pocketmine\utils\Binary;
use function file_get_contents;
use function property_exists;

class Protocol471Adapter extends Protocol475Adapter{
	public const PROTOCOL_VERSION = 471;

	protected const PACKET_MAP = [
		ProtocolInfo471::LOGIN_PACKET => LoginPacket471::class,
		ProtocolInfo471::START_GAME_PACKET => StartGamePacket471::class,
		ProtocolInfo471::INVENTORY_CONTENT_PACKET => InventoryContentPacket471::class,
		ProtocolInfo471::INVENTORY_SLOT_PACKET => InventorySlotPacket471::class,
		ProtocolInfo471::INVENTORY_TRANSACTION_PACKET => InventoryTransactionPacket471::class,
		ProtocolInfo471::ADD_ITEM_ACTOR_PACKET => AddItemActorPacket471::class,
		ProtocolInfo471::ADD_PLAYER_PACKET => AddPlayerPacket471::class,
		ProtocolInfo471::CRAFTING_DATA_PACKET => CraftingDataPacket471::class,
		ProtocolInfo471::CRAFTING_EVENT_PACKET => CraftingEventPacket471::class,
		ProtocolInfo471::CREATIVE_CONTENT_PACKET => CreativeContentPacket471::class,
		ProtocolInfo471::MOB_ARMOR_EQUIPMENT_PACKET => MobArmorEquipmentPacket471::class,
		ProtocolInfo471::MOB_EQUIPMENT_PACKET => MobEquipmentPacket471::class
	];

    public function __construct(){
        Network::registerPalette(new ItemPaletteEntry(new ItemPalette471()));

		$this->actorIdentifiers = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v471/entity_identifiers.nbt");
		$this->biomeDefinitions = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v471/biome_definitions.nbt");
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
		if($packet instanceof AvailableActorIdentifiersPacket){
			$packet->namedtag = $this->actorIdentifiers;
			return $packet;
		}

		if($packet instanceof BiomeDefinitionListPacket){
			$packet->namedtag = $this->biomeDefinitions;
			return $packet;
		}

		$pid = $packet->pid();

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

	public function getChunkProtocol() : int{
		return self::PROTOCOL_VERSION;
	}

	public function getProtocolVersion() : int{
		return self::PROTOCOL_VERSION;
	}
}

