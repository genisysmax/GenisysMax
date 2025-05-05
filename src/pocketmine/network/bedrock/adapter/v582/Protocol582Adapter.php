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

namespace pocketmine\network\bedrock\adapter\v582;

use Closure;
use pocketmine\network\bedrock\adapter\v582\palette\BlockPalette as BlockPalette582;
use pocketmine\network\bedrock\adapter\v582\palette\ItemPalette as ItemPalette582;
use pocketmine\network\bedrock\adapter\v582\protocol as v582;
use pocketmine\network\bedrock\adapter\v589\Protocol589Adapter;
use pocketmine\network\bedrock\palette\block\BlockPalette;
use pocketmine\network\bedrock\palette\entry\BlockPaletteEntry;
use pocketmine\network\bedrock\palette\entry\ItemPaletteEntry;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\LevelEventPacket;
use pocketmine\network\bedrock\protocol\LevelSoundEventPacket;
use pocketmine\network\bedrock\protocol\UpdateBlockPacket;
use pocketmine\network\Network;
use pocketmine\utils\Binary;
use function property_exists;

class Protocol582Adapter extends Protocol589Adapter{
	public const PROTOCOL_VERSION = 582;

	protected const PACKET_MAP = [
		v582\ProtocolInfo::ADD_ACTOR_PACKET => v582\AddActorPacket::class,
		v582\ProtocolInfo::ADD_ITEM_ACTOR_PACKET => v582\AddItemActorPacket::class,
		v582\ProtocolInfo::ADD_PLAYER_PACKET => v582\AddPlayerPacket::class,
		v582\ProtocolInfo::CRAFTING_DATA_PACKET => v582\CraftingDataPacket::class,
		v582\ProtocolInfo::CRAFTING_EVENT_PACKET => v582\CraftingEventPacket::class,
		v582\ProtocolInfo::CREATIVE_CONTENT_PACKET => v582\CreativeContentPacket::class,
		v582\ProtocolInfo::EMOTE_PACKET => v582\EmotePacket::class,
		v582\ProtocolInfo::INVENTORY_CONTENT_PACKET => v582\InventoryContentPacket::class,
		v582\ProtocolInfo::INVENTORY_SLOT_PACKET => v582\InventorySlotPacket::class,
		v582\ProtocolInfo::INVENTORY_TRANSACTION_PACKET => v582\InventoryTransactionPacket::class,
		v582\ProtocolInfo::LOGIN_PACKET => v582\LoginPacket::class,
		v582\ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET => v582\MobArmorEquipmentPacket::class,
		v582\ProtocolInfo::MOB_EQUIPMENT_PACKET => v582\MobEquipmentPacket::class,
		v582\ProtocolInfo::START_GAME_PACKET => v582\StartGamePacket::class,
	];

    public function __construct(){
        Network::registerPalette(new BlockPaletteEntry(new BlockPalette582()));
        Network::registerPalette(new ItemPaletteEntry(new ItemPalette582()));
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
		return BlockPalette582::getRuntimeFromLegacyId($id, $meta);
	}

	protected function processBlocks(DataPacket $packet, bool $clientToServer) : void{
		if($clientToServer){
			$c = function(int $runtimeId) : int{
				BlockPalette582::getLegacyFromRuntimeId($runtimeId, $id, $meta);
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

