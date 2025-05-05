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

namespace pocketmine\network\bedrock\adapter\v527;

use pocketmine\network\bedrock\adapter\v527\palette\ItemPalette as ItemPalette527;
use pocketmine\network\bedrock\adapter\v527\protocol\AddActorPacket as AddActorPacket527;
use pocketmine\network\bedrock\adapter\v527\protocol\AddItemActorPacket as AddItemActorPacket527;
use pocketmine\network\bedrock\adapter\v527\protocol\AddPlayerPacket as AddPlayerPacket527;
use pocketmine\network\bedrock\adapter\v527\protocol\CraftingDataPacket as CraftingDataPacket527;
use pocketmine\network\bedrock\adapter\v527\protocol\CraftingEventPacket as CraftingEventPacket527;
use pocketmine\network\bedrock\adapter\v527\protocol\CreativeContentPacket as CreativeContentPacket527;
use pocketmine\network\bedrock\adapter\v527\protocol\InventoryContentPacket as InventoryContentPacket527;
use pocketmine\network\bedrock\adapter\v527\protocol\InventorySlotPacket as InventorySlotPacket527;
use pocketmine\network\bedrock\adapter\v527\protocol\InventoryTransactionPacket as InventoryTransactionPacket527;
use pocketmine\network\bedrock\adapter\v527\protocol\LoginPacket as LoginPacket527;
use pocketmine\network\bedrock\adapter\v527\protocol\MobArmorEquipmentPacket as MobArmorEquipmentPacket527;
use pocketmine\network\bedrock\adapter\v527\protocol\MobEquipmentPacket as MobEquipmentPacket527;
use pocketmine\network\bedrock\adapter\v527\protocol\ProtocolInfo as ProtocolInfo527;
use pocketmine\network\bedrock\adapter\v527\protocol\StartGamePacket as StartGamePacket527;
use pocketmine\network\bedrock\adapter\v534\Protocol534Adapter;
use pocketmine\network\bedrock\palette\entry\ItemPaletteEntry;
use pocketmine\network\bedrock\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\ProtocolInfo;
use pocketmine\network\Network;
use pocketmine\utils\Binary;
use function file_get_contents;
use function property_exists;

class Protocol527Adapter extends Protocol534Adapter{
	public const PROTOCOL_VERSION = 527;

	protected const PACKET_MAP = [
		ProtocolInfo527::ADD_ACTOR_PACKET => AddActorPacket527::class,
		ProtocolInfo527::ADD_ITEM_ACTOR_PACKET => AddItemActorPacket527::class,
		ProtocolInfo527::ADD_PLAYER_PACKET => AddPlayerPacket527::class,
		ProtocolInfo527::CRAFTING_DATA_PACKET => CraftingDataPacket527::class,
		ProtocolInfo527::CRAFTING_EVENT_PACKET => CraftingEventPacket527::class,
		ProtocolInfo527::CREATIVE_CONTENT_PACKET => CreativeContentPacket527::class,
		ProtocolInfo527::INVENTORY_CONTENT_PACKET => InventoryContentPacket527::class,
		ProtocolInfo527::INVENTORY_SLOT_PACKET => InventorySlotPacket527::class,
		ProtocolInfo527::INVENTORY_TRANSACTION_PACKET => InventoryTransactionPacket527::class,
		ProtocolInfo527::LOGIN_PACKET => LoginPacket527::class,
		ProtocolInfo527::MOB_ARMOR_EQUIPMENT_PACKET => MobArmorEquipmentPacket527::class,
		ProtocolInfo527::MOB_EQUIPMENT_PACKET => MobEquipmentPacket527::class,
		ProtocolInfo527::START_GAME_PACKET => StartGamePacket527::class,
	];

	protected const NEW_PACKETS = [
		ProtocolInfo::UPDATE_ABILITIES_PACKET => true,
		ProtocolInfo::UPDATE_ADVENTURE_SETTINGS_PACKET => true,
		ProtocolInfo::DEATH_INFO_PACKET => true,
		ProtocolInfo::EDITOR_NETWORK_PACKET => true,
	];

    public function __construct(){
        Network::registerPalette(new ItemPaletteEntry(new ItemPalette527()));

		$this->actorIdentifiers = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v527/entity_identifiers.nbt");
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

	public function getProtocolVersion() : int{
		return self::PROTOCOL_VERSION;
	}
}

