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

namespace pocketmine\network\mcpe\protocol;

use function ord;

class PacketPool{
	/** @var \SplFixedArray<DataPacket> */
	protected static $pool = null;

	public static function init(){
		static::$pool = new \SplFixedArray(256);

		//Normal packets
		static::registerPacket(new LoginPacket());
		static::registerPacket(new PlayStatusPacket());
		static::registerPacket(new ServerToClientHandshakePacket());
		static::registerPacket(new ClientToServerHandshakePacket());
		static::registerPacket(new DisconnectPacket());
		static::registerPacket(new ResourcePacksInfoPacket());
		static::registerPacket(new ResourcePackStackPacket());
		static::registerPacket(new ResourcePackClientResponsePacket());
		static::registerPacket(new TextPacket());
		static::registerPacket(new SetTimePacket());
		static::registerPacket(new StartGamePacket());
		static::registerPacket(new AddPlayerPacket());
		static::registerPacket(new AddEntityPacket());
		static::registerPacket(new RemoveEntityPacket());
		static::registerPacket(new AddItemEntityPacket());
		static::registerPacket(new AddHangingEntityPacket());
		static::registerPacket(new TakeItemEntityPacket());
		static::registerPacket(new MoveEntityPacket());
		static::registerPacket(new MovePlayerPacket());
		static::registerPacket(new RiderJumpPacket());
		static::registerPacket(new RemoveBlockPacket());
		static::registerPacket(new UpdateBlockPacket());
		static::registerPacket(new AddPaintingPacket());
		static::registerPacket(new ExplodePacket());
		static::registerPacket(new LevelSoundEventPacket());
		static::registerPacket(new LevelEventPacket());
		static::registerPacket(new BlockEventPacket());
		static::registerPacket(new EntityEventPacket());
		static::registerPacket(new MobEffectPacket());
		static::registerPacket(new UpdateAttributesPacket());
		static::registerPacket(new MobEquipmentPacket());
		static::registerPacket(new MobArmorEquipmentPacket());
		static::registerPacket(new InteractPacket());
		static::registerPacket(new BlockPickRequestPacket());
		static::registerPacket(new UseItemPacket());
		static::registerPacket(new PlayerActionPacket());
		static::registerPacket(new EntityFallPacket());
		static::registerPacket(new HurtArmorPacket());
		static::registerPacket(new SetEntityDataPacket());
		static::registerPacket(new SetEntityMotionPacket());
		static::registerPacket(new SetEntityLinkPacket());
		static::registerPacket(new SetHealthPacket());
		static::registerPacket(new SetSpawnPositionPacket());
		static::registerPacket(new AnimatePacket());
		static::registerPacket(new RespawnPacket());
		static::registerPacket(new DropItemPacket());
		static::registerPacket(new InventoryActionPacket());
		static::registerPacket(new ContainerOpenPacket());
		static::registerPacket(new ContainerClosePacket());
		static::registerPacket(new ContainerSetSlotPacket());
		static::registerPacket(new ContainerSetDataPacket());
		static::registerPacket(new ContainerSetContentPacket());
		static::registerPacket(new CraftingDataPacket());
		static::registerPacket(new CraftingEventPacket());
		static::registerPacket(new AdventureSettingsPacket());
		static::registerPacket(new BlockEntityDataPacket());
		static::registerPacket(new PlayerInputPacket());
		static::registerPacket(new FullChunkDataPacket());
		static::registerPacket(new SetCommandsEnabledPacket());
		static::registerPacket(new SetDifficultyPacket());
		static::registerPacket(new ChangeDimensionPacket());
		static::registerPacket(new SetPlayerGameTypePacket());
		static::registerPacket(new PlayerListPacket());
		static::registerPacket(new SimpleEventPacket());
		static::registerPacket(new EventPacket());
		static::registerPacket(new SpawnExperienceOrbPacket());
		static::registerPacket(new ClientboundMapItemDataPacket());
		static::registerPacket(new MapInfoRequestPacket());
		static::registerPacket(new RequestChunkRadiusPacket());
		static::registerPacket(new ChunkRadiusUpdatedPacket());
		static::registerPacket(new ItemFrameDropItemPacket());
		static::registerPacket(new ReplaceItemInSlotPacket());
		static::registerPacket(new GameRulesChangedPacket());
		static::registerPacket(new CameraPacket());
		static::registerPacket(new AddItemPacket());
		static::registerPacket(new BossEventPacket());
		static::registerPacket(new ShowCreditsPacket());
		static::registerPacket(new AvailableCommandsPacket());
		static::registerPacket(new CommandStepPacket());
		static::registerPacket(new CommandBlockUpdatePacket());
		static::registerPacket(new UpdateTradePacket());
		static::registerPacket(new UpdateEquipPacket());
		static::registerPacket(new ResourcePackDataInfoPacket());
		static::registerPacket(new ResourcePackChunkDataPacket());
		static::registerPacket(new ResourcePackChunkRequestPacket());
		static::registerPacket(new TransferPacket());
		static::registerPacket(new PlaySoundPacket());
		static::registerPacket(new StopSoundPacket());
		static::registerPacket(new SetTitlePacket());
		static::registerPacket(new AddBehaviorTreePacket());
		static::registerPacket(new StructureBlockUpdatePacket());
		static::registerPacket(new ShowStoreOfferPacket());
		static::registerPacket(new PurchaseReceiptPacket());

		static::registerPacket(new BatchPacket());
	}

	/**
	 * @param DataPacket $packet
	 */
	public static function registerPacket(DataPacket $packet){
		static::$pool[$packet->pid()] = clone $packet;
	}

	/**
	 * @param int $pid
	 * @return DataPacket
	 */
	public static function getPacketById(int $pid) : DataPacket{
		return isset(static::$pool[$pid]) ? clone static::$pool[$pid] : new UnknownPacket();
	}

	/**
	 * @param string $buffer
	 * @return DataPacket
	 */
	public static function getPacket(string $buffer) : DataPacket{
		$pk = static::getPacketById(ord($buffer[0]));
		$pk->setBuffer($buffer);

		return $pk;
	}

}

