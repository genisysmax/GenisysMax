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

namespace pocketmine\network\bedrock\adapter\v428;


use pocketmine\network\bedrock\adapter\v428\protocol\AddItemActorPacket as AddItemActorPacket428;
use pocketmine\network\bedrock\adapter\v428\protocol\AddPlayerPacket as AddPlayerPacket428;
use pocketmine\network\bedrock\adapter\v428\protocol\CraftingDataPacket as CraftingDataPacket428;
use pocketmine\network\bedrock\adapter\v428\protocol\CraftingEventPacket as CraftingEventPacket428;
use pocketmine\network\bedrock\adapter\v428\protocol\CreativeContentPacket as CreativeContentPacket428;
use pocketmine\network\bedrock\adapter\v428\protocol\InventoryContentPacket as InventoryContentPacket428;
use pocketmine\network\bedrock\adapter\v428\protocol\InventorySlotPacket as InventorySlotPacket428;
use pocketmine\network\bedrock\adapter\v428\protocol\InventoryTransactionPacket as InventoryTransactionPacket428;
use pocketmine\network\bedrock\adapter\v428\protocol\ItemStackRequestPacket as ItemStackRequestPacket428;
use pocketmine\network\bedrock\adapter\v428\protocol\LoginPacket as LoginPacket428;
use pocketmine\network\bedrock\adapter\v428\protocol\MobArmorEquipmentPacket as MobArmorEquipmentPacket428;
use pocketmine\network\bedrock\adapter\v428\protocol\MobEquipmentPacket as MobEquipmentPacket428;
use pocketmine\network\bedrock\adapter\v428\protocol\ProtocolInfo as ProtocolInfo428;
use pocketmine\network\bedrock\adapter\v428\protocol\types\LevelEventParticleIds as LevelEventParticleIds428;
use pocketmine\network\bedrock\adapter\v431\Protocol431Adapter;
use pocketmine\network\bedrock\adapter\v440\protocol\types\LevelEventParticleIds as NewLevelEventParticleIds;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\InventoryTransactionPacket;
use pocketmine\utils\Binary;

class Protocol428Adapter extends Protocol431Adapter{

    public const PROTOCOL_VERSION = 428;

	protected const PACKET_MAP = [
		ProtocolInfo428::ADD_ITEM_ACTOR_PACKET => AddItemActorPacket428::class,
		ProtocolInfo428::ADD_PLAYER_PACKET => AddPlayerPacket428::class,
		ProtocolInfo428::CRAFTING_DATA_PACKET => CraftingDataPacket428::class,
		ProtocolInfo428::CRAFTING_EVENT_PACKET => CraftingEventPacket428::class,
		ProtocolInfo428::LOGIN_PACKET => LoginPacket428::class,
		ProtocolInfo428::INVENTORY_TRANSACTION_PACKET => InventoryTransactionPacket428::class,
		ProtocolInfo428::INVENTORY_CONTENT_PACKET => InventoryContentPacket428::class,
		ProtocolInfo428::INVENTORY_SLOT_PACKET => InventorySlotPacket428::class,
		ProtocolInfo428::ITEM_STACK_REQUEST_PACKET => ItemStackRequestPacket428::class,
		ProtocolInfo428::MOB_ARMOR_EQUIPMENT_PACKET => MobArmorEquipmentPacket428::class,
		ProtocolInfo428::MOB_EQUIPMENT_PACKET => MobEquipmentPacket428::class,
		ProtocolInfo428::CREATIVE_CONTENT_PACKET => CreativeContentPacket428::class,
	];

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

		if($packet instanceof InventoryTransactionPacket){
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

    public function translateParticleId(int $particleId) : ?int {
        $particleId = parent::translateParticleId($particleId);

        if($particleId === null){
            return null;
        }else if($particleId < NewLevelEventParticleIds::STALACTITE_DRIP_WATER) {
	        return $particleId;
        }else if ($particleId === NewLevelEventParticleIds::STALACTITE_DRIP_WATER || $particleId === NewLevelEventParticleIds::STALACTITE_DRIP_LAVA) {
            return null;
        } else if ($particleId - 2 > LevelEventParticleIds428::DRAGON_BREATH_TRAIL) {
            return null;
        }

        return $particleId - 2;
    }

    public function getChunkProtocol() : int{
        return self::PROTOCOL_VERSION;
    }

    public function getProtocolVersion() : int{
        return self::PROTOCOL_VERSION;
    }
}

