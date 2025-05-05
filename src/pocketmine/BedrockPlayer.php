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

namespace pocketmine;

use pocketmine\block\ItemFrame;
use pocketmine\entity\Human;
use pocketmine\entity\object\Item as DroppedItem;
use pocketmine\entity\object\MinecartAbstract;
use pocketmine\entity\object\MinecartEmpty;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\Rideable;
use pocketmine\entity\Skin;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\inventory\InventoryPickupArrowEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\player\PlayerAnimationEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerSkinChangeEvent;
use pocketmine\event\player\PlayerToggleGlideEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\player\PlayerToggleSprintEvent;
use pocketmine\event\player\PlayerTransferEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\RawPacketSendEvent;
use pocketmine\event\TextContainer;
use pocketmine\event\Timings;
use pocketmine\event\TranslationContainer;
use pocketmine\form\Form;
use pocketmine\form\FormValidationException;
use pocketmine\inventory\BaseTransaction;
use pocketmine\inventory\DropItemTransaction;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\SwapTransaction;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\FlintSteel;
use pocketmine\item\Item;
use pocketmine\item\SpawnEgg;
use pocketmine\level\Level;
use pocketmine\level\WeakPosition;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\bedrock\adapter\ProtocolAdapter;
use pocketmine\network\bedrock\adapter\ProtocolAdapterFactory;
use pocketmine\network\bedrock\adapter\v390\Protocol390Adapter;
use pocketmine\network\bedrock\adapter\v527\Protocol527Adapter;
use pocketmine\network\bedrock\adapter\v649\Protocol649Adapter;
use pocketmine\network\bedrock\BedrockPacketBatch;
use pocketmine\network\bedrock\chunk\BedrockChunkCache;
use pocketmine\network\bedrock\CompressBatchTask;
use pocketmine\network\bedrock\NetworkCompression;
use pocketmine\network\bedrock\PacketTranslator;
use pocketmine\network\bedrock\palette\ActorMapping;
use pocketmine\network\bedrock\palette\block\BlockPalette;
use pocketmine\network\bedrock\PlayerNetworkSessionAdapter;
use pocketmine\network\bedrock\protocol\ActorEventPacket;
use pocketmine\network\bedrock\protocol\AdventureSettingsPacket;
use pocketmine\network\bedrock\protocol\AnimatePacket;
use pocketmine\network\bedrock\protocol\AvailableCommandsPacket;
use pocketmine\network\bedrock\protocol\BlockActorDataPacket;
use pocketmine\network\bedrock\protocol\BlockPickRequestPacket;
use pocketmine\network\bedrock\protocol\ChangeDimensionPacket;
use pocketmine\network\bedrock\protocol\ChunkRadiusUpdatedPacket;
use pocketmine\network\bedrock\protocol\CompletedUsingItemPacket;
use pocketmine\network\bedrock\protocol\DataPacket as BedrockPacket;
use pocketmine\network\bedrock\protocol\DisconnectPacket;
use pocketmine\network\bedrock\protocol\EmotePacket;
use pocketmine\network\bedrock\protocol\InventorySlotPacket;
use pocketmine\network\bedrock\protocol\InventoryTransactionPacket;
use pocketmine\network\bedrock\protocol\ItemFrameDropItemPacket;
use pocketmine\network\bedrock\protocol\LevelSoundEventPacket;
use pocketmine\network\bedrock\protocol\LoginPacket;
use pocketmine\network\bedrock\protocol\MapInfoRequestPacket;
use pocketmine\network\bedrock\protocol\MobEquipmentPacket;
use pocketmine\network\bedrock\protocol\ModalFormRequestPacket;
use pocketmine\network\bedrock\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\bedrock\protocol\NetworkSettingsPacket;
use pocketmine\network\bedrock\protocol\PlayerActionPacket;
use pocketmine\network\bedrock\protocol\PlayerInputPacket;
use pocketmine\network\bedrock\protocol\PlayerListPacket;
use pocketmine\network\bedrock\protocol\PlayerSkinPacket;
use pocketmine\network\bedrock\protocol\PlayStatusPacket;
use pocketmine\network\bedrock\protocol\ProtocolInfo;
use pocketmine\network\bedrock\protocol\RequestNetworkSettingsPacket;
use pocketmine\network\bedrock\protocol\ResourcePackChunkDataPacket;
use pocketmine\network\bedrock\protocol\ResourcePackChunkRequestPacket;
use pocketmine\network\bedrock\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\bedrock\protocol\ResourcePackDataInfoPacket;
use pocketmine\network\bedrock\protocol\ResourcePacksInfoPacket;
use pocketmine\network\bedrock\protocol\ResourcePackStackPacket;
use pocketmine\network\bedrock\protocol\RespawnPacket;
use pocketmine\network\bedrock\protocol\ServerToClientHandshakePacket;
use pocketmine\network\bedrock\protocol\SetPlayerGameTypePacket;
use pocketmine\network\bedrock\protocol\SetTitlePacket;
use pocketmine\network\bedrock\protocol\StartGamePacket;
use pocketmine\network\bedrock\protocol\TextPacket;
use pocketmine\network\bedrock\protocol\TransferPacket;
use pocketmine\network\bedrock\protocol\types\command\CommandEnum;
use pocketmine\network\bedrock\protocol\types\command\CommandOverload;
use pocketmine\network\bedrock\protocol\types\command\CommandParameter;
use pocketmine\network\bedrock\protocol\types\command\CommandPermissions;
use pocketmine\network\bedrock\protocol\types\CompressionAlgorithm;
use pocketmine\network\bedrock\protocol\types\Experiments;
use pocketmine\network\bedrock\protocol\types\inventory\ContainerIds;
use pocketmine\network\bedrock\protocol\types\inventory\ItemInstance;
use pocketmine\network\bedrock\protocol\types\MismatchTransactionData;
use pocketmine\network\bedrock\protocol\types\NetworkInventoryAction;
use pocketmine\network\bedrock\protocol\types\NormalTransactionData;
use pocketmine\network\bedrock\protocol\types\PlayerListEntry;
use pocketmine\network\bedrock\protocol\types\PlayerMovementSettings;
use pocketmine\network\bedrock\protocol\types\PlayerMovementType;
use pocketmine\network\bedrock\protocol\types\PlayerPermissions;
use pocketmine\network\bedrock\protocol\types\ReleaseItemTransactionData;
use pocketmine\network\bedrock\protocol\types\resourcepacks\ResourcePackStackEntry;
use pocketmine\network\bedrock\protocol\types\UpdateAbilitiesPacketLayer;
use pocketmine\network\bedrock\protocol\types\UseItemOnActorTransactionData;
use pocketmine\network\bedrock\protocol\types\UseItemTransactionData;
use pocketmine\network\bedrock\protocol\UpdateAbilitiesPacket;
use pocketmine\network\bedrock\protocol\UpdateAdventureSettingsPacket;
use pocketmine\network\bedrock\protocol\UpdateAttributesPacket;
use pocketmine\network\bedrock\StaticPacketCache;
use pocketmine\network\bedrock\utils\BedrockUtils;
use pocketmine\network\bedrock\VerifyLoginTask;
use pocketmine\network\CompressBatchPromise;
use pocketmine\network\mcpe\encryption\EncryptionContext;
use pocketmine\network\mcpe\encryption\PrepareEncryptionTask;
use pocketmine\network\mcpe\NetworkCompression as McpeNetworkCompression;
use pocketmine\network\mcpe\NetworkNbtSerializer;
use pocketmine\network\mcpe\protocol\AnimatePacket as MCPEAnimatePacket;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\EntityEventPacket as MCPEEntityEventPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket as MCPELevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket as MCPELevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\TakeItemEntityPacket as MCPETakeItemEntityPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter as MCPECommandParameter;
use pocketmine\network\NetworkInterface;
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\tile\Spawnable;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;
use pocketmine\utils\UUID;
use function array_merge;
use function ceil;
use function count;
use function floor;
use function implode;
use function in_array;
use function microtime;
use function min;
use function round;
use function strtolower;
use function ucfirst;
use const M_PI;

class BedrockPlayer extends Player{

    public static function isValidUserName(string $name) : bool{
        $lname = strtolower($name);
        $len = strlen($name);
        return $lname !== "rcon" and $lname !== "console" and $len >= 2 and $len <= 16 and preg_match("/[^A-Za-z0-9_ ]/", $name) === 0 and trim($lname) !== "" and trim($lname) === $lname;
    }

	/** @var string */
	protected $xuid = "";
	/** @var string */
	protected $platformOnlineId = "";
	/** @var UUID */
	protected $deviceId;

	/** @var ProtocolAdapter|null */
	protected $protocolAdapter;
	/** @var int */
	protected $requestedViewDistance = 8;
	/** @var int */
	protected $lastEatingSound = 0;

	/** @var float */
	protected $lastRightClickTime = 0.0;
    /** @var UseItemTransactionData|null */
    protected $lastRightClickData = null;

	/** @var bool[] */
	protected $openedNewInventories = [];
	/** @var int */
	protected $clientClosingWindowId = -1;

	/** @var bool */
	protected $enableCompression = true;

	/** @var string|null */
	protected $lastRequestedFullSkinId = null;
	protected int $formIdCounter = 0;
	/** @var Form[] */
	protected array $forms = [];

	/**
	 * @internal
	 *
	 * @param int $windowId
	 *
	 * @return bool
	 */
	public function newInventoryOpen(int $windowId) : bool{
		if(!isset($this->openedNewInventories[$windowId])){
			$this->openedNewInventories[$windowId] = true;
			return true;
		}
		return false;
	}

	/**
	 * @internal
	 *
	 * @param int $windowId
	 */
	public function newInventoryClose(int $windowId) : void{
		unset($this->openedNewInventories[$windowId]);
	}

    /**
     * @internal
     *
     * @return int
     */
    public function getClientClosingWindowId() : int{
        return $this->clientClosingWindowId;
    }

    /**
     * @internal
     *
     * @param int $clientClosingWindowId
     */
    public function setClientClosingWindowId(int $clientClosingWindowId) : void{
        $this->clientClosingWindowId = $clientClosingWindowId;
    }

	/**
	 * @param NetworkInterface $interface
	 * @param int             $clientID
	 * @param string          $ip
	 * @param int             $port
	 */
	public function __construct(NetworkInterface $interface, $clientID, $ip, $port, bool $isValid){
		parent::__construct($interface, $clientID, $ip, $port, $isValid);

		$this->sessionAdapter = new PlayerNetworkSessionAdapter($this->server, $this);
		$this->chunkCache = BedrockChunkCache::getInstance($this->level, $this->getChunkProtocol());
	}

	/**
	 * @internal
	 *
	 * @param bool $enableCompression
	 */
	public function setEnableCompression(bool $enableCompression) : void{
		$this->enableCompression = $enableCompression;
	}

	/**
	 * @deprecated
	 * @return bool
	 */
	public function isBedrock() : bool{
		return true;
	}

	/**
	 * @return bool
	 */
	public function hasNetworkCompression() : bool{
		return $this->enableCompression;
	}

	/**
	 * Called when a packet is received from the client. This method will call DataPacketReceiveEvent.
	 *
	 * @param DataPacket $packet
	 */
	public function handleDataPacket(DataPacket $packet){
		if($packet instanceof BedrockPacket and $this->sessionAdapter !== null){
			$this->sessionAdapter->handleDataPacket($packet);
		}
	}

