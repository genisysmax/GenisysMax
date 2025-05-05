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

namespace pocketmine\network\bedrock\adapter\v630;

use Closure;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\bedrock\adapter\v630\palette\BlockPalette as BlockPalette630;
use pocketmine\network\bedrock\adapter\v630\palette\ItemPalette as ItemPalette630;
use pocketmine\network\bedrock\adapter\v630\protocol as v630;
use pocketmine\network\bedrock\adapter\v649\Protocol649Adapter;
use pocketmine\network\bedrock\palette\block\BlockPalette;
use pocketmine\network\bedrock\palette\entry\BlockPaletteEntry;
use pocketmine\network\bedrock\palette\entry\ItemPaletteEntry;
use pocketmine\network\bedrock\palette\item\ItemPalette;
use pocketmine\network\bedrock\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\bedrock\protocol\BlockActorDataPacket;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\LevelEventPacket;
use pocketmine\network\bedrock\protocol\LevelSoundEventPacket;
use pocketmine\network\bedrock\protocol\ProtocolInfo;
use pocketmine\network\bedrock\protocol\UpdateBlockPacket;
use pocketmine\network\mcpe\NetworkNbtSerializer;
use pocketmine\network\Network;
use pocketmine\tile\ItemFrame;
use pocketmine\utils\Binary;
use function property_exists;

class Protocol630Adapter extends Protocol649Adapter {
	public const PROTOCOL_VERSION = 630;

    protected const NEW_PACKETS = [
        ProtocolInfo::SET_HUD_PACKET => true,
        ProtocolInfo::SERVER_PLAYER_POST_MOVE_POSITION_PACKET => true,
    ];

	protected const PACKET_MAP = [
        v630\ProtocolInfo::ADD_ACTOR_PACKET => v630\AddActorPacket::class,
        v630\ProtocolInfo::ADD_ITEM_ACTOR_PACKET => v630\AddItemActorPacket::class,
        v630\ProtocolInfo::CRAFTING_EVENT_PACKET => v630\CraftingEventPacket::class,
        v630\ProtocolInfo::CREATIVE_CONTENT_PACKET => v630\CreativeContentPacket::class,
        v630\ProtocolInfo::CRAFTING_DATA_PACKET => v630\CraftingDataPacket::class,
        v630\ProtocolInfo::INVENTORY_TRANSACTION_PACKET => v630\InventoryTransactionPacket::class,
        v630\ProtocolInfo::INVENTORY_SLOT_PACKET => v630\InventorySlotPacket::class,
        v630\ProtocolInfo::INVENTORY_CONTENT_PACKET => v630\InventoryContentPacket::class,
        v630\ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET => v630\MobArmorEquipmentPacket::class,
        v630\ProtocolInfo::MOB_EQUIPMENT_PACKET => v630\MobEquipmentPacket::class,
        v630\ProtocolInfo::START_GAME_PACKET => v630\StartGamePacket::class,

		v630\ProtocolInfo::LOGIN_PACKET => v630\LoginPacket::class,
        v630\ProtocolInfo::LEVEL_CHUNK_PACKET => v630\LevelChunkPacket::class,
        v630\ProtocolInfo::PLAYER_AUTH_INPUT_PACKET => v630\PlayerAuthInputPacket::class,
        v630\ProtocolInfo::PLAYER_LIST_PACKET => v630\PlayerListPacket::class,
	];

    public function __construct(){
        Network::registerPalette(new BlockPaletteEntry(new BlockPalette630()));
        Network::registerPalette(new ItemPaletteEntry(new ItemPalette630()));

        $this->actorIdentifiers = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v630/entity_identifiers.nbt");
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

        if ($packet instanceof BlockActorDataPacket){
            $namedtag = $packet->namedtag;
            $nbt = (new NetworkNbtSerializer())->read($namedtag)->mustGetCompoundTag();
            if($nbt->hasTag(ItemFrame::TAG_ITEM)){
                $item = $nbt->getTag(ItemFrame::TAG_ITEM, CompoundTag::class);
                if($item->hasTag("Name", StringTag::class)){
                    $name = $item->getString("Name");
                    $runtimeId = ItemPalette::getRuntimeFromStringId($name);
                    [$legacyId, $legacyMeta] = ItemPalette::getLegacyFromRuntimeId($runtimeId, 0);
                    [$runtimeId, $_] = ItemPalette630::getRuntimeFromLegacyId($legacyId, $legacyMeta);
                    $runtimeString = ItemPalette630::getStringFromRuntimeId($runtimeId);
                    $item->setString("Name", $runtimeString);
                    $packet->namedtag = (new NetworkNbtSerializer())->write(new TreeRoot($nbt));
                }
            }
            return $packet;
        }

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
		return BlockPalette630::getRuntimeFromLegacyId($id, $meta);
	}

	protected function processBlocks(DataPacket $packet, bool $clientToServer) : void{
		if($clientToServer){
			$c = function(int $runtimeId) : int{
				BlockPalette630::getLegacyFromRuntimeId($runtimeId, $id, $meta);
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

