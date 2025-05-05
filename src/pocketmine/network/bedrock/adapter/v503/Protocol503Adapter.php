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

namespace pocketmine\network\bedrock\adapter\v503;

use Closure;
use pocketmine\network\bedrock\adapter\mapper\CommandArgTypeMapper;
use pocketmine\network\bedrock\adapter\v503\palette\BlockPalette as BlockPalette503;
use pocketmine\network\bedrock\adapter\v503\palette\ItemPalette as ItemPalette503;
use pocketmine\network\bedrock\adapter\v503\protocol\AddItemActorPacket as AddItemActorPacket503;
use pocketmine\network\bedrock\adapter\v503\protocol\AddPlayerPacket as AddPlayerPacket503;
use pocketmine\network\bedrock\adapter\v503\protocol\AvailableCommandsPacket as AvailableCommandsPacket503;
use pocketmine\network\bedrock\adapter\v503\protocol\CraftingDataPacket as CraftingDataPacket503;
use pocketmine\network\bedrock\adapter\v503\protocol\CraftingEventPacket as CraftingEventPacket503;
use pocketmine\network\bedrock\adapter\v503\protocol\CreativeContentPacket as CreativeContentPacket503;
use pocketmine\network\bedrock\adapter\v503\protocol\InventoryContentPacket as InventoryContentPacket503;
use pocketmine\network\bedrock\adapter\v503\protocol\InventorySlotPacket as InventorySlotPacket503;
use pocketmine\network\bedrock\adapter\v503\protocol\InventoryTransactionPacket as InventoryTransactionPacket503;
use pocketmine\network\bedrock\adapter\v503\protocol\LoginPacket as LoginPacket503;
use pocketmine\network\bedrock\adapter\v503\protocol\MobArmorEquipmentPacket as MobArmorEquipmentPacket503;
use pocketmine\network\bedrock\adapter\v503\protocol\MobEquipmentPacket as MobEquipmentPacket503;
use pocketmine\network\bedrock\adapter\v503\protocol\PlayerActionPacket as PlayerActionPacket503;
use pocketmine\network\bedrock\adapter\v503\protocol\PlayerAuthInputPacket as PlayerAuthInputPacket503;
use pocketmine\network\bedrock\adapter\v503\protocol\ProtocolInfo as ProtocolInfo503;
use pocketmine\network\bedrock\adapter\v503\protocol\StartGamePacket as StartGamePacket503;
use pocketmine\network\bedrock\adapter\v527\Protocol527Adapter;
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
use function file_get_contents;
use function property_exists;

class Protocol503Adapter extends Protocol527Adapter{
	public const PROTOCOL_VERSION = 503;

	protected const PACKET_MAP = [
		ProtocolInfo503::ADD_ITEM_ACTOR_PACKET => AddItemActorPacket503::class,
		ProtocolInfo503::ADD_PLAYER_PACKET => AddPlayerPacket503::class,
		ProtocolInfo503::CRAFTING_DATA_PACKET => CraftingDataPacket503::class,
		ProtocolInfo503::CRAFTING_EVENT_PACKET => CraftingEventPacket503::class,
		ProtocolInfo503::CREATIVE_CONTENT_PACKET => CreativeContentPacket503::class,
		ProtocolInfo503::INVENTORY_CONTENT_PACKET => InventoryContentPacket503::class,
		ProtocolInfo503::INVENTORY_SLOT_PACKET => InventorySlotPacket503::class,
		ProtocolInfo503::INVENTORY_TRANSACTION_PACKET => InventoryTransactionPacket503::class,
		ProtocolInfo503::LOGIN_PACKET => LoginPacket503::class,
		ProtocolInfo503::MOB_ARMOR_EQUIPMENT_PACKET => MobArmorEquipmentPacket503::class,
		ProtocolInfo503::MOB_EQUIPMENT_PACKET => MobEquipmentPacket503::class,
		ProtocolInfo503::PLAYER_ACTION_PACKET => PlayerActionPacket503::class,
		ProtocolInfo503::PLAYER_AUTH_INPUT_PACKET => PlayerAuthInputPacket503::class,
		ProtocolInfo503::START_GAME_PACKET => StartGamePacket503::class,
	];

	protected const NEW_PACKETS = [
		ProtocolInfo::LESSON_PROGRESS_PACKET => true,
		ProtocolInfo::REQUEST_ABILITY_PACKET => true,
		ProtocolInfo::REQUEST_PERMISSIONS_PACKET => true,
		ProtocolInfo::TOAST_REQUEST_PACKET => true,
	];

    public function __construct(){
        Network::registerPalette(new BlockPaletteEntry(new BlockPalette503()));
        Network::registerPalette(new ItemPaletteEntry(new ItemPalette503()));

		$this->biomeDefinitions = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v503/biome_definitions.nbt");
		$this->actorIdentifiers = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v503/entity_identifiers.nbt");
	}

	public function initArgTypeMapper() : void{
		$this->argTypeMapper = new CommandArgTypeMapper(AvailableCommandsPacket503::class);
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

	public function translateBlockId(int $runtimeId) : int{
		BlockPalette::getLegacyFromRuntimeId($runtimeId, $id, $meta);
		return BlockPalette503::getRuntimeFromLegacyId($id, $meta);
	}

	protected function processBlocks(DataPacket $packet, bool $clientToServer) : void{
		if($clientToServer){
			$c = function(int $runtimeId) : int{
				BlockPalette503::getLegacyFromRuntimeId($runtimeId, $id, $meta);
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