	protected function checkProtocol(string $buffer, int $protocol) : bool{
		$currentProtocol = ProtocolInfo::CURRENT_PROTOCOL;
		if($this->protocolAdapter !== null){
			$currentProtocol = $this->protocolAdapter->getProtocolVersion();
		}
		if($protocol !== $currentProtocol){
			if($this->protocolAdapter === null){
				$protocolAdapter = ProtocolAdapterFactory::get($protocol);
				if($protocolAdapter !== null){
					$this->protocolAdapter = $protocolAdapter;
					$this->chunkCache = BedrockChunkCache::getInstance($this->level, $this->getChunkProtocol());

					$pk = $protocolAdapter->processClientToServer($buffer);
					if($pk !== null){
						$this->handleDataPacket($pk);
					}
					return false;
				}
			}

			if($protocol < $currentProtocol){
				$message = "disconnectionScreen.outdatedClient";
				$this->sendPlayStatus(PlayStatusPacket::LOGIN_FAILED_CLIENT, true);
			}else{
				$message = "disconnectionScreen.outdatedServer";
				$this->sendPlayStatus(PlayStatusPacket::LOGIN_FAILED_SERVER, true);
			}
			$this->close("", $message, false);

			return false;
		}

		return true;
	}

    public function handleBedrockMapInfoRequest(MapInfoRequestPacket $packet) : bool
    {
        return false;
    }

	public function handleRequestNetworkSettings(RequestNetworkSettingsPacket $packet) : bool{
		if($this->enableCompression){
			return false;
		}

		if(!$this->checkProtocol($packet->buffer, $packet->protocolVersion)){
			return true;
		}

		$pk = new NetworkSettingsPacket();
		$pk->compressionThreshold = NetworkSettingsPacket::COMPRESS_EVERYTHING;
		$pk->compressionAlgorithm = CompressionAlgorithm::ZLIB;
		$pk->enableClientThrottling = false;
		$pk->clientThrottleThreshold = 0;
		$pk->clientThrottleScalar = 0;
		$this->sendDataPacket($pk, false, true);

        $this->enableCompression = true;
		return true;
	}

	public function handleBedrockLogin(LoginPacket $packet) : bool{
		if(!$this->enableCompression or $this->loggedIn){
			return false;
		}

		if(!$this->checkProtocol($packet->buffer, $packet->protocol)){
			return true;
		}

        $this->username = TextFormat::clean($packet->username);
        if($this->server->getAdvancedProperty("player.replace-gap-nickname", true)){
            $this->username = str_replace(" ", "_", $packet->username);
        }
		$this->displayName = $this->username;
		$this->iusername = strtolower($this->username);

		$this->languageCode = $packet->languageCode;
		$this->deviceModel = $packet->deviceModel;
		$this->clientVersion = $packet->clientVersion;
		$this->deviceOS = $packet->deviceOS;
		$this->currentInputMode = $packet->currentInputMode;
		$this->defaultInputMode = $packet->defaultInputMode;
		$this->uiProfile = $packet->uiProfile;
		$this->platformOnlineId = $packet->clientData["PlatformOnlineId"] ?? "";

		$this->xuid = $packet->xuid;

		if($this->server->getConfigBoolean("online-mode", false) and !$this->xboxAuthenticated){
			$this->kick("disconnectionScreen.notAuthenticated", false);
			return true;
		}

		if(count($this->server->getOnlinePlayers()) >= $this->server->getMaxPlayers() and $this->kick("disconnectionScreen.serverFull", false)){
			return true;
		}

		$this->randomClientId = $packet->clientId;

		$this->deviceId = UUID::fromString($packet->deviceId);

		$this->uuid = UUID::fromString($packet->clientUUID);
		$this->rawUUID = $this->uuid->toBinary();

		if(!Player::isValidUserName($packet->username)){
			$this->close("", "disconnectionScreen.invalidName");
			return true;
		}

		if(!$packet->skin->isValid()){
			$this->close("", "disconnectScreen.invalidSkin");
			return true;
		}
		$this->setSkin(Skin::fromBedrockSkin($packet->skin));

		$ev = new PlayerPreLoginEvent($this, "Plugin reason");
		$ev->call();
		if($ev->isCancelled()){
			$this->close("", $ev->getKickMessage());

			return true;
		}

		if(!$packet->skipVerification){
			$this->server->getScheduler()->scheduleAsyncTask(new VerifyLoginTask($this, $packet));
		}else{
			$this->onVerifyCompleted($packet, null, true);
		}

		return true;
	}


	/**
	 * @param LoginPacket  $packet
	 * @param string|null  $error
	 * @param bool         $signedByMojang
	 *
	 * @return void
	 */
	public function onVerifyCompleted($packet, ?string $error, bool $signedByMojang) : void{
		if($this->closed){
			return;
		}

		if($error !== null){
			$this->close("", $this->server->getLanguage()->translateString("Invalid session. Reason: $error"));
			return;
		}

		if(!$signedByMojang and $this->xuid !== ""){
			$this->server->getLogger()->warning($this->getName() . " has an XUID, but their login keychain is not signed by Mojang");
			$this->xuid = "";
		}

		if($this->xuid === ""){
			if($signedByMojang){
				$this->server->getLogger()->error($this->getName() . " should have an XUID, but none found");
			}

			if($this->server->getConfigBoolean("online-mode", false) and $this->kick("disconnectionScreen.notAuthenticated", false)){ //use kick to allow plugins to cancel this
				return;
			}

			$this->server->getLogger()->debug($this->getName() . " is NOT logged into Xbox Live");
			$this->xboxAuthenticated = false;
		}else{
			$this->server->getLogger()->debug($this->getName() . " is logged into Xbox Live");
			$this->xboxAuthenticated = true;
		}

		$identityPublicKey = base64_decode($packet->identityPublicKey, true);

		if($identityPublicKey === false){
			//if this is invalid it should have borked VerifyLoginTask
			throw new \InvalidArgumentException("We should never have reached here if the key is invalid");
		}

		if(EncryptionContext::$ENABLED){
			$this->getServer()->getScheduler()->getAsyncPool()->submitTask(new PrepareEncryptionTask(
				$identityPublicKey,
				function(string $encryptionKey, string $handshakeJwt, string $_, string $_1) use ($packet) : void{
					if(!$this->isConnected()){
						return;
					}

					$pk = new ServerToClientHandshakePacket();
					$pk->jwt = $handshakeJwt;
					$this->sendDataPacket($pk, false, true); //make sure this gets sent before encryption is enabled

					$this->awaitingEncryptionHandshake = true;

					if($packet->protocol < 429) {
						$this->cipher = EncryptionContext::cfb8($encryptionKey);
					} else {
						$this->cipher = EncryptionContext::fakeGCM($encryptionKey);
					}


					$this->server->getLogger()->debug("Enabled encryption for " . $this->username);
				}
			));
		}else{
			$this->processLogin();
		}
	}

	public function handlePlayerSkin(PlayerSkinPacket $packet) : bool{
		if($packet->skin->getFullSkinId() === $this->lastRequestedFullSkinId){
			//TODO: HACK! In 1.19.60, the client sends its skin back to us if we sent it a skin different from the one
			//it's using. We need to prevent this from causing a feedback loop.
			$this->server->getLogger()->debug("Refused duplicate skin change request for " . $this->getName());
			return true;
		}
		$this->lastRequestedFullSkinId = $packet->skin->getFullSkinId();

		if(!$packet->skin->isValid()){
			return false;
		}

		$ev = new PlayerSkinChangeEvent($this, $this->skin, Skin::fromBedrockSkin($packet->skin));
		$ev->call();
		if($ev->isCancelled()){
			$this->sendSkin($this);
			return true;
		}

		$this->setSkin($ev->getNewSkin());
		$this->sendSkin();
		$this->sendSkin($this);
		return true;
	}

	protected function processLogin(){
		if(!$this->server->isWhitelisted($this->iusername)){
			$this->close($this->getLeaveMessage(), "Server is white-listed");

			return;
		}elseif($this->server->getNameBans()->isBanned($this->iusername) or $this->server->getIPBans()->isBanned($this->getAddress())){
			$this->close($this->getLeaveMessage(), "You are banned");

			return;
		}

        foreach($this->server->getOnlinePlayers() as $p){
            if($p !== $this and ($p->iusername === $this->iusername or $this->getUniqueId()->equals($p->getUniqueId()))){
                $this->close($this->getLeaveMessage(), "disconnectionScreen.loggedinOtherLocation");
                return;
            }
        }

        if($this->loggedIn){
            return; //спасает от одновременного входа игроков с одним ником
        }

		$this->namedtag = $this->server->getOfflinePlayerData($this->username);

		$this->playedBefore = ($this->namedtag->getLong("lastPlayed", 0) - $this->namedtag->getLong("firstPlayed", 0)) > 1; // microtime(true) - microtime(true) may have less than one millisecond difference
		$this->namedtag->setString("NameTag", $this->username);
		$this->gamemode = $this->namedtag->getInt("playerGameType", 0) & 0x03;
		if($this->server->getForceGamemode()){
			$this->gamemode = $this->server->getGamemode();
			$this->namedtag->setInt("playerGameType", $this->gamemode);
		}

		$this->allowFlight = (bool) ($this->gamemode & 0x01);

		if(($level = $this->server->getLevelByName($this->namedtag->getString("Level", ""))) !== null){
			$this->setLevel($level);
		}else{
			$this->setLevel($this->server->getDefaultLevel());

			$spawn = $this->level->getSpawnPosition();
			$this->namedtag->setString("Level", $this->level->getName());
			$this->namedtag->setTag("Pos", new ListTag([
				new DoubleTag($spawn->x),
				new DoubleTag($spawn->y),
				new DoubleTag($spawn->z),
			]));
		}

		$this->namedtag->setLong("lastPlayed", (int) floor(microtime(true) * 1000));
		if($this->server->getAutoSave()){
			$this->server->saveOfflinePlayerData($this->username, $this->namedtag, true);
		}

		$this->sendPlayStatus(PlayStatusPacket::LOGIN_SUCCESS);

		$this->loggedIn = true;

		$pk = new ResourcePacksInfoPacket();
		$manager = $this->server->getBedrockResourcePackManager();
		$pk->resourcePackEntries = $manager->getResourceStack();
		$pk->mustAccept = $manager->resourcePacksRequired();
		$this->sendDataPacket($pk);
	}

	public function sendAttributes(bool $sendAll = false){
		$entries = $sendAll ? $this->attributeMap->getAll() : $this->attributeMap->needSend();
		if(count($entries) > 0){
			$pk = new UpdateAttributesPacket();
			$pk->actorRuntimeId = $this->id;
			$pk->entries = $entries;
			$this->sendDataPacket($pk);
			foreach($entries as $entry){
				$entry->markSynchronized();
			}
		}
	}

	public function handleBedrockResourcePackClientResponse(ResourcePackClientResponsePacket $packet) : bool{
		switch($packet->status){
			case ResourcePackClientResponsePacket::STATUS_REFUSED:
				//TODO: add lang strings for this
				$this->close("", "You must accept resource packs to join this server.", true);
				break;
			case ResourcePackClientResponsePacket::STATUS_SEND_PACKS:
				$manager = $this->server->getBedrockResourcePackManager();
				foreach($packet->packIds as $uuid){
					//dirty hack for mojang's dirty hack for versions
					$splitPos = strpos($uuid, "_");
					if($splitPos !== false){
						$uuid = substr($uuid, 0, $splitPos);
					}
					$pack = $manager->getPackById($uuid);

					if(!($pack instanceof ResourcePack)){
						//Client requested a resource pack but we don't have it available on the server
						$this->close("", "disconnectionScreen.resourcePack", true);
						$this->server->getLogger()->debug("Got a resource pack request for unknown pack with UUID " . $uuid . ", available packs: " . implode(", ", $manager->getPackIdList()));
						return false;
					}

					$pk = new ResourcePackDataInfoPacket();
					$pk->packId = $pack->getPackId();
					$pk->maxChunkSize = self::PACK_CHUNK_SIZE;
					$pk->chunkCount = (int) ceil($pack->getPackSize() / $pk->maxChunkSize);
					$pk->compressedPackSize = $pack->getPackSize();
					$pk->sha256 = $pack->getSha256();
					$this->sendDataPacket($pk);
				}

				break;
			case ResourcePackClientResponsePacket::STATUS_HAVE_ALL_PACKS:
                $manager = $this->server->getBedrockResourcePackManager();

                $stack = array_map(static function(ResourcePack $pack) : ResourcePackStackEntry{
                    return new ResourcePackStackEntry($pack->getPackId(), $pack->getPackVersion(), ""); //TODO: subpacks
                }, $manager->getResourceStack());

                //we support chemistry blocks by default, the client should already have this installed
                $stack[] = new ResourcePackStackEntry("0fba4063-dba1-4281-9b89-ff9390653530", "1.0.0", "");

				$pk = new ResourcePackStackPacket();
				$pk->resourcePackStack = $stack;
				$pk->mustAccept = $manager->resourcePacksRequired();
				$pk->experiments = new Experiments([], false);
				$this->sendDataPacket($pk);
				break;
			case ResourcePackClientResponsePacket::STATUS_COMPLETED:
				$this->completeLoginSequence();
				return true;
			default:
				return false;
		}

		return true;
	}

