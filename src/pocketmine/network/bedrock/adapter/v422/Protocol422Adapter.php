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

namespace pocketmine\network\bedrock\adapter\v422;

use Closure;
use pocketmine\network\bedrock\adapter\mapper\CommandArgTypeMapper;
use pocketmine\network\bedrock\adapter\v422\palette\BlockPalette as BlockPalette422;
use pocketmine\network\bedrock\adapter\v422\protocol\AddActorPacket as AddActorPacket422;
use pocketmine\network\bedrock\adapter\v422\protocol\AddItemActorPacket as AddItemActorPacket422;
use pocketmine\network\bedrock\adapter\v422\protocol\AdventureSettingsPacket as AdventureSettingsPacket422;
use pocketmine\network\bedrock\adapter\v422\protocol\AvailableCommandsPacket as AvailableCommandsPacket422;
use pocketmine\network\bedrock\adapter\v422\protocol\CameraShakePacket as CameraShakePacket422;
use pocketmine\network\bedrock\adapter\v422\protocol\ItemStackRequestPacket as ItemStackRequestPacket422;
use pocketmine\network\bedrock\adapter\v422\protocol\ItemStackResponsePacket as ItemStackResponsePacket422;
use pocketmine\network\bedrock\adapter\v422\protocol\LoginPacket as LoginPacket422;
use pocketmine\network\bedrock\adapter\v422\protocol\PlayerActionPacket as PlayerActionPacket422;
use pocketmine\network\bedrock\adapter\v422\protocol\PlayerListPacket as PlayerListPacket422;
use pocketmine\network\bedrock\adapter\v422\protocol\PlayerSkinPacket as PlayerSkinPacket422;
use pocketmine\network\bedrock\adapter\v422\protocol\ProtocolInfo as ProtocolInfo422;
use pocketmine\network\bedrock\adapter\v422\protocol\SetActorDataPacket as SetActorDataPacket422;
use pocketmine\network\bedrock\adapter\v422\protocol\StartGamePacket as StartGamePacket422;
use pocketmine\network\bedrock\adapter\v428\Protocol428Adapter;
use pocketmine\network\bedrock\palette\block\BlockPalette;
use pocketmine\network\bedrock\palette\entry\BlockPaletteEntry;
use pocketmine\network\bedrock\protocol\AdventureSettingsPacket;
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

class Protocol422Adapter extends Protocol428Adapter {

	public const PROTOCOL_VERSION = 422;

	protected const NEW_PACKETS = [
		ProtocolInfo::CLIENTBOUND_DEBUG_RENDERER_PACKET => true
	];

	protected const PACKET_MAP = [
		ProtocolInfo422::ADD_ACTOR_PACKET => AddActorPacket422::class,
		ProtocolInfo422::ADD_ITEM_ACTOR_PACKET => AddItemActorPacket422::class,
		ProtocolInfo422::ADVENTURE_SETTINGS_PACKET => AdventureSettingsPacket422::class,
		ProtocolInfo422::CAMERA_SHAKE_PACKET => CameraShakePacket422::class,
		ProtocolInfo422::ITEM_STACK_REQUEST_PACKET => ItemStackRequestPacket422::class,
		ProtocolInfo422::ITEM_STACK_RESPONSE_PACKET => ItemStackResponsePacket422::class,
		ProtocolInfo422::LOGIN_PACKET => LoginPacket422::class,
		ProtocolInfo422::PLAYER_ACTION_PACKET => PlayerActionPacket422::class,
		ProtocolInfo422::PLAYER_LIST_PACKET => PlayerListPacket422::class,
		ProtocolInfo422::PLAYER_SKIN_PACKET => PlayerSkinPacket422::class,
		ProtocolInfo422::SET_ACTOR_DATA_PACKET => SetActorDataPacket422::class,
		ProtocolInfo422::START_GAME_PACKET => StartGamePacket422::class,
	];

    public function __construct(){
        Network::registerPalette(new BlockPaletteEntry(new BlockPalette422()));

		$this->biomeDefinitions = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/v422/biome_definitions.nbt");
	}

	public function initArgTypeMapper() : void{
		$this->argTypeMapper = new CommandArgTypeMapper(AvailableCommandsPacket422::class);
	}

	public function processClientToServer(string $buf) : ?DataPacket{
		$offset = 0;
		$pid = Binary::readUnsignedVarInt($buf, $offset);

		if(isset(self::PACKET_MAP[$pid])){
			$class = self::PACKET_MAP[$pid];

			$pk = new $class($buf);
			if($pk instanceof AdventureSettingsPacket422){
				$pk->decode();

				if($pk->getFlag(AdventureSettingsPacket422::BUILD_AND_MINE)){
					$pk->setFlag(AdventureSettingsPacket::BUILD, true);
					$pk->setFlag(AdventureSettingsPacket::MINE, true);
				}
			}
			return $pk;
		}

		return parent::processClientToServer($buf);
    }

	public function processServerToClient(DataPacket $packet) : ?DataPacket{
		$pid = $packet->pid();

		if($packet instanceof BiomeDefinitionListPacket){
			$packet->namedtag = $this->biomeDefinitions;
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
		return BlockPalette422::getRuntimeFromLegacyId($id, $meta);
	}

	protected function processBlocks(DataPacket $packet, bool $clientToServer) : void{
		if($clientToServer){
			$c = function(int $runtimeId) : int{
				BlockPalette422::getLegacyFromRuntimeId($runtimeId, $id, $meta);
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


