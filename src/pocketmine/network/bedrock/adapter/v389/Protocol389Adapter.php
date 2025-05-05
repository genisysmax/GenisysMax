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

namespace pocketmine\network\bedrock\adapter\v389;

use Closure;
use pocketmine\network\bedrock\adapter\v389\palette\BlockPalette as BlockPalette389;
use pocketmine\network\bedrock\adapter\v389\palette\ItemPalette as ItemPalette389;
use pocketmine\network\bedrock\adapter\v389\protocol as v389;
use pocketmine\network\bedrock\adapter\v390\Protocol390Adapter;
use pocketmine\network\bedrock\palette\block\BlockPalette;
use pocketmine\network\bedrock\palette\entry\BlockPaletteEntry;
use pocketmine\network\bedrock\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\bedrock\protocol\BiomeDefinitionListPacket;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\LevelEventPacket;
use pocketmine\network\bedrock\protocol\LevelSoundEventPacket;
use pocketmine\network\bedrock\protocol\UpdateBlockPacket;
use pocketmine\network\Network;
use pocketmine\utils\Binary;
use function file_get_contents;
use function property_exists;

class Protocol389Adapter extends Protocol390Adapter{

	public const PROTOCOL_VERSION = 389; // 1.14.0

	protected const OLD_PACKETS = [
		v389\ProtocolInfo::ACTOR_FALL_PACKET => true,
		v389\ProtocolInfo::UPDATE_BLOCK_PROPERTIES_PACKET => true
	];

	protected const PACKET_MAP = [
		v389\ProtocolInfo::ADD_ITEM_ACTOR_PACKET => v389\AddItemActorPacket::class,
		v389\ProtocolInfo::ADD_PLAYER_PACKET => v389\AddPlayerPacket::class,
		v389\ProtocolInfo::CRAFTING_DATA_PACKET => v389\CraftingDataPacket::class,
		v389\ProtocolInfo::CRAFTING_EVENT_PACKET => v389\CraftingEventPacket::class,
        v389\ProtocolInfo::INVENTORY_CONTENT_PACKET => v389\InventoryContentPacket::class,
        v389\ProtocolInfo::INVENTORY_SLOT_PACKET => v389\InventorySlotPacket::class,
		v389\ProtocolInfo::INVENTORY_TRANSACTION_PACKET => v389\InventoryTransactionPacket::class,
		v389\ProtocolInfo::LOGIN_PACKET => v389\LoginPacket::class,
		v389\ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET => v389\MobArmorEquipmentPacket::class,
		v389\ProtocolInfo::MOB_EQUIPMENT_PACKET => v389\MobEquipmentPacket::class,
		v389\ProtocolInfo::START_GAME_PACKET => v389\StartGamePacket::class,

        v389\ProtocolInfo::PLAYER_SKIN_PACKET => v389\PlayerSkinPacket::class,
        v389\ProtocolInfo::PLAYER_LIST_PACKET => v389\PlayerListPacket::class,
	];

	public function __construct(){
        Network::registerPalette(new BlockPaletteEntry(new BlockPalette389()));
		ItemPalette389::lazyInit();

		$this->actorIdentifiers = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v389/entity_identifiers.nbt");
        $this->biomeDefinitions = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v389/biome_definitions.nbt");
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
		return BlockPalette389::getRuntimeFromLegacyId($id, $meta);
	}

	protected function processBlocks(DataPacket $packet, bool $clientToServer) : void{
		if($clientToServer){
			$c = function(int $runtimeId) : int{
				BlockPalette389::getLegacyFromRuntimeId($runtimeId, $id, $meta);
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