	public function handleBedrockResourcePackChunkRequest(ResourcePackChunkRequestPacket $packet) : bool{
		$manager = $this->server->getBedrockResourcePackManager();
		$pack = $manager->getPackById($packet->packId);
		if(!($pack instanceof ResourcePack)){
			$this->close("", "disconnectionScreen.resourcePack", true);
			$this->server->getLogger()->debug("Got a resource pack chunk request for unknown pack with UUID " . $packet->packId . ", available packs: " . implode(", ", $manager->getPackIdList()));

			return false;
		}

		$packId = $pack->getPackId();

		if(isset($this->downloadedChunks[$packId][$packet->chunkIndex])){
			$this->close("", "disconnectionScreen.resourcePack", true);
			$this->server->getLogger()->debug("Duplicate request for chunk $packet->chunkIndex of pack $packet->packId");

			return false;
		}

		$offset = $packet->chunkIndex * self::PACK_CHUNK_SIZE;
		if($offset < 0 || $offset >= $pack->getPackSize()){
			$this->close("", "disconnectionScreen.resourcePack", true);
			$this->server->getLogger()->debug("Invalid out-of-bounds request for chunk $packet->chunkIndex of $packet->packId: offset $offset, file size " . $pack->getPackSize());

			return false;
		}

		if(!isset($this->downloadedChunks[$packId])){
			$this->downloadedChunks[$packId] = [$packet->chunkIndex => true];
		}else{
			$this->downloadedChunks[$packId][$packet->chunkIndex] = true;
		}

		$pk = new ResourcePackChunkDataPacket();
		$pk->packId = $packId;
		$pk->chunkIndex = $packet->chunkIndex;
		$pk->offset = $offset;
		$pk->data = $pack->getPackChunk($offset, self::PACK_CHUNK_SIZE);
		$this->sendDataPacket($pk);
		return true;
	}

	public function setViewDistance(int $requestedDistance){
		$distance = $requestedDistance;
		if(!$this->spawned){
			$this->requestedViewDistance = $distance;
			$distance = min($distance, $this->server->getProperty("chunk-sending.spawn-radius", 4)); //a hack
		}
		$this->viewDistance = $this->server->getAllowedViewDistance($distance);

		$this->spawnThreshold = (int) ($this->viewDistance ** 2 * M_PI);

		$this->nextChunkOrderRun = 0;

		$pk = new ChunkRadiusUpdatedPacket();
		$pk->radius = $this->viewDistance;
		$this->sendDataPacket($pk);

		$this->server->getLogger()->debug("Setting view distance for " . $this->getName() . " to " . $this->viewDistance . " (requested " . $requestedDistance . ")");
	}

	protected function completeLoginSequence() : void{
		if($this->loginProcessed){
			$this->close("", "Trying to login after logging in");
			$this->server->getNetwork()->blockAddress($this->ip, 1200);
			throw new \InvalidArgumentException("Attempted to complete login sequence while it was already completed");
		}
		$this->loginProcessed = true;

		Human::__construct($this->level, $this->namedtag);

        if(!$this->isConnected()){
            return;
        }

		if(!$this->hasValidSpawnPosition()){
			if($this->namedtag->hasTag("SpawnLevel", StringTag::class) and ($level = $this->server->getLevelByName($this->namedtag->getString("SpawnLevel"))) instanceof Level){
				$this->spawnPosition = new WeakPosition($this->namedtag->getInt("SpawnX"), $this->namedtag->getInt("SpawnY"), $this->namedtag->getInt("SpawnZ"), $level);
			}else{
				$this->spawnPosition = WeakPosition::fromObject($this->level->getSafeSpawn());
			}
		}

		$spawnPosition = $this->getSpawn();

		$pk = new StartGamePacket();
		$pk->actorUniqueId = $this->id;
		$pk->actorRuntimeId = $this->id;
		$pk->playerGamemode = Player::getClientFriendlyGamemode($this->gamemode);
		$pk->playerPosition = $this->add(0, $this->baseOffset + 0.001, 0); #BlameMojang
		$pk->pitch = $this->pitch;
		$pk->yaw = $this->yaw;
		$pk->seed = -1;
        $pk->dimension = $this->level->getDimension();
		$pk->worldGamemode = Player::getClientFriendlyGamemode($this->server->getGamemode());
		$pk->difficulty = $this->server->getDifficulty();
		$pk->spawnX = $spawnPosition->getFloorX();
		$pk->spawnY = $spawnPosition->getFloorY();
		$pk->spawnZ = $spawnPosition->getFloorZ();
		$pk->hasAchievementsDisabled = true;
		$pk->time = $this->level->getTime();
		$pk->rainLevel = 0; //TODO: implement these properly
		$pk->lightningLevel = 0;
		$pk->commandsEnabled = true;
		$pk->levelId = "";
		$pk->worldName = $this->server->getMotd();
		$pk->experiments = new Experiments([], false);
		$pk->playerMovementSettings = new PlayerMovementSettings(PlayerMovementType::LEGACY, 0, false);
        $pk->serverSoftwareVersion = sprintf("%s %s", NAME, VERSION);
		$pk->playerActorProperties = new CompoundTag();
		$pk->worldTemplateId = new UUID();
        $this->sendDataPacket($pk);

        $this->queueEncoded(StaticPacketCache::getAvailableActorIdentifiers($this->getProtocolVersion()));
        $this->queueEncoded(StaticPacketCache::getBiomeDefs($this->getProtocolVersion()));

		$ev = new PlayerLoginEvent($this, "Plugin reason");
		$ev->call();
		if($ev->isCancelled()){
			$this->close($this->getLeaveMessage(), $ev->getKickMessage());

			return;
		}

		$this->level->sendTime($this);

		$this->setFlying(false);
		$this->setAllowFlight($this->isCreative());

		$this->sendAttributes(true);
		$this->sendCommandData();
		$this->sendSettings();
		$this->sendPotionEffects($this);
		$this->sendData($this);

		$this->inventory->sendContents($this);
		$this->inventory->sendCreativeContents();
		$this->inventory->sendHeldItem($this);

        $this->level->getWeather()->sendWeather($this);

		$this->setNameTagVisible(true);
		$this->setNameTagAlwaysVisible(true);
		$this->setCanClimb(true);
		$this->setImmobile(true);

		$this->server->getLogger()->info($this->getServer()->getLanguage()->translateString("pocketmine.player.logIn", [
			TextFormat::AQUA . $this->username . TextFormat::WHITE,
			$this->ip,
			$this->port,
			$this->id,
			$this->level->getName(),
			round($this->x, 4),
			round($this->y, 4),
			round($this->z, 4),
			$this->clientVersion,
			$this->getProtocolVersion()
		]));

		if($this->isOp()){
			$this->setRemoveFormat(false);
		}

		$this->server->addOnlinePlayer($this);
		$this->server->checkPlayerOnline($this);

		$this->sendFullPlayerList();
	}

	protected function orderChunks(){
		if($this->connected === false or $this->viewDistance === -1 or !$this->doOrderChunks){
			return false;
		}

		Timings::$playerChunkOrderTimer->startTiming();

		$newOrder = [];
		$unloadChunks = $this->usedChunks;

		foreach($this->selectChunks() as $hash){
			if(!isset($this->usedChunks[$hash]) or $this->usedChunks[$hash] === false){
				$newOrder[$hash] = true;
			}
			unset($unloadChunks[$hash]);
		}

		foreach($unloadChunks as $index => $bool){
			Level::getXZ($index, $X, $Z);
			$this->unloadChunk($X, $Z);
		}

		$this->loadQueue = $newOrder;
		if(!empty($this->loadQueue) or !empty($unloadChunks)){
			$pk = new NetworkChunkPublisherUpdatePacket();
			$pk->x = $this->getFloorX();
			$pk->y = $this->getFloorY();
			$pk->z = $this->getFloorZ();
			$pk->radius = $this->viewDistance * 16; //blocks, not chunks >.>
			$this->sendDataPacket($pk);
		}

		Timings::$playerChunkOrderTimer->stopTiming();

		return true;
	}

	/**
	 * @internal
	 * Sends the player's gamemode to the client.
	 */
	public function sendGamemode(){
		$pk = new SetPlayerGameTypePacket();
		$pk->gamemode = Player::getClientFriendlyGamemode($this->gamemode);
		$this->sendDataPacket($pk);
	}

	/**
	 * Sends all the option flags
	 */
	public function sendSettings(){
		if($this->getProtocolVersion() > Protocol527Adapter::PROTOCOL_VERSION){
			//ALL of these need to be set for the base layer, otherwise the client will cry
			$boolAbilities = [
				UpdateAbilitiesPacketLayer::ABILITY_ALLOW_FLIGHT => $this->allowFlight,
				UpdateAbilitiesPacketLayer::ABILITY_FLYING => $this->flying,
				UpdateAbilitiesPacketLayer::ABILITY_NO_CLIP => $this->isSpectator(),
				UpdateAbilitiesPacketLayer::ABILITY_OPERATOR => $this->isOp(),
				UpdateAbilitiesPacketLayer::ABILITY_TELEPORT => $this->hasPermission("pocketmine.command.teleport"),
				UpdateAbilitiesPacketLayer::ABILITY_INVULNERABLE => $this->isCreative(),
				UpdateAbilitiesPacketLayer::ABILITY_MUTED => false,
				UpdateAbilitiesPacketLayer::ABILITY_WORLD_BUILDER => false,
				UpdateAbilitiesPacketLayer::ABILITY_INFINITE_RESOURCES => $this->isCreative(),
				UpdateAbilitiesPacketLayer::ABILITY_LIGHTNING => false,
				UpdateAbilitiesPacketLayer::ABILITY_BUILD => !$this->isSpectator(),
				UpdateAbilitiesPacketLayer::ABILITY_MINE => !$this->isSpectator(),
				UpdateAbilitiesPacketLayer::ABILITY_DOORS_AND_SWITCHES => !$this->isSpectator(),
				UpdateAbilitiesPacketLayer::ABILITY_OPEN_CONTAINERS => !$this->isSpectator(),
				UpdateAbilitiesPacketLayer::ABILITY_ATTACK_PLAYERS => !$this->isSpectator(),
				UpdateAbilitiesPacketLayer::ABILITY_ATTACK_MOBS => !$this->isSpectator(),
			];

			$pk = new UpdateAbilitiesPacket();
			$pk->commandPermission = ($this->isOp() ? CommandPermissions::OPERATOR : CommandPermissions::NORMAL);
			$pk->playerPermission = ($this->isOp() ? PlayerPermissions::OPERATOR : PlayerPermissions::MEMBER);
			$pk->targetActorUniqueId = $this->getId();
			$pk->abilityLayers = [
				//TODO: dynamic flying speed! FINALLY!!!!!!!!!!!!!!!!!
				new UpdateAbilitiesPacketLayer(UpdateAbilitiesPacketLayer::LAYER_BASE, $boolAbilities, 0.05, 0.1),
			];

			$this->sendDataPacket($pk);

			$pk = new UpdateAdventureSettingsPacket();
			$pk->noAttackingMobs = false;
			$pk->noAttackingPlayers = false;
			$pk->worldImmutable = false;
			$pk->showNameTags = true;
			$pk->autoJump = $this->autoJump;
			$this->sendDataPacket($pk);
		}else{
			$pk = new AdventureSettingsPacket();

			$pk->setFlag(AdventureSettingsPacket::WORLD_IMMUTABLE, $this->isSpectator());
			$pk->setFlag(AdventureSettingsPacket::NO_PVP, $this->isSpectator());
			$pk->setFlag(AdventureSettingsPacket::AUTO_JUMP, $this->autoJump);
			$pk->setFlag(AdventureSettingsPacket::ALLOW_FLIGHT, $this->allowFlight);
			$pk->setFlag(AdventureSettingsPacket::NO_CLIP, $this->isSpectator());
			$pk->setFlag(AdventureSettingsPacket::FLYING, $this->flying);

			$pk->commandPermission = ($this->isOp() ? CommandPermissions::OPERATOR : CommandPermissions::NORMAL);
			$pk->playerPermission = ($this->isOp() ? PlayerPermissions::OPERATOR : PlayerPermissions::MEMBER);
			$pk->actorUniqueId = $this->getId();

			$this->sendDataPacket($pk);
		}
	}

