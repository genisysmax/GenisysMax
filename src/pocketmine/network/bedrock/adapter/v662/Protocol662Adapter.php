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

namespace pocketmine\network\bedrock\adapter\v662;

use Closure;
use pocketmine\network\bedrock\adapter\v662\palette\BlockPalette as BlockPalette662;
use pocketmine\network\bedrock\adapter\v662\palette\ItemPalette as ItemPalette662;
use pocketmine\network\bedrock\adapter\v662\protocol as v662;
use pocketmine\network\bedrock\adapter\v671\Protocol671Adapter;
use pocketmine\network\bedrock\palette\block\BlockPalette;
use pocketmine\network\bedrock\palette\entry\BlockPaletteEntry;
use pocketmine\network\bedrock\palette\entry\ItemPaletteEntry;
use pocketmine\network\bedrock\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\LevelEventPacket;
use pocketmine\network\bedrock\protocol\LevelSoundEventPacket;
use pocketmine\network\bedrock\protocol\UpdateBlockPacket;
use pocketmine\network\Network;
use pocketmine\utils\Binary;
use function property_exists;

class Protocol662Adapter extends Protocol671Adapter {
	public const PROTOCOL_VERSION = 662;

	protected const PACKET_MAP = [
        v662\ProtocolInfo::ADD_ACTOR_PACKET => v662\AddActorPacket::class,
        v662\ProtocolInfo::ADD_ITEM_ACTOR_PACKET => v662\AddItemActorPacket::class,
        v662\ProtocolInfo::CRAFTING_EVENT_PACKET => v662\CraftingEventPacket::class,
        v662\ProtocolInfo::CREATIVE_CONTENT_PACKET => v662\CreativeContentPacket::class,
        v662\ProtocolInfo::CRAFTING_DATA_PACKET => v662\CraftingDataPacket::class,
        v662\ProtocolInfo::INVENTORY_TRANSACTION_PACKET => v662\InventoryTransactionPacket::class,
        v662\ProtocolInfo::INVENTORY_SLOT_PACKET => v662\InventorySlotPacket::class,
        v662\ProtocolInfo::INVENTORY_CONTENT_PACKET => v662\InventoryContentPacket::class,
        v662\ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET => v662\MobArmorEquipmentPacket::class,
        v662\ProtocolInfo::MOB_EQUIPMENT_PACKET => v662\MobEquipmentPacket::class,
        v662\ProtocolInfo::START_GAME_PACKET => v662\StartGamePacket::class,
		v662\ProtocolInfo::LOGIN_PACKET => v662\LoginPacket::class,
        v662\ProtocolInfo::RESOURCE_PACK_STACK_PACKET => v662\ResourcePackStackPacket::class,
        v662\ProtocolInfo::UPDATE_PLAYER_GAME_TYPE_PACKET => v662\UpdatePlayerGameTypePacket::class,
	];

	public function __construct(){
        Network::registerPalette(new BlockPaletteEntry(new BlockPalette662()));
        Network::registerPalette(new ItemPaletteEntry(new ItemPalette662()));

        $this->actorIdentifiers = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v662/entity_identifiers.nbt");
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

        if($packet instanceof AvailableActorIdentifiersPacket){
            $packet->namedtag = $this->actorIdentifiers;
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
		return BlockPalette662::getRuntimeFromLegacyId($id, $meta);
	}

	protected function processBlocks(DataPacket $packet, bool $clientToServer) : void{
		if($clientToServer){
			$c = function(int $runtimeId) : int{
				BlockPalette662::getLegacyFromRuntimeId($runtimeId, $id, $meta);
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

