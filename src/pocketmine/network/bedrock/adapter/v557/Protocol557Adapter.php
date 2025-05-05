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

namespace pocketmine\network\bedrock\adapter\v557;

use Closure;
use pocketmine\network\bedrock\adapter\v557\palette\BlockPalette as BlockPalette557;
use pocketmine\network\bedrock\adapter\v557\palette\ItemPalette as ItemPalette557;
use pocketmine\network\bedrock\adapter\v557\protocol as v557;
use pocketmine\network\bedrock\adapter\v560\Protocol560Adapter;
use pocketmine\network\bedrock\palette\block\BlockPalette;
use pocketmine\network\bedrock\palette\entry\BlockPaletteEntry;
use pocketmine\network\bedrock\palette\entry\ItemPaletteEntry;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\LevelEventPacket;
use pocketmine\network\bedrock\protocol\LevelSoundEventPacket;
use pocketmine\network\bedrock\protocol\ProtocolInfo;
use pocketmine\network\bedrock\protocol\UpdateBlockPacket;
use pocketmine\network\Network;
use pocketmine\utils\Binary;
use function property_exists;

class Protocol557Adapter extends Protocol560Adapter{
	public const PROTOCOL_VERSION = 557;

    protected const NEW_PACKETS = [
        ProtocolInfo::UPDATE_CLIENT_INPUT_LOCKS_PACKET => true,
    ];

	protected const PACKET_MAP = [
		v557\ProtocolInfo::ADD_ACTOR_PACKET => v557\AddActorPacket::class,
		v557\ProtocolInfo::ADD_ITEM_ACTOR_PACKET => v557\AddItemActorPacket::class,
		v557\ProtocolInfo::ADD_PLAYER_PACKET => v557\AddPlayerPacket::class,
		v557\ProtocolInfo::CRAFTING_DATA_PACKET => v557\CraftingDataPacket::class,
		v557\ProtocolInfo::CRAFTING_EVENT_PACKET => v557\CraftingEventPacket::class,
		v557\ProtocolInfo::CREATIVE_CONTENT_PACKET => v557\CreativeContentPacket::class,
		v557\ProtocolInfo::INVENTORY_CONTENT_PACKET => v557\InventoryContentPacket::class,
		v557\ProtocolInfo::INVENTORY_SLOT_PACKET => v557\InventorySlotPacket::class,
		v557\ProtocolInfo::INVENTORY_TRANSACTION_PACKET => v557\InventoryTransactionPacket::class,
		v557\ProtocolInfo::LOGIN_PACKET => v557\LoginPacket::class,
		v557\ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET => v557\MobArmorEquipmentPacket::class,
		v557\ProtocolInfo::MOB_EQUIPMENT_PACKET => v557\MobEquipmentPacket::class,
		v557\ProtocolInfo::SET_ACTOR_DATA_PACKET => v557\SetActorDataPacket::class,
		v557\ProtocolInfo::START_GAME_PACKET => v557\StartGamePacket::class,
	];

    public function __construct(){
        Network::registerPalette(new BlockPaletteEntry(new BlockPalette557()));
        Network::registerPalette(new ItemPaletteEntry(new ItemPalette557()));
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
		return BlockPalette557::getRuntimeFromLegacyId($id, $meta);
	}

	protected function processBlocks(DataPacket $packet, bool $clientToServer) : void{
		if($clientToServer){
			$c = function(int $runtimeId) : int{
				BlockPalette557::getLegacyFromRuntimeId($runtimeId, $id, $meta);
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