	protected function onTerrainReady() : void{
		$this->sendPlayStatus(PlayStatusPacket::PLAYER_SPAWN);
	}

	public function doFirstSpawn() : void{
		parent::doFirstSpawn();

		$this->setViewDistance($this->requestedViewDistance); //a hack
		$this->sendEncoded($this->server->getCraftingManager()->getCraftingDataPacket($this->getProtocolVersion()));
	}

	public function handleBedrockLevelSoundEvent(LevelSoundEventPacket $packet) : bool{
		//TODO: add events so plugins can change this
        if(
            (($packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE or $packet->sound === LevelSoundEventPacket::SOUND_ATTACK_STRONG) and $this->isSpectator()) or
            $packet->sound === LevelSoundEventPacket::SOUND_THROW or //Being sent by server itself
            $packet->sound === LevelSoundEventPacket::SOUND_NOTE
        ){
            return false;
        }

		if($this->level === null){
			return false;
		}

		static $soundIds = [
			LevelSoundEventPacket::SOUND_ITEM_USE_ON => MCPELevelSoundEventPacket::SOUND_ITEM_USE_ON,
			LevelSoundEventPacket::SOUND_HIT => MCPELevelSoundEventPacket::SOUND_HIT,
			LevelSoundEventPacket::SOUND_STEP => MCPELevelSoundEventPacket::SOUND_STEP,
			LevelSoundEventPacket::SOUND_JUMP => MCPELevelSoundEventPacket::SOUND_JUMP,
			LevelSoundEventPacket::SOUND_BREAK => MCPELevelSoundEventPacket::SOUND_BREAK,
			LevelSoundEventPacket::SOUND_PLACE => MCPELevelSoundEventPacket::SOUND_PLACE,
			LevelSoundEventPacket::SOUND_HEAVY_STEP => MCPELevelSoundEventPacket::SOUND_HEAVY_STEP,
			LevelSoundEventPacket::SOUND_GALLOP => MCPELevelSoundEventPacket::SOUND_GALLOP,
			LevelSoundEventPacket::SOUND_FALL => MCPELevelSoundEventPacket::SOUND_FALL,
			LevelSoundEventPacket::SOUND_AMBIENT => MCPELevelSoundEventPacket::SOUND_AMBIENT,
			LevelSoundEventPacket::SOUND_AMBIENT_BABY => MCPELevelSoundEventPacket::SOUND_AMBIENT_BABY,
			LevelSoundEventPacket::SOUND_AMBIENT_IN_WATER => MCPELevelSoundEventPacket::SOUND_AMBIENT_IN_WATER,
			LevelSoundEventPacket::SOUND_BREATHE => MCPELevelSoundEventPacket::SOUND_BREATHE,
			LevelSoundEventPacket::SOUND_DEATH => MCPELevelSoundEventPacket::SOUND_DEATH,
			LevelSoundEventPacket::SOUND_DEATH_IN_WATER => MCPELevelSoundEventPacket::SOUND_DEATH_IN_WATER,
			LevelSoundEventPacket::SOUND_DEATH_TO_ZOMBIE => MCPELevelSoundEventPacket::SOUND_DEATH_TO_ZOMBIE,
			LevelSoundEventPacket::SOUND_HURT => MCPELevelSoundEventPacket::SOUND_HURT,
			LevelSoundEventPacket::SOUND_HURT_IN_WATER => MCPELevelSoundEventPacket::SOUND_HURT_IN_WATER,
			LevelSoundEventPacket::SOUND_MAD => MCPELevelSoundEventPacket::SOUND_MAD,
			LevelSoundEventPacket::SOUND_BOOST => MCPELevelSoundEventPacket::SOUND_BOOST,
			LevelSoundEventPacket::SOUND_BOW => MCPELevelSoundEventPacket::SOUND_BOW,
			LevelSoundEventPacket::SOUND_SQUISH_BIG => MCPELevelSoundEventPacket::SOUND_SQUISH_BIG,
			LevelSoundEventPacket::SOUND_SQUISH_SMALL => MCPELevelSoundEventPacket::SOUND_SQUISH_SMALL,
			LevelSoundEventPacket::SOUND_FALL_BIG => MCPELevelSoundEventPacket::SOUND_FALL_BIG,
			LevelSoundEventPacket::SOUND_FALL_SMALL => MCPELevelSoundEventPacket::SOUND_FALL_SMALL,
			LevelSoundEventPacket::SOUND_SPLASH => MCPELevelSoundEventPacket::SOUND_SPLASH,
			LevelSoundEventPacket::SOUND_FIZZ => MCPELevelSoundEventPacket::SOUND_FIZZ,
			LevelSoundEventPacket::SOUND_FLAP => MCPELevelSoundEventPacket::SOUND_FLAP,
			LevelSoundEventPacket::SOUND_SWIM => MCPELevelSoundEventPacket::SOUND_SWIM,
			LevelSoundEventPacket::SOUND_DRINK => MCPELevelSoundEventPacket::SOUND_DRINK,
			LevelSoundEventPacket::SOUND_EAT => MCPELevelSoundEventPacket::SOUND_EAT,
			LevelSoundEventPacket::SOUND_TAKEOFF => MCPELevelSoundEventPacket::SOUND_TAKEOFF,
			LevelSoundEventPacket::SOUND_SHAKE => MCPELevelSoundEventPacket::SOUND_SHAKE,
			LevelSoundEventPacket::SOUND_PLOP => MCPELevelSoundEventPacket::SOUND_PLOP,
			LevelSoundEventPacket::SOUND_LAND => MCPELevelSoundEventPacket::SOUND_LAND,
			LevelSoundEventPacket::SOUND_SADDLE => MCPELevelSoundEventPacket::SOUND_SADDLE,
			LevelSoundEventPacket::SOUND_ARMOR => MCPELevelSoundEventPacket::SOUND_ARMOR,
			LevelSoundEventPacket::SOUND_ADD_CHEST => MCPELevelSoundEventPacket::SOUND_ADD_CHEST,
			LevelSoundEventPacket::SOUND_THROW => MCPELevelSoundEventPacket::SOUND_THROW,
			LevelSoundEventPacket::SOUND_ATTACK => MCPELevelSoundEventPacket::SOUND_ATTACK,
			LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE => MCPELevelSoundEventPacket::SOUND_ATTACK_NODAMAGE,
			LevelSoundEventPacket::SOUND_ATTACK_STRONG => MCPELevelSoundEventPacket::SOUND_ATTACK_NODAMAGE,
			LevelSoundEventPacket::SOUND_WARN => MCPELevelSoundEventPacket::SOUND_WARN,
			LevelSoundEventPacket::SOUND_SHEAR => MCPELevelSoundEventPacket::SOUND_SHEAR,
			LevelSoundEventPacket::SOUND_MILK => MCPELevelSoundEventPacket::SOUND_MILK,
			LevelSoundEventPacket::SOUND_THUNDER => MCPELevelSoundEventPacket::SOUND_THUNDER,
			LevelSoundEventPacket::SOUND_EXPLODE => MCPELevelSoundEventPacket::SOUND_EXPLODE,
			LevelSoundEventPacket::SOUND_FIRE => MCPELevelSoundEventPacket::SOUND_FIRE,
			LevelSoundEventPacket::SOUND_IGNITE => MCPELevelSoundEventPacket::SOUND_IGNITE,
			LevelSoundEventPacket::SOUND_FUSE => MCPELevelSoundEventPacket::SOUND_FUSE,
			LevelSoundEventPacket::SOUND_STARE => MCPELevelSoundEventPacket::SOUND_STARE,
			LevelSoundEventPacket::SOUND_SPAWN => MCPELevelSoundEventPacket::SOUND_SPAWN,
			LevelSoundEventPacket::SOUND_SHOOT => MCPELevelSoundEventPacket::SOUND_SHOOT,
			LevelSoundEventPacket::SOUND_BREAK_BLOCK => MCPELevelSoundEventPacket::SOUND_BREAK_BLOCK,
			LevelSoundEventPacket::SOUND_REMEDY => MCPELevelSoundEventPacket::SOUND_REMEDY,
			LevelSoundEventPacket::SOUND_UNFECT => MCPELevelSoundEventPacket::SOUND_UNFECT,
			LevelSoundEventPacket::SOUND_LEVELUP => MCPELevelSoundEventPacket::SOUND_LEVELUP,
			LevelSoundEventPacket::SOUND_BOW_HIT => MCPELevelSoundEventPacket::SOUND_BOW_HIT,
			LevelSoundEventPacket::SOUND_BULLET_HIT => MCPELevelSoundEventPacket::SOUND_BULLET_HIT,
			LevelSoundEventPacket::SOUND_EXTINGUISH_FIRE => MCPELevelSoundEventPacket::SOUND_EXTINGUISH_FIRE,
			LevelSoundEventPacket::SOUND_ITEM_FIZZ => MCPELevelSoundEventPacket::SOUND_ITEM_FIZZ,
			LevelSoundEventPacket::SOUND_CHEST_OPEN => MCPELevelSoundEventPacket::SOUND_CHEST_OPEN,
			LevelSoundEventPacket::SOUND_CHEST_CLOSED => MCPELevelSoundEventPacket::SOUND_CHEST_CLOSED,
			LevelSoundEventPacket::SOUND_SHULKERBOX_OPEN => MCPELevelSoundEventPacket::SOUND_SHULKERBOX_OPEN,
			LevelSoundEventPacket::SOUND_SHULKERBOX_CLOSED => MCPELevelSoundEventPacket::SOUND_SHULKERBOX_CLOSED,
			LevelSoundEventPacket::SOUND_POWER_ON => MCPELevelSoundEventPacket::SOUND_POWER_ON,
			LevelSoundEventPacket::SOUND_POWER_OFF => MCPELevelSoundEventPacket::SOUND_POWER_OFF,
			LevelSoundEventPacket::SOUND_ATTACH => MCPELevelSoundEventPacket::SOUND_ATTACH,
			LevelSoundEventPacket::SOUND_DETACH => MCPELevelSoundEventPacket::SOUND_DETACH,
			LevelSoundEventPacket::SOUND_DENY => MCPELevelSoundEventPacket::SOUND_DENY,
			LevelSoundEventPacket::SOUND_TRIPOD => MCPELevelSoundEventPacket::SOUND_TRIPOD,
			LevelSoundEventPacket::SOUND_POP => MCPELevelSoundEventPacket::SOUND_POP,
			LevelSoundEventPacket::SOUND_DROP_SLOT => MCPELevelSoundEventPacket::SOUND_DROP_SLOT,
			LevelSoundEventPacket::SOUND_NOTE => MCPELevelSoundEventPacket::SOUND_NOTE,
			LevelSoundEventPacket::SOUND_THORNS => MCPELevelSoundEventPacket::SOUND_THORNS,
			LevelSoundEventPacket::SOUND_PISTON_IN => MCPELevelSoundEventPacket::SOUND_PISTON_IN,
			LevelSoundEventPacket::SOUND_PISTON_OUT => MCPELevelSoundEventPacket::SOUND_PISTON_OUT,
			LevelSoundEventPacket::SOUND_PORTAL => MCPELevelSoundEventPacket::SOUND_PORTAL,
			LevelSoundEventPacket::SOUND_WATER => MCPELevelSoundEventPacket::SOUND_WATER,
			LevelSoundEventPacket::SOUND_LAVA_POP => MCPELevelSoundEventPacket::SOUND_LAVA_POP,
			LevelSoundEventPacket::SOUND_LAVA => MCPELevelSoundEventPacket::SOUND_LAVA,
			LevelSoundEventPacket::SOUND_BURP => MCPELevelSoundEventPacket::SOUND_BURP,
			LevelSoundEventPacket::SOUND_BUCKET_FILL_WATER => MCPELevelSoundEventPacket::SOUND_BUCKET_FILL_WATER,
			LevelSoundEventPacket::SOUND_BUCKET_FILL_LAVA => MCPELevelSoundEventPacket::SOUND_BUCKET_FILL_LAVA,
			LevelSoundEventPacket::SOUND_BUCKET_EMPTY_WATER => MCPELevelSoundEventPacket::SOUND_BUCKET_EMPTY_WATER,
			LevelSoundEventPacket::SOUND_BUCKET_EMPTY_LAVA => MCPELevelSoundEventPacket::SOUND_BUCKET_EMPTY_LAVA,
			LevelSoundEventPacket::SOUND_ELDERGUARDIAN_CURSE => MCPELevelSoundEventPacket::SOUND_ELDERGUARDIAN_CURSE,
			LevelSoundEventPacket::SOUND_MOB_WARNING => MCPELevelSoundEventPacket::SOUND_MOB_WARNING,
			LevelSoundEventPacket::SOUND_MOB_WARNING_BABY => MCPELevelSoundEventPacket::SOUND_MOB_WARNING_BABY,
			LevelSoundEventPacket::SOUND_TELEPORT => MCPELevelSoundEventPacket::SOUND_TELEPORT,
			LevelSoundEventPacket::SOUND_SHULKER_OPEN => MCPELevelSoundEventPacket::SOUND_SHULKER_OPEN,
			LevelSoundEventPacket::SOUND_SHULKER_CLOSE => MCPELevelSoundEventPacket::SOUND_SHULKER_CLOSE,
			LevelSoundEventPacket::SOUND_HAGGLE => MCPELevelSoundEventPacket::SOUND_HAGGLE,
			LevelSoundEventPacket::SOUND_HAGGLE_YES => MCPELevelSoundEventPacket::SOUND_HAGGLE_YES,
			LevelSoundEventPacket::SOUND_HAGGLE_NO => MCPELevelSoundEventPacket::SOUND_HAGGLE_NO,
			LevelSoundEventPacket::SOUND_HAGGLE_IDLE => MCPELevelSoundEventPacket::SOUND_HAGGLE_IDLE,
			LevelSoundEventPacket::SOUND_CHORUSGROW => MCPELevelSoundEventPacket::SOUND_CHORUSGROW,
			LevelSoundEventPacket::SOUND_CHORUSDEATH => MCPELevelSoundEventPacket::SOUND_CHORUSDEATH,
			LevelSoundEventPacket::SOUND_GLASS => MCPELevelSoundEventPacket::SOUND_GLASS,
			LevelSoundEventPacket::SOUND_CAST_SPELL => MCPELevelSoundEventPacket::SOUND_CAST_SPELL,
			LevelSoundEventPacket::SOUND_PREPARE_ATTACK => MCPELevelSoundEventPacket::SOUND_PREPARE_ATTACK,
			LevelSoundEventPacket::SOUND_PREPARE_SUMMON => MCPELevelSoundEventPacket::SOUND_PREPARE_SUMMON,
			LevelSoundEventPacket::SOUND_PREPARE_WOLOLO => MCPELevelSoundEventPacket::SOUND_PREPARE_WOLOLO,
			LevelSoundEventPacket::SOUND_FANG => MCPELevelSoundEventPacket::SOUND_FANG,
			LevelSoundEventPacket::SOUND_CHARGE => MCPELevelSoundEventPacket::SOUND_CHARGE,
			LevelSoundEventPacket::SOUND_CAMERA_TAKE_PICTURE => MCPELevelSoundEventPacket::SOUND_CAMERA_TAKE_PICTURE,
			LevelSoundEventPacket::SOUND_DEFAULT => MCPELevelSoundEventPacket::SOUND_DEFAULT,
			LevelSoundEventPacket::SOUND_UNDEFINED => MCPELevelSoundEventPacket::SOUND_UNDEFINED,
		];

		if(!isset($soundIds[$packet->sound]) or ($type = ActorMapping::getLegacyIdFromStringId($packet->actorType)) === -1){
			return false;
		}

		$this->sendDataPacket($packet);

		if(count($this->getViewers()) > 0){
			$pk = new MCPELevelSoundEventPacket();
			$pk->sound = $soundIds[$packet->sound];
			$pk->x = $packet->position->x;
			$pk->y = $packet->position->y;
			$pk->z = $packet->position->z;
			if($pk->sound === MCPELevelSoundEventPacket::SOUND_HIT){
				BlockPalette::getLegacyFromRuntimeId($packet->extraData, $id, $meta);
				$pk->extraData = $id;
			}else{
				$pk->extraData = $packet->extraData;
			}
			$pk->entityType = $type;
			$pk->isBabyMob = $packet->isBabyMob;
			$pk->disableRelativeVolume = $packet->disableRelativeVolume;

			$this->server->broadcastPacket($this->getViewers(), $pk);
		}
		return true;
	}

