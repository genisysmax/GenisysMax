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

namespace pocketmine\network\bedrock\adapter\v568;

use Closure;
use pocketmine\item\Item;
use pocketmine\network\bedrock\adapter\v568\palette\BlockPalette as BlockPalette568;
use pocketmine\network\bedrock\adapter\v568\palette\ItemPalette as ItemPalette568;
use pocketmine\network\bedrock\adapter\v568\protocol as v568;
use pocketmine\network\bedrock\adapter\v575\Protocol575Adapter;
use pocketmine\network\bedrock\palette\block\BlockPalette;
use pocketmine\network\bedrock\palette\entry\BlockPaletteEntry;
use pocketmine\network\bedrock\palette\entry\ItemPaletteEntry;
use pocketmine\network\bedrock\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\LevelEventPacket;
use pocketmine\network\bedrock\protocol\LevelSoundEventPacket;
use pocketmine\network\bedrock\protocol\ProtocolInfo;
use pocketmine\network\bedrock\protocol\UpdateBlockPacket;
use pocketmine\network\Network;
use pocketmine\utils\Binary;
use function file_get_contents;
use function property_exists;

class Protocol568Adapter extends Protocol575Adapter{
	public const PROTOCOL_VERSION = 568;

    protected const NEW_PACKETS = [
        ProtocolInfo::CAMERA_PRESETS_PACKET => true,
        ProtocolInfo::UNLOCKED_RECIPES_PACKET => true,
        ProtocolInfo::CAMERA_INSTRUCTION_PACKET => true,
    ];

	protected const PACKET_MAP = [
		v568\ProtocolInfo::ADD_ACTOR_PACKET => v568\AddActorPacket::class,
		v568\ProtocolInfo::ADD_ITEM_ACTOR_PACKET => v568\AddItemActorPacket::class,
		v568\ProtocolInfo::ADD_PLAYER_PACKET => v568\AddPlayerPacket::class,
		v568\ProtocolInfo::CRAFTING_DATA_PACKET => v568\CraftingDataPacket::class,
		v568\ProtocolInfo::CRAFTING_EVENT_PACKET => v568\CraftingEventPacket::class,
		v568\ProtocolInfo::CREATIVE_CONTENT_PACKET => v568\CreativeContentPacket::class,
		v568\ProtocolInfo::INVENTORY_CONTENT_PACKET => v568\InventoryContentPacket::class,
		v568\ProtocolInfo::INVENTORY_SLOT_PACKET => v568\InventorySlotPacket::class,
		v568\ProtocolInfo::INVENTORY_TRANSACTION_PACKET => v568\InventoryTransactionPacket::class,
		v568\ProtocolInfo::LOGIN_PACKET => v568\LoginPacket::class,
		v568\ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET => v568\MobArmorEquipmentPacket::class,
		v568\ProtocolInfo::MOB_EQUIPMENT_PACKET => v568\MobEquipmentPacket::class,
		v568\ProtocolInfo::PLAYER_AUTH_INPUT_PACKET => v568\PlayerAuthInputPacket::class,
        v568\ProtocolInfo::START_GAME_PACKET => v568\StartGamePacket::class,
	];

    public function __construct(){
        Network::registerPalette(new BlockPaletteEntry(new BlockPalette568()));
        Network::registerPalette(new ItemPaletteEntry(new ItemPalette568()));

		$this->actorIdentifiers = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v568/entity_identifiers.nbt");
	}

    public function getCreativeItems() : array{
        $creativeItemsJson = json_decode(file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v568/creativeitems.json"), true);
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
		return BlockPalette568::getRuntimeFromLegacyId($id, $meta);
	}

	protected function processBlocks(DataPacket $packet, bool $clientToServer) : void{
		if($clientToServer){
			$c = function(int $runtimeId) : int{
				BlockPalette568::getLegacyFromRuntimeId($runtimeId, $id, $meta);
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

