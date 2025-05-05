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

namespace pocketmine\network\bedrock\adapter\v575;

use Closure;
use pocketmine\item\Item;
use pocketmine\network\bedrock\adapter\mapper\CommandArgTypeMapper;
use pocketmine\network\bedrock\adapter\v575\palette\BlockPalette as BlockPalette575;
use pocketmine\network\bedrock\adapter\v575\palette\ItemPalette as ItemPalette575;
use pocketmine\network\bedrock\adapter\v575\protocol as v575;
use pocketmine\network\bedrock\adapter\v582\Protocol582Adapter;
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

class Protocol575Adapter extends Protocol582Adapter{
	public const PROTOCOL_VERSION = 575;

    protected const NEW_PACKETS = [
        ProtocolInfo::OPEN_SIGN_PACKET => true,
        ProtocolInfo::TRIM_DATA_PACKET => true,
        ProtocolInfo::COMPRESSED_BIOME_DEFINITION_LIST_PACKET => true,
    ];

	protected const PACKET_MAP = [
		v575\ProtocolInfo::ADD_ACTOR_PACKET => v575\AddActorPacket::class,
		v575\ProtocolInfo::ADD_ITEM_ACTOR_PACKET => v575\AddItemActorPacket::class,
		v575\ProtocolInfo::ADD_PLAYER_PACKET => v575\AddPlayerPacket::class,
		v575\ProtocolInfo::CRAFTING_DATA_PACKET => v575\CraftingDataPacket::class,
		v575\ProtocolInfo::CRAFTING_EVENT_PACKET => v575\CraftingEventPacket::class,
		v575\ProtocolInfo::CREATIVE_CONTENT_PACKET => v575\CreativeContentPacket::class,
		v575\ProtocolInfo::INVENTORY_CONTENT_PACKET => v575\InventoryContentPacket::class,
		v575\ProtocolInfo::INVENTORY_SLOT_PACKET => v575\InventorySlotPacket::class,
		v575\ProtocolInfo::INVENTORY_TRANSACTION_PACKET => v575\InventoryTransactionPacket::class,
		v575\ProtocolInfo::LOGIN_PACKET => v575\LoginPacket::class,
		v575\ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET => v575\MobArmorEquipmentPacket::class,
		v575\ProtocolInfo::MOB_EQUIPMENT_PACKET => v575\MobEquipmentPacket::class,
		v575\ProtocolInfo::REQUEST_CHUNK_RADIUS_PACKET => v575\RequestChunkRadiusPacket::class,
		v575\ProtocolInfo::START_GAME_PACKET => v575\StartGamePacket::class,
    ];

    public function __construct(){
        Network::registerPalette(new BlockPaletteEntry(new BlockPalette575()));
        Network::registerPalette(new ItemPaletteEntry(new ItemPalette575()));

		$this->biomeDefinitions = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v575/biome_definitions.nbt");
		$this->actorIdentifiers = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v575/entity_identifiers.nbt");
	}

    public function getCreativeItems(): array
    {
        $creativeItemsJson = json_decode(file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/creativeitems.json"), true);
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

	public function initArgTypeMapper() : void{
		$this->argTypeMapper = new CommandArgTypeMapper(v575\AvailableCommandsPacket::class);
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
			$pk = clone $packet;
			$pk->namedtag = $this->actorIdentifiers;
			return $pk;
		}
		if($packet instanceof BiomeDefinitionListPacket){
			$pk = clone $packet;
			$pk->namedtag = $this->biomeDefinitions;
			return $pk;
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
		return BlockPalette575::getRuntimeFromLegacyId($id, $meta);
	}

	protected function processBlocks(DataPacket $packet, bool $clientToServer) : void{
		if($clientToServer){
			$c = function(int $runtimeId) : int{
				BlockPalette575::getLegacyFromRuntimeId($runtimeId, $id, $meta);
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