	public function handleActorEvent(ActorEventPacket $packet) : bool{
		if($this->spawned === false or !$this->isAlive()){
			return true;
		}
		$this->resetCrafting();

		switch($packet->event){
			case ActorEventPacket::EATING_ITEM:
				if($packet->data === 0){
					return false;
				}

				$currentTick = $this->server->getTick();
				if($currentTick - $this->lastEatingSound >= 4){ // duct tape for eating sound spam bug
					$this->sendDataPacket($packet);

					if(count($this->getViewers()) > 0){
						$pk = new MCPEEntityEventPacket();
						$pk->entityRuntimeId = $packet->entityRuntimeId;
						$pk->event = MCPEEntityEventPacket::EATING_ITEM;
						$pk->data = $packet->data >> 16 & 0x1ff;
						$this->server->broadcastPacket($this->getViewers(), $pk);
					}

					$this->lastEatingSound = $currentTick;
				}
				break;
			default:
				return false;
		}

		return true;
	}

	public function handleBedrockMobEquipment(MobEquipmentPacket $packet) : bool{
		if($this->spawned === false or !$this->isAlive()){
			return true;
		}

		switch($packet->windowId){
			case ContainerIds::INVENTORY:
				if(!$this->equipItem($packet->inventorySlot, $packet->hotbarSlot)){
					return true;
				}
				break;
			case ContainerIds::OFFHAND:
				$this->getTransactionQueue()->addTransaction(new BaseTransaction($this->offHandInventory, 0, $packet->item->stack));
				break;
			default:
				return false;
		}

		$this->setUsingItem(false);

		return true;
	}

	public function handleBedrockBlockPickRequest(BlockPickRequestPacket $packet) : bool{
		if($this->isCreative()){
			$block = $this->level->getBlockAt($packet->blockX, $packet->blockY, $packet->blockZ);

			$item = $block->getPickedItem();
			if($packet->addUserData){
				$tile = $this->getLevel()->getTileAt($packet->blockX, $packet->blockY, $packet->blockZ);
				if($tile instanceof Tile){
					$nbt = $tile->getCleanedNBT();
					if($nbt instanceof CompoundTag){
						$item->setCustomBlockData($nbt);
						$item->setLore(["+(DATA)"]);
					}
				}
			}

			for($i = 0; $i < PlayerInventory::HOTBAR_SIZE; ++$i){
				if($this->inventory->getItem($i)->equals($item)){
					$this->inventory->setHeldItemSlot($i);
					return true;
				}
			}

			$this->inventory->setItemInHand($item);
			return true;
		}

		return false;
	}

	public function toggleSprint(bool $sprint) : bool{
		if($sprint === $this->sprinting){
			return true;
		}
		$ev = new PlayerToggleSprintEvent($this, $sprint);
		$ev->call();
		if($ev->isCancelled()){
			$this->sendData($this);
			return false;
		}
		$this->setSprinting($sprint);
		return true;
	}

	public function toggleGlide(bool $glide) : bool{
		if ($glide === $this->gliding) return true;

		$ev = new PlayerToggleGlideEvent($this, $glide);
		$ev->call();

		if ($ev->isCancelled()) {
			$this->sendData($this);
			return false;
		}

		$this->setGliding($glide);
		return true;
	}

	public function toggleSneak(bool $sneak) : bool{
		if($sneak === $this->sneaking){
			return true;
		}
		$ev = new PlayerToggleSneakEvent($this, $sneak);
		$ev->call();
		if($ev->isCancelled()){
			$this->sendData($this);
			return false;
		}
		$this->setSneaking($sneak);
		return true;
	}

    public function handleBedrockPlayerActionFromData(int $action, Vector3 $blockPosition, int $face) : bool
    {
        if (!$this->spawned or (!$this->isAlive() and $action !== PlayerActionPacket::ACTION_RESPAWN and $action !== PlayerActionPacket::ACTION_DIMENSION_CHANGE_ACK)) {
            return true;
        }

        $pos = new Vector3($blockPosition->x, $blockPosition->y, $blockPosition->z);

        switch ($action) {
            case PlayerActionPacket::ACTION_START_BREAK:
                $this->attackBlock($pos, $face);
                break;

            /** @noinspection PhpMissingBreakStatementInspection */
            case PlayerActionPacket::ACTION_ABORT_BREAK:
            case PlayerActionPacket::ACTION_STOP_BREAK:
                if ($pos->distanceSquared($this) > 10000) {
                    break;
                }
                $this->level->broadcastLevelEvent($pos, MCPELevelEventPacket::EVENT_BLOCK_STOP_BREAK);
                break;
            case PlayerActionPacket::ACTION_STOP_SLEEPING:
                $this->stopSleep();
                break;
            case PlayerActionPacket::ACTION_RESPAWN:
                if ($this->isAlive() or !$this->isOnline()) {
                    break;
                }

                $this->respawn();
                break;
            case PlayerActionPacket::ACTION_DIMENSION_CHANGE_ACK:
                break;
            case PlayerActionPacket::ACTION_JUMP:
                $this->jump();
                return true;
            case PlayerActionPacket::ACTION_START_SPRINT:
                $this->toggleSprint(true);
                return true;
            case PlayerActionPacket::ACTION_STOP_SPRINT:
                $this->toggleSprint(false);
                return true;
            case PlayerActionPacket::ACTION_START_SNEAK:
                $this->toggleSneak(true);
                return true;
            case PlayerActionPacket::ACTION_STOP_SNEAK:
                $this->toggleSneak(false);
                return true;
            case PlayerActionPacket::ACTION_START_GLIDE:
                if ($this->armorInventory->getChestplate()->getId() !== Item::ELYTRA) {
                    $this->server->getLogger()->debug("Player " . $this->username . " tried to start glide without elytra");
                    $this->sendData($this);
                    $this->armorInventory->sendContents($this);
                    return false;
                }
                $this->toggleGlide(true);
                return true;
            case PlayerActionPacket::ACTION_STOP_GLIDE:
                $this->toggleGlide(false);
                return true;
            case PlayerActionPacket::ACTION_CRACK_BREAK:
                $block = $this->level->getBlock($pos);
                $this->level->broadcastLevelEvent($pos, MCPELevelEventPacket::EVENT_PARTICLE_PUNCH_BLOCK, $block->getId() | ($block->getDamage() << 8) | ($face << 16));
                break;
            case PlayerActionPacket::ACTION_INTERACT_BLOCK:
            case PlayerActionPacket::ACTION_START_ITEM_USE_ON:
            case PlayerActionPacket::ACTION_STOP_ITEM_USE_ON:
            case PlayerActionPacket::HANDLED_TELEPORT:
            case PlayerActionPacket::MISSED_SWING:
            case PlayerActionPacket::START_CRAWLING:
            case PlayerActionPacket::STOP_CRAWLING:
            case PlayerActionPacket::ACTION_CREATIVE_PLAYER_DESTROY_BLOCK:
                //TODO: do we need to handle this?
                break;
            default:
                $this->server->getLogger()->debug("Unhandled/unknown player action type " . $action . " from " . $this->getName());
                return false;
        }

        $this->setUsingItem(false);

        return true;
    }

