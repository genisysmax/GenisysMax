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

namespace pocketmine\network\bedrock\adapter\v407;

use Closure;
use pocketmine\item\Item;
use pocketmine\network\bedrock\adapter\v407\palette\BlockPalette as BlockPalette407;
use pocketmine\network\bedrock\adapter\v407\palette\ItemPalette as ItemPalette407;
use pocketmine\network\bedrock\adapter\v407\protocol\AddItemActorPacket as AddItemActorPacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\AddPlayerPacket as AddPlayerPacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\ContainerClosePacket as ContainerClosePacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\CraftingDataPacket as CraftingDataPacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\CraftingEventPacket as CraftingEventPacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\CreativeContentPacket as CreativeContentPacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\InventoryContentPacket as InventoryContentPacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\InventorySlotPacket as InventorySlotPacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\InventoryTransactionPacket as InventoryTransactionPacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\ItemStackRequestPacket as ItemStackRequestPacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\ItemStackResponsePacket as ItemStackResponsePacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\LoginPacket as LoginPacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\MobArmorEquipmentPacket as MobArmorEquipmentPacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\MobEquipmentPacket as MobEquipmentPacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\MovePlayerPacket as MovePlayerPacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\PlayerActionPacket as PlayerActionPacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\PlayerAuthInputPacket as PlayerAuthInputPacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\PlayerListPacket as PlayerListPacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\PlayerSkinPacket as PlayerSkinPacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\ProtocolInfo as ProtocolInfo407;
use pocketmine\network\bedrock\adapter\v407\protocol\ResourcePackStackPacket as ResourcePackStackPacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\SetActorDataPacket as SetActorDataPacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\StartGamePacket as StartGamePacket407;
use pocketmine\network\bedrock\adapter\v407\protocol\UpdateAttributesPacket as UpdateAttributesPacket407;
use pocketmine\network\bedrock\adapter\v419\Protocol419Adapter;
use pocketmine\network\bedrock\palette\block\BlockPalette;
use pocketmine\network\bedrock\palette\entry\BlockPaletteEntry;
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

class Protocol407Adapter extends Protocol419Adapter{

	public const PROTOCOL_VERSION = 407; // 1.16.0

	protected const NEW_PACKETS = [
		ProtocolInfo::MOTION_PREDICTION_HINTS_PACKET => true,
		ProtocolInfo::ANIMATE_ACTOR_PACKET => true,
		ProtocolInfo::CAMERA_SHAKE_PACKET => true,
		ProtocolInfo::PLAYER_FOG_PACKET => true,
		ProtocolInfo::CORRECT_PLAYER_MOVE_PREDICTION_PACKET => true,
		ProtocolInfo::ITEM_COMPONENT_PACKET => true,
        ProtocolInfo::MOVE_ACTOR_DELTA_PACKET => true,
	];

	protected const OLD_PACKETS = [
		ProtocolInfo407::ACTOR_FALL_PACKET => true,
		ProtocolInfo407::UPDATE_BLOCK_PROPERTIES_PACKET => true
	];

	protected const PACKET_MAP = [
		ProtocolInfo407::ADD_ITEM_ACTOR_PACKET => AddItemActorPacket407::class,
		ProtocolInfo407::ADD_PLAYER_PACKET => AddPlayerPacket407::class,
		ProtocolInfo407::CONTAINER_CLOSE_PACKET => ContainerClosePacket407::class,
		ProtocolInfo407::CRAFTING_DATA_PACKET => CraftingDataPacket407::class,
		ProtocolInfo407::CRAFTING_EVENT_PACKET => CraftingEventPacket407::class,
		ProtocolInfo407::CREATIVE_CONTENT_PACKET => CreativeContentPacket407::class,
		ProtocolInfo407::INVENTORY_CONTENT_PACKET => InventoryContentPacket407::class,
		ProtocolInfo407::INVENTORY_SLOT_PACKET => InventorySlotPacket407::class,
		ProtocolInfo407::INVENTORY_TRANSACTION_PACKET => InventoryTransactionPacket407::class,
		ProtocolInfo407::ITEM_STACK_REQUEST_PACKET => ItemStackRequestPacket407::class,
		ProtocolInfo407::ITEM_STACK_RESPONSE_PACKET => ItemStackResponsePacket407::class,
		ProtocolInfo407::LOGIN_PACKET => LoginPacket407::class,
		ProtocolInfo407::MOB_ARMOR_EQUIPMENT_PACKET => MobArmorEquipmentPacket407::class,
		ProtocolInfo407::MOB_EQUIPMENT_PACKET => MobEquipmentPacket407::class,
		ProtocolInfo407::MOVE_PLAYER_PACKET => MovePlayerPacket407::class,
		ProtocolInfo407::PLAYER_ACTION_PACKET => PlayerActionPacket407::class,
		ProtocolInfo407::PLAYER_AUTH_INPUT_PACKET => PlayerAuthInputPacket407::class,
		ProtocolInfo407::PLAYER_LIST_PACKET => PlayerListPacket407::class,
		ProtocolInfo407::PLAYER_SKIN_PACKET => PlayerSkinPacket407::class,
		ProtocolInfo407::RESOURCE_PACK_STACK_PACKET => ResourcePackStackPacket407::class,
		ProtocolInfo407::SET_ACTOR_DATA_PACKET => SetActorDataPacket407::class,
		ProtocolInfo407::START_GAME_PACKET => StartGamePacket407::class,
		ProtocolInfo407::UPDATE_ATTRIBUTES_PACKET => UpdateAttributesPacket407::class,
	];

    public function __construct(){
        Network::registerPalette(new BlockPaletteEntry(new BlockPalette407()));
		ItemPalette407::lazyInit();

		$this->actorIdentifiers = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v407/entity_identifiers.nbt");
	}

    public function getCreativeItems() : array{
        $creativeItemsJson = json_decode(file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v407/creativeitems.json"), true);
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
		if(isset(self::OLD_PACKETS[$pid])){
			return null;
		}

		if(isset(self::PACKET_MAP[$pid])){
			$class = self::PACKET_MAP[$pid];

			$pk = new $class($buf);
			if($pk instanceof PlayerActionPacket407){
				$pk->decode();

				if($pk->action === PlayerActionPacket407::ACTION_DIMENSION_CHANGE_REQUEST){
					return null; // Unsupported
				}
			}
			return $pk;
		}

		return parent::processClientToServer($buf);
	}

	public function processServerToClient(DataPacket $packet) : ?DataPacket{
		$pid = $packet->pid();
		if($packet instanceof AvailableActorIdentifiersPacket){
			$packet->namedtag = $this->actorIdentifiers;
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
		return BlockPalette407::getRuntimeFromLegacyId($id, $meta);
	}

	protected function processBlocks(DataPacket $packet, bool $clientToServer) : void{
		if($clientToServer){
			$c = function(int $runtimeId) : int{
				BlockPalette407::getLegacyFromRuntimeId($runtimeId, $id, $meta);
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

