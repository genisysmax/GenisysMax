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

namespace pocketmine\network\bedrock\adapter\v671;

use Closure;
use pocketmine\network\bedrock\adapter\v671\palette\BlockPalette as BlockPalette671;
use pocketmine\network\bedrock\adapter\v671\palette\ItemPalette as ItemPalette671;
use pocketmine\network\bedrock\adapter\v671\protocol as v671;
use pocketmine\network\bedrock\adapter\v685\Protocol685Adapter;
use pocketmine\network\bedrock\palette\block\BlockPalette;
use pocketmine\network\bedrock\palette\entry\BlockPaletteEntry;
use pocketmine\network\bedrock\palette\entry\ItemPaletteEntry;
use pocketmine\network\bedrock\protocol\BiomeDefinitionListPacket;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\LevelEventPacket;
use pocketmine\network\bedrock\protocol\LevelSoundEventPacket;
use pocketmine\network\bedrock\protocol\UpdateBlockPacket;
use pocketmine\network\Network;
use pocketmine\utils\Binary;
use function property_exists;

class Protocol671Adapter extends Protocol685Adapter {
	public const PROTOCOL_VERSION = 671;

	protected const PACKET_MAP = [
        v671\ProtocolInfo::ADD_ACTOR_PACKET => v671\AddActorPacket::class,
        v671\ProtocolInfo::ADD_ITEM_ACTOR_PACKET => v671\AddItemActorPacket::class,
        v671\ProtocolInfo::CRAFTING_EVENT_PACKET => v671\CraftingEventPacket::class,
        v671\ProtocolInfo::CREATIVE_CONTENT_PACKET => v671\CreativeContentPacket::class,
        v671\ProtocolInfo::CRAFTING_DATA_PACKET => v671\CraftingDataPacket::class,
        v671\ProtocolInfo::INVENTORY_TRANSACTION_PACKET => v671\InventoryTransactionPacket::class,
        v671\ProtocolInfo::INVENTORY_SLOT_PACKET => v671\InventorySlotPacket::class,
        v671\ProtocolInfo::INVENTORY_CONTENT_PACKET => v671\InventoryContentPacket::class,
        v671\ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET => v671\MobArmorEquipmentPacket::class,
        v671\ProtocolInfo::MOB_EQUIPMENT_PACKET => v671\MobEquipmentPacket::class,
        v671\ProtocolInfo::START_GAME_PACKET => v671\StartGamePacket::class,
        v671\ProtocolInfo::LOGIN_PACKET => v671\LoginPacket::class,
        v671\ProtocolInfo::TEXT_PACKET => v671\TextPacket::class,
        v671\ProtocolInfo::UPDATE_PLAYER_GAME_TYPE_PACKET => v671\UpdatePlayerGameTypePacket::class,
        v671\ProtocolInfo::CONTAINER_CLOSE_PACKET => v671\ContainerClosePacket::class,
	];

	public function __construct(){
        Network::registerPalette(new BlockPaletteEntry(new BlockPalette671()));
        Network::registerPalette(new ItemPaletteEntry(new ItemPalette671()));

        $this->biomeDefinitions = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v671/biome_definitions.nbt");
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
		return BlockPalette671::getRuntimeFromLegacyId($id, $meta);
	}

	protected function processBlocks(DataPacket $packet, bool $clientToServer) : void{
		if($clientToServer){
			$c = function(int $runtimeId) : int{
				BlockPalette671::getLegacyFromRuntimeId($runtimeId, $id, $meta);
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