    public function handleBedrockItemFrameDropItem(ItemFrameDropItemPacket $packet) : bool{
        if($this->spawned === false or !$this->isAlive()){
            return true;
        }

        $tile = $this->level->getBlockAt($packet->x, $packet->y, $packet->z);
        if($tile instanceof ItemFrame){
            $tile->onAttack(Item::air(), -1, $this);
        }

        return true;
    }

	public function setHealth(float $amount, bool $send = false) : void{
		parent::setHealth($amount);
        if ($send) {
            $this->sendAttributes();
        }
	}

	public function handleBedrockAnimate(AnimatePacket $packet) : bool{
		if($this->spawned === false or !$this->isAlive()){
			return true;
		}

		$ev = new PlayerAnimationEvent($this, $packet->action);
		$ev->call();
		if($ev->isCancelled()){
			return true;
		}

        if ($packet->action < 1 or $packet->action > 129){
            return true;
        }elseif($packet->rowingTime > 999){
            return true;
        }

		$pk = new MCPEAnimatePacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->action = $ev->getAnimationType();
        $pk->rowingTime = $packet->rowingTime;
		$this->server->broadcastPacket($this->getViewers(), $pk);

		return true;
	}

	public function handleBedrockBlockActorData(BlockActorDataPacket $packet) : bool{
		if($this->spawned === false or !$this->isAlive()){
			return true;
		}
		$this->resetCrafting();

		if($this->temporalVector->setComponents($packet->x, $packet->y, $packet->z)->distanceSquared($this) > 10000){
			return true;
		}

		$t = $this->level->getTileAt($packet->x, $packet->y, $packet->z);
		if($t instanceof Spawnable){
			$data = (new NetworkNbtSerializer())->read($packet->namedtag);
			if(!$t->updateCompoundTag($data->mustGetCompoundTag(), $this)){
				$t->spawnTo($this);
			}
		}

		return true;
	}

    public function handleBedrockPlayerInput(PlayerInputPacket $packet) : bool{
        if($this->spawned === false or !$this->isAlive()){
            return true;
        }
        if ($this->linkedEntity instanceof MinecartEmpty) {
            $this->linkedEntity->setCurrentSpeed($packet->motionY);
            return true;
        }
        return false;
    }

	public function handleBedrockSetPlayerGameType(SetPlayerGameTypePacket $packet) : bool{
		if($packet->gamemode !== $this->gamemode){
			//Set this back to default. TODO: handle this properly
			$this->sendGamemode();
			$this->sendSettings();
		}
		return true;
	}

	public function handleInventoryTransaction(InventoryTransactionPacket $packet) : bool{
		$result = true;

        if(count($packet->trData->getActions()) > 50){
            $this->server->getNetwork()->blockAddress($this->getAddress(), 300);
            return false;
        }
        if(count($packet->legacySetItemSlots) > 10){
            $this->server->getNetwork()->blockAddress($this->getAddress(), 300);
            return false;
        }

        $data = $packet->trData;
		if($data instanceof NormalTransactionData){
			$actions = [];
			foreach($data->getActions() as $action){
				if($action->oldItem->stack->equals($action->newItem->stack, true, true, true)){ // ???
					continue;
				}
				if(!$action->isFinalCraftingPart() and ($action->windowId === ContainerIds::FIXED_INVENTORY or $action->windowId === ContainerIds::UI or $action->windowId === ContainerIds::OFFHAND)){ // TODO: make proper inventory handling
					continue;
				}
				$actions[] = $action;
			}
			if(
				count($actions) === 2 and
				!$actions[0]->isFinalCraftingPart() and
				!$actions[1]->isFinalCraftingPart() and
				$actions[0]->sourceType === NetworkInventoryAction::SOURCE_CONTAINER and
				$actions[1]->sourceType === NetworkInventoryAction::SOURCE_CONTAINER and
				$actions[0]->oldItem->stack->equals($actions[1]->newItem->stack, true, true, true) and
				$actions[1]->oldItem->stack->equals($actions[0]->newItem->stack, true, true, true)
			){ //Swap items transaction
				switch($actions[0]->windowId){
					case ContainerIds::INVENTORY:
						$firstInventory = $this->inventory;
                        break;
					case ContainerIds::ARMOR:
						$firstInventory = $this->armorInventory;
                        break;
					default:
						if(!isset($this->windowIndex[$actions[0]->windowId])){
							return false; //unknown windowID and/or not matching any open windows
						}
						$firstInventory = $this->windowIndex[$actions[0]->windowId];
                        break;
				}
                $firstSlot = $actions[0]->inventorySlot;
                switch($actions[1]->windowId){
					case ContainerIds::INVENTORY:
						$secondInventory = $this->inventory;
                        break;
					case ContainerIds::ARMOR:
						$secondInventory = $this->armorInventory;
                        break;
					default:
						if(!isset($this->windowIndex[$actions[1]->windowId])){
							return false; //unknown windowID and/or not matching any open windows
						}
						$secondInventory = $this->windowIndex[$actions[1]->windowId];
                        break;
				}
                $secondSlot = $actions[1]->inventorySlot;
                $this->getTransactionQueue()->addTransaction(new SwapTransaction($firstInventory, $secondInventory, $firstSlot, $secondSlot));
				return true;
			}
			foreach($actions as $action){
				if($action->isFinalCraftingPart()){
					$possibleRecipes = $this->server->getCraftingManager()->getRecipesByResult($action->oldItem->stack);
					$recipe = null;
					$toRemove = [];
					$canCraft = true;
					$ingredients = [];
					$floatingInventory = null;
					foreach($possibleRecipes as $r){

						//Make a copy of the floating inventory that we can make changes to.
						$floatingInventory = clone $this->floatingInventory;
						$ingredients = $r->getIngredientList();

						//Check we have all the necessary ingredients.
						foreach($ingredients as $ingredient){
							if(!$floatingInventory->contains($ingredient)){
								//We're short on ingredients, try the next recipe
								$canCraft = false;
								continue 2;
							}
							$toRemove[] = $ingredient;
							$floatingInventory->removeItem($ingredient);
						}
						if($canCraft){
							//Found a recipe that works, take it and run with it.
							$recipe = $r;
							break;
						}
					}
					if($recipe !== null){
						$this->floatingInventory = $floatingInventory; //Set player crafting inv to the idea one created in this process
						$ev = new CraftItemEvent($this, $ingredients, $recipe);
						$ev->call();
						if($ev->isCancelled()){
							foreach($toRemove as $item){
								$this->inventory->addItem($item);
							}
							$this->inventory->sendContents($this);
							continue;
						}
						$this->floatingInventory->addItem(clone $recipe->getResult()); //Add the result to our picture of the crafting inventory
					}else{
						$this->server->getLogger()->debug("Unmatched desktop crafting recipe from player " . $this->getName());
						$this->inventory->sendContents($this);
					}
				}elseif($action->sourceType === NetworkInventoryAction::SOURCE_CONTAINER){
					switch($action->windowId){
						case ContainerIds::INVENTORY: //Normal inventory change
							if($action->inventorySlot >= $this->inventory->getSize()){
								return false;
							}

							$transaction = new BaseTransaction($this->inventory, $action->inventorySlot, $action->newItem->stack);
							break;
						case ContainerIds::ARMOR: //Armour change
							if($action->inventorySlot >= 4){
								return false;
							}

							$transaction = new BaseTransaction($this->armorInventory, $action->inventorySlot, $action->newItem->stack);
							break;
						default:
							if(!isset($this->windowIndex[$action->windowId])){
								return false; //unknown windowID and/or not matching any open windows
							}
							$inv = $this->windowIndex[$action->windowId];
							$transaction = new BaseTransaction($inv, $action->inventorySlot, $action->newItem->stack);
							break;
					}
					$this->getTransactionQueue()->addTransaction($transaction);
				}elseif($action->sourceType === NetworkInventoryAction::SOURCE_WORLD){
					if($action->newItem->stack->getId() !== Item::AIR){ //drop
						$this->getTransactionQueue()->addTransaction(new DropItemTransaction($action->newItem->stack));
					}
				}
			}
        }elseif($data instanceof MismatchTransactionData){
            if(count($packet->trData->getActions()) > 0){
                $this->server->getLogger()->debug("Expected 0 actions for mismatch, got " . count($packet->trData->getActions()) . ", " . json_encode($packet->trData->getActions()));
            }
            $this->setUsingItem(false);
            $this->sendAllInventories();
            return true;
		}elseif($data instanceof UseItemTransactionData){
            $blockVector = $packet->trData->getBlockPos();
            $face = $packet->trData->getFace();

			if($this->inventory->getHeldItemSlot() !== $data->getHotbarSlot()){
				$this->equipItem($data->getHotbarSlot(), $data->getHotbarSlot());
			}

			switch($data->getActionType()){
				case UseItemTransactionData::ACTION_CLICK_BLOCK:
					$item = $data->getItemInHand()->stack;
					if(!$this->isCreative() and !$item->equals($this->inventory->getItemInHand())){
						$this->inventory->sendHeldItem($this);
						return false;
					}

					$blockPos = $data->getBlockPos();
                    $block = $this->level->getBlockAt($blockPos->x, $blockPos->y, $blockPos->z);
					$clickPos = $data->getClickPos();

					//region antispamhack
					if((!$item->canBePlaced() and !$item instanceof FlintSteel and !$item instanceof SpawnEgg and ($this->isSneaking())) or $this->isAdventure() or $this->isSpectator()) {
                        //TODO: start hack for client spam bug
                        $spamBug = ($this->lastRightClickData !== null and
                            microtime(true) - $this->lastRightClickTime < 0.1 and //100ms
                            $this->lastRightClickData->getPlayerPos()->distanceSquared($data->getPlayerPos()) < 0.00001 and
                            $this->lastRightClickData->getBlockPos()->equals($data->getBlockPos()) and
                            $this->lastRightClickData->getClickPos()->distanceSquared($data->getClickPos()) < 0.00001 //signature spam bug has 0 distance, but allow some error
                        );
                        //get rid of continued spam if the player clicks and holds right-click
                        $this->lastRightClickData = $data;
                        $this->lastRightClickTime = microtime(true);
                        if ($spamBug) {
                            return true;
                        }
                    }
					//endregion antispamhack

					$this->useItem($blockPos, $clickPos, $data->getFace(), $item);
					break;
				case UseItemTransactionData::ACTION_BREAK_BLOCK:
					$result = $this->removeBlock($data->getBlockPos());
					break;
				case UseItemTransactionData::ACTION_CLICK_AIR:
					$item = $data->getItemInHand()->stack;
					if(!$this->isCreative() and !$item->equals($this->inventory->getItemInHand())){
						$this->inventory->sendHeldItem($this);
						return false;
					}

					if($this->isUsingItem()){
						if($item->canBeConsumed() and $this->consumeItem($item)){
							$action = CompletedUsingItemPacket::ACTION_CONSUME;
						}else{
							$action = CompletedUsingItemPacket::ACTION_UNKNOWN;
						}
						$this->completeUsingItem($item, $action);
						$this->setUsingItem(false);
					}else{
						$this->useItem($data->getBlockPos(), $data->getClickPos(), -1, $item);
					}
					break;
			}
		}elseif($data instanceof UseItemOnActorTransactionData){
			if($this->inventory->getHeldItemSlot() !== $data->getHotbarSlot()){
				$this->equipItem($data->getHotbarSlot(), $data->getHotbarSlot());
			}

			$this->resetCrafting();

			$target = $this->level->getEntity($data->getActorRuntimeId());
			if($target === null){
				return false;
			}

			switch($data->getActionType()) {
                case UseItemOnActorTransactionData::ACTION_ATTACK:
                    if ($target instanceof MinecartAbstract) { //TODO: Boat
                        if ($this->linkedEntity === $target) {
                            $target->setLinked(0, $this);
                        }
                        $target->flagForDespawn();
                    } else {
                        $this->attackEntity($target);
                    }
                    break;
                case UseItemOnActorTransactionData::ACTION_INTERACT:
                    if ($target instanceof Rideable) {
                        $this->linkEntity($target);
                    } else {
                        $this->interactEntity($target, $data->getClickPos());
                    }
                    break;
                default:
                    $this->server->getLogger()->debug("Unhandled/unknown interaction type " . $data->getActionType() . "received from " . $this->getName());

                    $result = false;
            }
		}elseif($data instanceof ReleaseItemTransactionData){
			if($this->inventory->getHeldItemSlot() !== $data->getHotbarSlot()){
				$this->equipItem($data->getHotbarSlot(), $data->getHotbarSlot());
			}

			switch($data->getActionType()){
				case ReleaseItemTransactionData::ACTION_RELEASE:
					$item = $data->getItemInHand()->stack;
					if($this->releaseItem($item)){
						$this->completeUsingItem($item, CompletedUsingItemPacket::ACTION_SHOOT);
					}
					break;
				case ReleaseItemTransactionData::ACTION_CONSUME:
					return false;
			}
			$this->setUsingItem(false);
		}

		if(!$result){
			$this->inventory->sendContents($this);
		}
		return $result;
	}

