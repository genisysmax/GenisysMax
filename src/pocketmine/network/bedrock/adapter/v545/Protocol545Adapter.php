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

namespace pocketmine\network\bedrock\adapter\v545;

use pocketmine\network\bedrock\adapter\v545\protocol\CraftingDataPacket as CraftingDataPacket545;
use pocketmine\network\bedrock\adapter\v545\protocol\ItemStackRequestPacket as ItemStackRequestPacket545;
use pocketmine\network\bedrock\adapter\v545\protocol\LoginPacket as LoginPacket545;
use pocketmine\network\bedrock\adapter\v545\protocol\NetworkSettingsPacket as NetworkSettingsPacket545;
use pocketmine\network\bedrock\adapter\v545\protocol\ProtocolInfo as ProtocolInfo545;
use pocketmine\network\bedrock\adapter\v554\Protocol554Adapter;
use pocketmine\network\bedrock\protocol\BiomeDefinitionListPacket;
use pocketmine\network\bedrock\protocol\CraftingDataPacket;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\NetworkSettingsPacket;
use pocketmine\network\bedrock\protocol\ProtocolInfo;
use pocketmine\utils\Binary;
use function file_get_contents;

class Protocol545Adapter extends Protocol554Adapter{
	public const PROTOCOL_VERSION = 545;
	public const RAKNET_PROTOCOL_VERSION = 10;

    protected const NEW_PACKETS = [
        ProtocolInfo::SERVER_STATS_PACKET => true,
        ProtocolInfo::REQUEST_NETWORK_SETTINGS_PACKET => true,
        ProtocolInfo::GAME_TEST_REQUEST_PACKET => true,
        ProtocolInfo::GAME_TEST_RESULTS_PACKET => true,
    ];

    public function __construct(){
        $this->biomeDefinitions = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v545/biome_definitions.nbt");
	}

	public function processClientToServer(string $buf) : ?DataPacket{
		$offset = 0;
		$pid = Binary::readUnsignedVarInt($buf, $offset);

		if($pid === ProtocolInfo545::LOGIN_PACKET){
			return new LoginPacket545($buf);
		}

		if($pid === ProtocolInfo545::ITEM_STACK_REQUEST_PACKET){
			return new ItemStackRequestPacket545($buf);
		}

		return parent::processClientToServer($buf);
	}

	public function processServerToClient(DataPacket $packet) : ?DataPacket{
		if($packet instanceof BiomeDefinitionListPacket){
			$pk = clone $packet;
			$pk->namedtag = $this->biomeDefinitions;
			return $pk;
		}

		if($packet instanceof CraftingDataPacket){
			$pk = new CraftingDataPacket545();
			$pk->entries = $packet->entries;
			$pk->potionTypeRecipes = $packet->potionTypeRecipes;
			$pk->potionContainerRecipes = $packet->potionContainerRecipes;
			$pk->materialReducerRecipes = $packet->materialReducerRecipes;
			$pk->cleanRecipes = $packet->cleanRecipes;
			return $pk;
		}

		if($packet instanceof NetworkSettingsPacket){
			$pk = new NetworkSettingsPacket545();
			$pk->compressionThreshold = $packet->compressionThreshold;
			return $pk;
		}

        if(isset(self::NEW_PACKETS[$packet->pid()])){
            return null;
        }

		return parent::processServerToClient($packet);
	}

	public function getProtocolVersion() : int{
		return self::PROTOCOL_VERSION;
	}
}