	public function completeUsingItem(Item $item, int $action) : void{
		$pk = new CompletedUsingItemPacket();
		$pk->itemId = $item->getId();
		$pk->action = $action;
		$this->sendDataPacket($pk);
	}

	public function handleBedrockRespawn(RespawnPacket $packet) : bool{
		if($this->isAlive()){
			return false;
		}

		if($packet->respawnState === RespawnPacket::STATE_CLIENT_READY_TO_SPAWN){
			$pk = new RespawnPacket();
			$pk->position = $this->getSpawn()->add(0, $this->baseOffset + 0.001, 0); //Blame Mojang
			$pk->respawnState = RespawnPacket::STATE_READY_TO_SPAWN;
			$pk->actorRuntimeId = $this->id;
			$this->sendDataPacket($pk);
		}
		return true;
	}

	public function handleEmote(EmotePacket $packet) : bool{
		if($packet->actorRuntimeId !== $this->id){
			return false;
		}

		$pk = new EmotePacket();
		$pk->actorRuntimeId = $this->id;
		$pk->emoteId = $packet->emoteId;
		$pk->xboxUserId = $packet->xboxUserId;
		$pk->platformChatId = $packet->platformChatId;
		$pk->flags |= EmotePacket::FLAG_SERVER_SIDE;

		BedrockUtils::splitPlayers($this->hasSpawned, $_, $bedrockPlayers);
		$this->server->broadcastPacket($bedrockPlayers, $pk);

		return true;
	}

	protected function checkNearEntities(){
		foreach($this->level->getNearbyEntities($this->boundingBox->grow(1, 0.5, 1), $this) as $entity){
			$entity->scheduleUpdate();

			if(!$entity->isAlive() or $entity->isFlaggedForDespawn()){
				continue;
			}

			if($entity instanceof Arrow and $entity->canBePickedUp()){
				$item = Item::get(Item::ARROW, $entity->getPotionId() + 1, 1);

				$ev = new InventoryPickupArrowEvent($this->inventory, $entity);
				if(!$this->inventory->canAddItem($item) or ($entity->getBow() !== null and $entity->getBow()->hasEnchantment(Enchantment::INFINITY))){
					$ev->setCancelled(true);
				}
				$ev->call();
				if($ev->isCancelled()){
					continue;
				}

				$pk = new MCPETakeItemEntityPacket();
				$pk->eid = $this->id;
				$pk->target = $entity->getId();
				$this->server->broadcastPacket($entity->getViewers(), $pk);

				$this->inventory->addItem(clone $item);
				$entity->flagForDespawn();
			}elseif($entity instanceof DroppedItem){
				if($entity->getPickupDelay() <= 0){
					$item = $entity->getItem();

					if($item instanceof Item){
						$ev = new InventoryPickupItemEvent($this->inventory, $entity);
						if(!$this->inventory->canAddItem($item)){
							$ev->setCancelled(true);
						}
						$ev->call();
						if($ev->isCancelled()){
							continue;
						}

						$pk = new MCPETakeItemEntityPacket();
						$pk->eid = $this->id;
						$pk->target = $entity->getId();
						$this->server->broadcastPacket($entity->getViewers(), $pk);

						$this->inventory->addItem(clone $item);

						$entity->flagForDespawn();
					}
				}
			}
		}
	}

	protected function switchLevel(Level $targetLevel) : bool{
        $oldLevel = $this->level;
        if(Human::switchLevel($targetLevel)){
            if($oldLevel !== null){
                foreach($this->usedChunks as $index => $d){
                    Level::getXZ($index, $X, $Z);
                    $this->unloadChunk($X, $Z, $oldLevel);
                }
            }

            $this->usedChunks = [];
            $this->loadQueue = [];

            $this->level->sendTime($this);
            $this->level->sendDifficulty($this);

            if($targetLevel->getDimension() != $oldLevel->getDimension()){
                $pk = new ChangeDimensionPacket();
                $pk->dimension = $targetLevel->getDimension();
                $pk->position = $this->asVector3();
                $this->sendDataPacket($pk);
            }

            $targetLevel->getWeather()->sendWeather($this);
            return true;
        }
        return false;
	}

	protected function unloadChunk($x, $z, Level $level = null){
		$level = $level ?? $this->level;
		$index = Level::chunkHash($x, $z);
		if(isset($this->usedChunks[$index])){
			if($this->usedChunks[$index]){ //The chunk was sent
				foreach($level->getChunkEntities($x, $z) as $entity){
					if($entity !== $this){
						$entity->despawnFrom($this);
					}
				}
			}else{ //There can still be a pending request
				BedrockChunkCache::getInstance($level, $this->getChunkProtocol())->unregister($this, $x, $z);
			}
            
            unset($this->activeChunkGenerationRequests[$index]);
			unset($this->usedChunks[$index]);
		}
		$level->unregisterChunkLoader($this, $x, $z);
		$level->unregisterChunkListener($this, $x, $z);
		unset($this->loadQueue[$index]);
	}

	public function setLevel(Level $level = null){
		parent::setLevel($level);

		if($this->level !== null){
			$this->chunkCache = BedrockChunkCache::getInstance($this->level, $this->getChunkProtocol());
		}
		return $this;
	}

	protected function getChunkProtocol() : int{
		if($this->protocolAdapter !== null){
			return $this->protocolAdapter->getChunkProtocol();
		}
		return ProtocolInfo::CURRENT_PROTOCOL;
	}

	/**
	 * Sends a popup message to the player
	 *
	 * TODO: add translation type popups
	 *
	 * @param string $message
	 * @param string $subtitle @deprecated
	 */
	public function sendPopup($message, $subtitle = ""){
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_POPUP;
		$pk->message = $message;
		$this->sendDataPacket($pk);
	}

	/**
	 * Adds a title text to the user's screen, with an optional subtitle.
	 *
	 * @param string $title
	 * @param string $subtitle
	 * @param int    $fadeIn Duration in ticks for fade-in. If -1 is given, client-sided defaults will be used.
	 * @param int    $stay Duration in ticks to stay on screen for
	 * @param int    $fadeOut Duration in ticks for fade-out.
	 */
	public function addTitle(string $title, string $subtitle = "", int $fadeIn = -1, int $stay = -1, int $fadeOut = -1){
		$this->resetTitles();
		$this->setTitleDuration($fadeIn, $stay, $fadeOut);
		if($subtitle !== ""){
			$this->addSubTitle($subtitle);
		}
		$this->sendTitleText($title === "" ? " " : $title, SetTitlePacket::TYPE_SET_TITLE);
	}

	/**
	 * Sets the subtitle message, without sending a title.
	 *
	 * @param string $subtitle
	 */
	public function addSubTitle(string $subtitle){
		$this->sendTitleText($subtitle === "" ? " " : $subtitle, SetTitlePacket::TYPE_SET_SUBTITLE);
	}

	/**
	 * Adds small text to the user's screen.
	 *
	 * @param string $message
	 */
	public function addActionBarMessage(string $message){
		$this->sendTitleText($message, SetTitlePacket::TYPE_SET_ACTIONBAR_MESSAGE);
	}

	/**
	 * Removes the title from the client's screen.
	 */
	public function removeTitles(){
		$pk = new SetTitlePacket();
		$pk->type = SetTitlePacket::TYPE_CLEAR_TITLE;
		$this->sendDataPacket($pk);
	}

	/**
	 * Resets the title duration settings.
	 */
	public function resetTitles(){
		$pk = new SetTitlePacket();
		$pk->type = SetTitlePacket::TYPE_RESET_TITLE;
		$this->sendDataPacket($pk);
	}

	/**
	 * Sets the title duration.
	 *
	 * @param int $fadeIn Title fade-in time in ticks.
	 * @param int $stay Title stay time in ticks.
	 * @param int $fadeOut Title fade-out time in ticks.
	 */
	public function setTitleDuration(int $fadeIn, int $stay, int $fadeOut){
		if($fadeIn >= 0 and $stay >= 0 and $fadeOut >= 0){
			$pk = new SetTitlePacket();
			$pk->type = SetTitlePacket::TYPE_SET_ANIMATION_TIMES;
			$pk->fadeInTime = $fadeIn;
			$pk->stayTime = $stay;
			$pk->fadeOutTime = $fadeOut;
			$this->sendDataPacket($pk);
		}
	}

	/**
	 * Internal function used for sending titles.
	 *
	 * @param string $title
	 * @param int    $type
	 */
	protected function sendTitleText(string $title, int $type){
		$pk = new SetTitlePacket();
		$pk->type = $type;
		$pk->text = $title;
		$this->sendDataPacket($pk);
	}

	public function sendCommandMessage($message, array $parameters = []){
		if($message instanceof TextContainer){
			if($message instanceof TranslationContainer){
				$parameters = array_merge($parameters, $message->getParameters());
			}
			$message = $message->getText();
		}
		$this->sendMessage($this->server->getLanguage()->translateString($message, $parameters));
	}

	/**
	 * Sends a Form to the player, or queue to send it if a form is already open.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function sendForm(Form $form) : void{
		$id = $this->formIdCounter++;
		$pk = new ModalFormRequestPacket;
		$pk->formId = $id;
		$pk->formData = json_encode($form, JSON_THROW_ON_ERROR);
		if($this->sendDataPacket($pk)){
			$this->forms[$id] = $form;
		}
	}

	public function onFormSubmit(int $formId, mixed $responseData) : bool{
		if(!isset($this->forms[$formId])){
			$this->getServer()->getLogger()->debug("Got unexpected response for form $formId");
			return false;
		}

		try{
			$this->forms[$formId]->handleResponse($this, $responseData);
		}catch(FormValidationException $e){
			$this->getServer()->getLogger()->critical("Failed to validate form " . get_class($this->forms[$formId]) . ": " . $e->getMessage());
			$this->getServer()->getLogger()->logException($e);
		}finally{
			unset($this->forms[$formId]);
		}

		return true;
	}

	/**
	 * Transfers a player to another server.
	 *
	 * @param string $address The IP address or hostname of the destination server
	 * @param int    $port    The destination port, defaults to 19132
	 * @param string $message Message to show in the console when closing the player
	 *
	 * @return bool if transfer was successful.
	 */
	public function transfer(string $address, int $port = 19132, string $message = "transfer") : bool{
		$ev = new PlayerTransferEvent($this, $address, $port, $message);
		$ev->call();

		if(!$ev->isCancelled()){
			$pk = new TransferPacket();
			$pk->address = $ev->getAddress();
			$pk->port = $ev->getPort();
			$this->sendDataPacket($pk, false, true);
			$this->flagForClose("", $ev->getMessage(), false);

			return true;
		}

		return false;
	}

    public function sendCommandData() : void{
        $pk = new AvailableCommandsPacket();
        foreach($this->server->getCommandMap()->getCommands() as $name => $command){
            if(isset($pk->commandData[$command->getName()]) or $command->getName() === "help" or !$command->isRegistered()){
                continue;
            }

            $data = $command->getCommandData();

            if ($command->convertMcpeDataToBedrock) {
                $data->overloads = [];

                foreach ($command->getMcpeCommandData()["overloads"] as $overload) {
                    $parameters = [];

                    $params = (isset($overload["input"]) ? $overload["input"]["parameters"] : $overload);

                    foreach ($params as $param) {
                        if ($param instanceof MCPECommandParameter) {
                            throw new \UnexpectedValueException("You can convert only the MCPEData represented as an array.");
                        }
                        $parameter = new CommandParameter();
                        $parameter->paramName = $param["name"];
                        $parameter->paramType = AvailableCommandsPacket::ARG_FLAG_VALID | AvailableCommandsPacket::argTypeFromString($param["type"]);
                        if($param["type"] === "stringenum"){
                            $parameter->enum = $enum = new CommandEnum();
                            $enum->enumName = $parameter->paramName;
                            $enum->enumValues = $param["enum_values"];
                        }
                        $parameter->isOptional = $param["isOptional"] ?? false;
                        $parameters[] = $parameter;
                    }

                    $data->overloads[] = new CommandOverload(false, $parameters);
                }
            }

            $aliases = $command->getAliases();
            if(!empty($aliases)){
                if(!in_array($data->commandName, $aliases, true)){
                    //work around a client bug which makes the original name not show when aliases are used
                    $aliases[] = $data->commandName;
                }
                $data->aliases = new CommandEnum();
                $data->aliases->enumName = ucfirst($command->getName()) . "Aliases";
                $data->aliases->enumValues = $aliases;
            }

            $pk->commandData[$command->getName()] = $data;
        }

        $this->sendDataPacket($pk);
    }

	protected function sendRespawnPacket(Vector3 $pos) : void{
		$pk = new RespawnPacket();
		$pk->position = $pos->add(0, $this->baseOffset + 0.001, 0); //Blame Mojang
		$pk->respawnState = RespawnPacket::STATE_SEARCHING_FOR_SPAWN;
		$pk->actorRuntimeId = $this->id;
		$this->sendDataPacket($pk);
	}

	public function sendDisconnect(string $reason = "") : void{
		$pk = new DisconnectPacket();
		$pk->message = $reason;
		$this->sendDataPacket($pk, false, true);
	}

	public function sendPlayStatus(int $status, bool $immediate = false){
		$pk = new PlayStatusPacket();
		$pk->status = $status;
		$this->sendDataPacket($pk, false, $immediate);
	}

	/**
	 * @param DataPacket $packet
	 * @param bool       $needACK
	 * @param bool       $immediate
	 *
	 * @return int|bool
	 */
	public function sendDataPacket(DataPacket $packet, bool $needACK = false, bool $immediate = false){
		if(!$this->connected){
			return false;
		}

		if($packet instanceof BatchPacket){
			return true;
		}

		if($packet instanceof BedrockPacket){
			if($this->protocolAdapter !== null){
				$packet = $this->protocolAdapter->processServerToClient($packet);
			}
		}else {
            $packet = PacketTranslator::translate($packet); //try to translate this packet to client, otherwise ignore

            if ($packet !== null) {
                if ($this->protocolAdapter !== null) {
                    $packet = $this->protocolAdapter->processServerToClient($packet);
                }
            }
        }

        if($packet === null){
            return true;
        }

		//Basic safety restriction. TODO: improve this
		if(!$this->loggedIn and !$packet->canBeSentBeforeLogin()){
			throw new \InvalidArgumentException("Attempted to send " . get_class($packet) . " to " . $this->getName() . " too early");
		}

		$timings = Timings::getSendDataPacketTimings($packet);
		$timings->startTiming();

		$ev = new DataPacketSendEvent($this, $packet);
		$ev->call();
		if($ev->isCancelled()){
			$timings->stopTiming();
			return false;
		}

		if(!$packet->isEncoded){
			$packet->encode();
		}

		$stream = new BedrockPacketBatch();
		$stream->putPacket($packet);

		if(NetworkCompression::$THRESHOLD >= 0 and strlen($stream->buffer) >= NetworkCompression::$THRESHOLD){
			$compressionLevel = NetworkCompression::$LEVEL;
			$forceSync = false;
		}else{
			$compressionLevel = 0;
			$forceSync = true;
		}

		if($immediate){
			// Skip any queues
			if($this->enableCompression){
				$this->sendEncoded(ProtocolInfo::MCPE_RAKNET_PACKET_ID . NetworkCompression::compress($stream->buffer, $compressionLevel), false, true);
			}else{
				$this->sendEncoded(ProtocolInfo::MCPE_RAKNET_PACKET_ID . $stream->buffer, false, true);
			}
			return true;
		}

		$promise = new CompressBatchPromise();
		$this->batchQueue->push($promise);

		$promise->onResolve(function(CompressBatchPromise $promise) : void{
			if($this->connected and $this->batchQueue->bottom() === $promise){
				$this->batchQueue->dequeue(); //result unused
				$this->sendEncoded(ProtocolInfo::MCPE_RAKNET_PACKET_ID . $promise->getResult());

				while(!$this->batchQueue->isEmpty()){
					/** @var CompressBatchPromise $current */
					$current = $this->batchQueue->bottom();
					if($current->hasResult()){
						$this->batchQueue->dequeue();

						$this->sendEncoded(ProtocolInfo::MCPE_RAKNET_PACKET_ID . $current->getResult());
					}else{
						//can't send any more queued until this one is ready
						break;
					}
				}
			}
		});

		if($this->enableCompression){
			if(!$forceSync and !$immediate and $this->server->isNetworkCompressionAsync()){
				$task = new CompressBatchTask($stream->buffer, $compressionLevel, $promise);
				$this->server->getScheduler()->scheduleAsyncTask($task);
			}else{
				$promise->resolve(NetworkCompression::compress($stream->buffer, $compressionLevel));
			}
		}else{
			$promise->resolve($stream->buffer);
		}

		$timings->stopTiming();
		return true;
	}

    public function sendEncoded(string $payload, bool $needACK = false, bool $immediate = false, int $compression = CompressionAlgorithm::ZLIB){
        if(!$this->connected){
            return false;
        }
        $ev = new RawPacketSendEvent($this, $payload);
        $ev->call();
        if($ev->isCancelled()){
            return false;
        }

        if($this->getProtocolVersion() >= Protocol649Adapter::PROTOCOL_VERSION) {
            $rawBuffer = substr($payload, 1);
            $payload = ProtocolInfo::MCPE_RAKNET_PACKET_ID . chr($compression) . $rawBuffer;
        }elseif($this->getProtocolVersion() <= Protocol390Adapter::PROTOCOL_VERSION){
            $rawBuffer = substr($payload, 1);
            $payloadDecoded = NetworkCompression::decompress($rawBuffer);
            if($payloadDecoded === "") {
                return false;
            }
            $payload = ProtocolInfo::MCPE_RAKNET_PACKET_ID . McpeNetworkCompression::compress($payloadDecoded);
        }

        $identifier = $this->interface->putBuffer($this, $payload, $needACK, $immediate);
        if($needACK and $identifier !== null){
            $this->needACK[$identifier] = false;
            return $identifier;
        }
        return true;
    }

	public function queueEncoded(string $data) : bool{
		if(!$this->connected){
			return false;
		}

		$payload = new CompressBatchPromise();
		$payload->resolve($data);
		$this->batchQueue->push($payload);

		$payload->onResolve(function(CompressBatchPromise $promise) : void{
			if($this->connected and $this->batchQueue->bottom() === $promise){
				$this->batchQueue->dequeue(); //result unused
				$this->sendEncoded(ProtocolInfo::MCPE_RAKNET_PACKET_ID . $promise->getResult());

				while(!$this->batchQueue->isEmpty()){
					/** @var CompressBatchPromise $current */
					$current = $this->batchQueue->bottom();
					if($current->hasResult()){
						$this->batchQueue->dequeue();

						$this->sendEncoded(ProtocolInfo::MCPE_RAKNET_PACKET_ID . $current->getResult());
					}else{
						//can't send any more queued until this one is ready
						break;
					}
				}
			}
		});

		return true;
	}

	/**
	 * Sends empty cursor to the Bedrock Player.
	 */
	public function clearCursor() : void{
		$pk = new InventorySlotPacket();
		$pk->windowId = ContainerIds::UI;
		$pk->inventorySlot = 0; // Cursor index
		$pk->item = ItemInstance::legacy(Item::get(Item::AIR, 0, 0));
		$this->sendDataPacket($pk);
	}

	/**
	 * @return string
	 */
	public function getXUID() : string{
		return $this->xuid;
	}

	/**
	 * @return string
	 */
	public function getPlatformOnlineId() : string{
		return $this->platformOnlineId;
	}

	/**
	 * @return UUID
	 */
	public function getDeviceId() : UUID{
		return $this->deviceId;
	}

	/**
	 * @param Player $player
	 */
	public function updatePlayerList(Player $player) : void{
		$this->sendPlayerList([$player]);
	}

	public function sendFullPlayerList() : void{
		$this->sendPlayerList($this->server->getOnlinePlayers());
	}

	/**
	 * @param Player[] $players
	 */
	public function sendPlayerList(array $players) : void{
		$pk = new PlayerListPacket();
		$pk->type = PlayerListPacket::TYPE_ADD;
		foreach($players as $player){
			$pk->entries[] = PlayerListEntry::createAdditionEntry(
				$player->getUniqueId(),
				$player->getId(),
				$player->getDisplayName(),
				$player->getSkin()->getBedrockSkin(),
				$player instanceof BedrockPlayer ? $player->getXUID() : "",
				$player instanceof BedrockPlayer ? $player->getPlatformOnlineId() : "",
				$player->getDeviceOS()
			);
		}
		$this->sendDataPacket($pk);
	}

	/**
	 * @param UUID $uuid
	 */
	public function removePlayerList(UUID $uuid) : void{
		$pk = new PlayerListPacket();
		$pk->type = PlayerListPacket::TYPE_REMOVE;
		$pk->entries = [PlayerListEntry::createRemovalEntry($uuid)];
		$this->sendDataPacket($pk);
	}

	/**
	 * @return int
	 */
	public function getProtocolVersion() : int{
		if($this->protocolAdapter !== null){
			return $this->protocolAdapter->getProtocolVersion();
		}
		return ProtocolInfo::CURRENT_PROTOCOL;
	}

	/**
	 * @return ProtocolAdapter|null
	 */
	public function getProtocolAdapter() : ?ProtocolAdapter{
		return $this->protocolAdapter;
	}
}


