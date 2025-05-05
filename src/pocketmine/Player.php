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

use InvalidArgumentException;
use InvalidStateException;
use pocketmine\block\Bed;
use pocketmine\block\Block;
use pocketmine\block\ItemFrame;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Living;
use pocketmine\entity\object\Item as DroppedItem;
use pocketmine\entity\object\MinecartEmpty;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\FishingHook;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryPickupArrowEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\player\PlayerAnimationEvent;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerBedLeaveEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerPreMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerToggleFlightEvent;
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
use pocketmine\inventory\AnvilInventory;
use pocketmine\inventory\BaseTransaction;
use pocketmine\inventory\BigShapedRecipe;
use pocketmine\inventory\BigShapelessRecipe;
use pocketmine\inventory\DropItemTransaction;
use pocketmine\inventory\FloatingInventory;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\inventory\ShapelessRecipe;
use pocketmine\inventory\SimpleTransactionQueue;
use pocketmine\inventory\SwapTransaction;
use pocketmine\inventory\Transaction;
use pocketmine\item\Durable;
use pocketmine\item\Elytra;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\MeleeWeaponEnchantment;
use pocketmine\item\Item;
use pocketmine\level\ChunkListener;
use pocketmine\level\ChunkLoader;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\level\WeakPosition;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\metadata\MetadataValue;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\CompressBatchPromise;
use pocketmine\network\mcpe\chunk\MCPEChunkCache;
use pocketmine\network\mcpe\CompressBatchTask;
use pocketmine\network\mcpe\encryption\EncryptionContext;
use pocketmine\network\mcpe\encryption\PrepareEncryptionTask;
use pocketmine\network\mcpe\MCPEPacketBatch;
use pocketmine\network\mcpe\NetworkCompression;
use pocketmine\network\mcpe\NetworkNbtSerializer;
use pocketmine\network\mcpe\PlayerNetworkSessionAdapter;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\BlockEntityDataPacket;
use pocketmine\network\mcpe\protocol\BlockPickRequestPacket;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\ChunkRadiusUpdatedPacket;
use pocketmine\network\mcpe\protocol\ContainerSetSlotPacket;
use pocketmine\network\mcpe\protocol\CraftingEventPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\DropItemPacket;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\network\mcpe\protocol\ItemFrameDropItemPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\MapInfoRequestPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerInputPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\ResourcePackChunkDataPacket;
use pocketmine\network\mcpe\protocol\ResourcePackChunkRequestPacket;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\ResourcePackDataInfoPacket;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\RespawnPacket;
use pocketmine\network\mcpe\protocol\ServerToClientHandshakePacket;
use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\network\mcpe\protocol\SpawnExperienceOrbPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\TakeItemEntityPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandOverload;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\InputModeIds;
use pocketmine\network\mcpe\protocol\types\OS;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\mcpe\VerifyLoginTask;
use pocketmine\network\NetworkChunkCache;
use pocketmine\network\NetworkInterface;
use pocketmine\permission\PermissibleBase;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionAttachment;
use pocketmine\permission\PermissionAttachmentInfo;
use pocketmine\plugin\Plugin;
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\tile\Spawnable;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;
use pocketmine\utils\UUID;
use SplQueue;
use function abs;
use function array_fill;
use function assert;
use function ceil;
use function cos;
use function count;
use function explode;
use function floor;
use function get_class;
use function implode;
use function is_int;
use function log;
use function max;
use function mb_strlen;
use function microtime;
use function min;
use function preg_match;
use function round;
use function sin;
use function strlen;
use function strtolower;
use function substr;
use function time;
use function trim;
use const M_PI;
use const M_SQRT3;

/**
 * Main class that handles networking, recovery, and packet sending to the server part
 */
class Player extends Human implements CommandSender, ChunkLoader, ChunkListener, IPlayer
{

    public const int SURVIVAL = 0;
    public const int CREATIVE = 1;
    public const int ADVENTURE = 2;
    public const int SPECTATOR = 3;

    public const int CRAFTING_SMALL = 0;
    public const int CRAFTING_BIG = 1;
    public const int CRAFTING_ANVIL = 2;
    public const int CRAFTING_ENCHANT = 3;

    //TODO: HACK!
    //these IDs are used for 1.16 to restore 1.14ish crafting & inventory behaviour; since they don't seem to have any
    //effect on the behaviour of inventory transactions I don't currently plan to integrate these into the main system.
    private const int RESERVED_WINDOW_ID_RANGE_START = ContainerIds::LAST - 10;
    private const int RESERVED_WINDOW_ID_RANGE_END = ContainerIds::LAST;

    protected const int PACK_CHUNK_SIZE = 128 * 1024; //128KB

    private const int MOVES_PER_TICK = 2;
    private const int MOVE_BACKLOG_SIZE = 100 * self::MOVES_PER_TICK; //100 ticks backlog (5 seconds)


    /**
     * Checks a supplied username and checks it is valid.
     * @param string $name
     *
     * @return bool
     */
    public static function isValidUserName(string $name): bool
    {
        $lname = strtolower($name);
        $len = strlen($name);
        return $lname !== "rcon" and $lname !== "console" and $len >= 2 and $len <= 16 and preg_match("/[^A-Za-z0-9_ ]/", $name) === 0 and trim($lname) !== "" and trim($lname) === $lname;
    }

    /** @var NetworkInterface */
    protected $interface;

    /**
     * @var PlayerNetworkSessionAdapter
     * TODO: remove this once player and network are divorced properly
     */
    public $sessionAdapter;

    /** @var bool */
    public $playedBefore;
    public $spawned = false;
    public $loggedIn = false;
    public $loginProcessed = false;
    public $joined = false;
    public $gamemode;

    protected int $windowCnt = 2;
    /** @var int[] */
    protected array $windows = [];
    /** @var Inventory[] */
    protected array $windowIndex = [];
    /** @var bool[] */
    protected array $permanentWindows = [];

    /** @var bool */
    public bool $keepMovement = true;

    protected $messageCounter = 2;

    protected $removeBlockCounter = 0;
    protected $removeBlockLast = 0;

    /** @var Vector3 */
    public $speed = null;

    public $craftingType = self::CRAFTING_SMALL; //0 = 2x2 crafting, 1 = 3x3 crafting, 2 = anvil, 3 = enchanting

    public $creationTime = 0;

    protected $randomClientId;

    protected $connected = true;
    protected $ip;
    protected $removeFormat = true;
    protected $port;
    protected $username;
    protected $iusername;
    protected $displayName;
    protected $languageCode = "en_US";
    protected $clientVersion = "";
    protected $deviceModel = "";
    protected $deviceOS = -1;
    protected $currentInputMode = -1;
    protected $defaultInputMode = -1;
    protected $uiProfile = -1;
    protected $xboxAuthenticated = false;
    protected $ping = 0;
    protected $startAction = -1.0;
    /** @var Vector3|null */
    protected $sleeping = null;
    protected $clientID = null;

    protected $loaderId = 0;

    protected float $stepHeight = 0.6;

    private array $activeChunkGenerationRequests = [];
    public array $usedChunks = [];
    protected array $loadQueue = [];
    protected $nextChunkOrderRun = 5;
    /** @var bool */
    protected $doOrderChunks = true;

    /** @var int */
    protected $chunkHack = 0;

    /** @var Player[] */
    protected $hiddenPlayers = [];

    /** @var int */
    protected $moveRateLimit = 10 * self::MOVES_PER_TICK;
    /** @var float|null */
    protected $lastMovementProcess = null;
    /** @var Vector3|null */
    protected $forceMoveSync = null;

    protected $viewDistance = -1;
    protected $chunksPerTick;
    protected $spawnThreshold;
    protected $spawnChunkLoadCount = 0;
    /** @var null|WeakPosition */
    protected $spawnPosition = null;

    protected $inAirTicks = 0;
    protected $startAirTicks = 5;

    //TODO: Abilities
    protected $autoJump = true;
    protected $allowFlight = false;
    protected $flying = false;

    protected $respawnLocked = false;

    protected $needACK = [];

    protected $batchedPackets = [];

    /** @var \SplQueue */
    protected $batchQueue;

    protected $cipher = null;

    /** @var PermissibleBase */
    protected $perm = null;

    /** @var int|null */
    protected $lineHeight = null;

    /** @var \SplFixedArray */
    protected $flagForClose;

    /** @var array */
    protected $customData = [];

    /** @var int[] ID => ticks map */
    protected $usedItemsCooldown = [];

    /** @var NetworkChunkCache */
    protected $chunkCache;

    /** @var FishingHook|null */
    protected $fishingHook;
    /** @var bool */
    public $awaitingEncryptionHandshake = false;

    /** @var bool[][] uuid => [chunk index => hasSent] */
    protected $downloadedChunks = [];

    private bool $isValidSession;

    public $weatherData = [0, 0, 0];

    protected int $currentWindowType = WindowTypes::CONTAINER;

    protected ?SimpleTransactionQueue $transactionQueue = null;

    /**
     * @return TranslationContainer|string
     */
    public function getLeaveMessage()
    {
        if ($this->joined) {
            return new TranslationContainer(TextFormat::YELLOW . "%multiplayer.player.left", [
                $this->getDisplayName()
            ]);
        }

        return "";
    }

    /**
     * @return string
     */
    public function getClientVersion(): string
    {
        return $this->clientVersion;
    }

    public function getClientId()
    {
        return $this->randomClientId;
    }

    public function isBanned(): bool
    {
        return $this->server->getNameBans()->isBanned($this->iusername);
    }

    public function setBanned(bool $value)
    {
        if ($value === true) {
            $this->server->getNameBans()->addBan($this->getName(), null, null, null);
            $this->kick("You have been banned");
        } else {
            $this->server->getNameBans()->remove($this->getName());
        }
    }

    public function isWhitelisted(): bool
    {
        return $this->server->isWhitelisted($this->iusername);
    }

    public function setWhitelisted(bool $value)
    {
        if ($value === true) {
            $this->server->addWhitelist($this->iusername);
        } else {
            $this->server->removeWhitelist($this->iusername);
        }
    }

    public function getPlayer()
    {
        return $this;
    }

    public function getFirstPlayed()
    {
        return $this->namedtag instanceof CompoundTag ? $this->namedtag->getLong("firstPlayed", 0) : null;
    }

    public function getLastPlayed()
    {
        return $this->namedtag instanceof CompoundTag ? $this->namedtag->getLong("lastPlayed", 0) : null;
    }

    public function hasPlayedBefore(): bool
    {
        return $this->playedBefore;
    }

    public function setAllowFlight(bool $value)
    {
        $this->allowFlight = $value;
        $this->sendSettings();
    }

    public function getAllowFlight(): bool
    {
        return $this->allowFlight;
    }

    public function setFlying(bool $value)
    {
        $this->flying = $value;
        $this->sendSettings();
    }

    public function isFlying(): bool
    {
        return $this->flying;
    }

    public function setAutoJump($value)
    {
        $this->autoJump = $value;
        $this->sendSettings();
    }

    public function hasAutoJump()
    {
        return $this->autoJump;
    }

    /**
     * @param Player $player
     */
    public function spawnTo(Player $player): void
    {
        if ($this->spawned and $player->spawned and $this->isAlive() and $player->isAlive() and $player->getLevel() === $this->level and $player->canSee($this) and !$this->isSpectator()) {
            parent::spawnTo($player);
        }
    }

    /**
     * @return bool
     */
    public function getRemoveFormat(): bool
    {
        return $this->removeFormat;
    }

    /**
     * @param bool $remove
     */
    public function setRemoveFormat($remove = true)
    {
        $this->removeFormat = (bool)$remove;
    }

    public function getScreenLineHeight(): int
    {
        return $this->lineHeight ?? 7;
    }

    public function setScreenLineHeight(int $height = null)
    {
        if ($height !== null and $height < 1) {
            throw new \InvalidArgumentException("Line height must be at least 1");
        }
        $this->lineHeight = $height;
    }

    /**
     * @param Player $player
     *
     * @return bool
     */
    public function canSee(Player $player): bool
    {
        return !isset($this->hiddenPlayers[$player->getRawUniqueId()]) and !$player->isSpectator();
    }

    /**
     * @return int
     */
    public function getNextChunkOrderRun(): int
    {
        return $this->nextChunkOrderRun;
    }

    /**
     * @param int
     */
    public function setNextChunkOrderRun(int $nextChunkOrderRun): void
    {
        $this->nextChunkOrderRun = $nextChunkOrderRun;
    }

    /**
     * @return bool
     */
    public function isOrderChunks(): bool
    {
        return $this->doOrderChunks;
    }

    /**
     * @param bool $value
     */
    public function setOrderChunks(bool $value): void
    {
        $this->doOrderChunks = $value;
    }

    public function collectArrays(): void
    {
        Utils::reallocateArray($this->loadQueue);
        Utils::reallocateArray($this->usedChunks);
        Utils::reallocateArray($this->hiddenPlayers);
        Utils::reallocateArray($this->windowIndex);
        Utils::reallocateArray($this->usedItemsCooldown);
        Utils::reallocateArray($this->customData);
    }

    /**
     * @param Player $player
     */
    public function hidePlayer(Player $player)
    {
        if ($player === $this) {
            return;
        }
        $this->hiddenPlayers[$player->getRawUniqueId()] = $player;
        $player->despawnFrom($this);
    }

    /**
     * @param Player $player
     */
    public function showPlayer(Player $player)
    {
        if ($player === $this) {
            return;
        }
        unset($this->hiddenPlayers[$player->getRawUniqueId()]);
        if ($player->isOnline()) {
            $player->spawnTo($this);
        }
    }

    /**
     * @return Player[]
     */
    public function getHiddenPlayers(): array
    {
        return $this->hiddenPlayers;
    }

    public function canCollideWith(Entity $entity): bool
    {
        return false;
    }

    public function resetFallDistance(): void
    {
        parent::resetFallDistance();
        $this->resetAirTicks();
    }

    public function resetAirTicks()
    {
        if ($this->inAirTicks !== 0) {
            $this->startAirTicks = 5;
        }
        $this->inAirTicks = 0;
    }

    public function getViewDistance(): int
    {
        return $this->viewDistance;
    }

    public function setViewDistance(int $distance){
        $this->viewDistance = $this->server->getAllowedViewDistance($distance);

        $this->spawnThreshold = (int) (min($this->viewDistance, $this->server->getProperty("chunk-sending.spawn-radius", 4)) ** 2 * M_PI);

        $this->nextChunkOrderRun = 0;

        $pk = new ChunkRadiusUpdatedPacket();
        $pk->radius = $this->viewDistance;
        $this->sendDataPacket($pk);

        $this->server->getLogger()->debug("Setting view distance for " . $this->getName() . " to " . $this->viewDistance . " (requested " . $distance . ")");
    }

    public function isOnline() : bool{
        return $this->isConnected() and $this->loggedIn;
    }

    public function isOp() : bool{
        return $this->server->isOp($this->getName());
    }

    /**
     * @param bool $value
     */
    public function setOp(bool $value){
        if($value === $this->isOp()){
            return;
        }

        if($value){
            $this->server->addOp($this->getName());
        }else{
            $this->server->removeOp($this->getName());
        }

        $this->sendSettings();
    }

    /**
     * @return bool
     */
    public function isBedrock(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isDesktop(): bool
    {
        return $this->deviceOS === OS::WINDOWS_10 or $this->deviceOS === OS::UNKNOWN or $this->deviceOS === OS::MACOS or $this->deviceModel === "Linux" or $this->currentInputMode === InputModeIds::MOUSE;
    }

    /**
     * @param permission\Permission|string $name
     *
     * @return bool
     */
    public function isPermissionSet($name): bool
    {
        return $this->perm->isPermissionSet($name);
    }


    /**
     * @param permission\Permission|string $name
     *
     * @return bool
     *
     * @throws \InvalidStateException if the player is closed
     */
    public function hasPermission(Permission|string $name): bool
    {
        if ($this->closed) {
            throw new \InvalidStateException("Trying to get permissions of closed player");
        }
        return $this->perm->hasPermission($name);
    }

    /**
     * @param Plugin $plugin
     * @param string $name
     * @param bool $value
     *
     * @return PermissionAttachment
     */
    public function addAttachment(Plugin $plugin, string $name = null, bool $value = null): PermissionAttachment
    {
        return $this->perm->addAttachment($plugin, $name, $value);
    }

    /**
     * @param PermissionAttachment $attachment
     */
    public function removeAttachment(PermissionAttachment $attachment)
    {
        $this->perm->removeAttachment($attachment);
    }

    public function recalculatePermissions() : void
    {
        $this->server->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_USERS, $this);
        $this->server->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);

        if ($this->perm === null) {
            return;
        }

        $this->perm->recalculatePermissions();

        if ($this->spawned) {
            if ($this->hasPermission(Server::BROADCAST_CHANNEL_USERS)) {
                $this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_USERS, $this);
            }
            if ($this->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE)) {
                $this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);
            }

            $this->sendCommandData();
        }
    }

    /**
     * @return PermissionAttachmentInfo[]
     */
    public function getEffectivePermissions(): array
    {
        return $this->perm->getEffectivePermissions();
    }

    public function sendCommandData() : void{
        $pk = new AvailableCommandsPacket();
        $pk->commandData = [];
        foreach($this->server->getCommandMap()->getCommands() as $command){
            if(!$command->testPermissionSilent($this) && !$command->isRegistered()){
                continue;
            }

            $cmdData = $command->getMcpeCommandData();
            foreach ($cmdData["overloads"] as $index => $overload) {
                $params = (isset($overload["input"]) ? false : $overload);

                if (!$params) {
                    break;
                }

                $overload = new CommandOverload();

                foreach ($params as $param) {
                    $overload->getInput()->addParameter($param);
                }

                $cmdData["overloads"][$index] = $overload;
            }
            $cmdData["aliases"] = $command->getAliases();

            $pk->commandData[$command->getName()]["versions"][0] = $cmdData;
        }
        if(count($pk->commandData) > 0){
            $this->sendDataPacket($pk);
        }
    }

    public function getCurrentWindowType() : int{
        return $this->currentWindowType;
    }

    public function setCurrentWindowType(int $currentWindowType) : void{
        $this->currentWindowType = $currentWindowType;
    }

    /**
     * @param NetworkInterface $interface
     * @param int $clientID
     * @param string $ip
     * @param int $port
     */
    public function __construct(NetworkInterface $interface, $clientID, $ip, $port, bool $isValid)
    {
        $this->interface = $interface;
        $this->isValidSession = $isValid;
        $this->perm = new PermissibleBase($this);
        $this->namedtag = CompoundTag::create();
        $this->server = Server::getInstance();
        $this->ip = $ip;
        $this->port = $port;
        $this->clientID = $clientID;
        $this->loaderId = Level::generateChunkLoaderId($this);
        $this->chunksPerTick = (int)$this->server->getProperty("chunk-sending.per-tick", 4);
        $this->spawnThreshold = (int)(($this->server->getProperty("chunk-sending.spawn-radius", 4) ** 2) * M_PI);
        $this->spawnPosition = null;
        $this->gamemode = $this->server->getGamemode();
        $this->setLevel($this->server->getDefaultLevel());
        $this->boundingBox = new AxisAlignedBB(0, 0, 0, 0, 0, 0);

        $this->uuid = null;
        $this->rawUUID = null;

        $this->batchQueue = new SplQueue();

        $this->flagForClose = new \SplFixedArray(3);

        $this->creationTime = microtime(true);

        $this->sessionAdapter = new PlayerNetworkSessionAdapter($this->server, $this);
    }

    public function getTransactionQueue() : SimpleTransactionQueue{
        //Is creating the transaction queue ondemand a good idea? I think only if it's destroyed afterwards. hmm...
        if($this->transactionQueue === null){
            //Potential for crashes here if a plugin attempts to use this, say for an NPC plugin or something...
            $this->transactionQueue = new SimpleTransactionQueue($this);
        }
        return $this->transactionQueue;
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Gets the "friendly" name to display of this player to use in the chat.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * @param string $name
     */
    public function setDisplayName($name)
    {
        $this->displayName = $name;
        if ($this->spawned) {
            $this->server->updatePlayerList($this);
        }
    }

    public function setSkin(Skin $skin): void
    {
        parent::setSkin($skin);
        if ($this->spawned) {
            $this->server->updatePlayerList($this);
        }
    }

    public function jump() : void
    {
        (new PlayerJumpEvent($this))->call();
        parent::jump();
    }

    /**
     * Gets the player IP address
     *
     * @return string
     */
    public function getAddress(): string
    {
        return $this->ip;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return Position
     * @deprecated
     */
    public function getNextPosition(): Position
    {
        return $this->getPosition();
    }

    /**
     * @return bool
     */
    public function isSleeping(): bool
    {
        return $this->sleeping !== null;
    }

    public function getInAirTicks()
    {
        return $this->inAirTicks;
    }

    public function getStartAirTicks()
    {
        return $this->startAirTicks;
    }

    /**
     * Returns how long the player has been using their currently-held item for. Used for determining arrow shoot force
     * for bows.
     *
     * @return int
     */
    public function getItemUseDuration(): int
    {
        return $this->startAction === -1 ? -1 : ($this->server->getTick() - $this->startAction);
    }

    /**
     * Returns whether the player is currently using an item (right-click and hold).
     * @return bool
     */
    public function isUsingItem(): bool
    {
        return $this->getGenericFlag(self::DATA_FLAG_ACTION) and $this->startAction > -1;
    }

    public function setUsingItem(bool $value = true): void
    {
        $this->startAction = $value ? $this->server->getTick() : -1;
        $this->setGenericFlag(self::DATA_FLAG_ACTION, $value);
    }

    /**
     * Returns whether the player has a cooldown period left before it can use the given item again.
     *
     * @param Item $item
     *
     * @return bool
     */
    public function hasItemCooldown(Item $item): bool
    {
        $this->checkItemCooldowns();
        return isset($this->usedItemsCooldown[$item->getId()]);
    }

    /**
     * Resets the player's cooldown time for the given item back to the maximum.
     *
     * @param Item $item
     */
    public function resetItemCooldown(Item $item): void
    {
        $ticks = $item->getCooldownTicks();
        if ($ticks > 0) {
            $this->usedItemsCooldown[$item->getId()] = $this->server->getTick() + $ticks;
        }
    }

    protected function checkItemCooldowns(): void
    {
        $serverTick = $this->server->getTick();
        foreach ($this->usedItemsCooldown as $itemId => $cooldownUntil) {
            if ($cooldownUntil <= $serverTick) {
                unset($this->usedItemsCooldown[$itemId]);
            }
        }
    }

    /**
     * @return FishingHook|null
     */
    public function getFishingHook(): ?FishingHook
    {
        return $this->fishingHook;
    }

    /**
     * @param FishingHook|null $fishingHook
     */
    public function setFishingHook(?FishingHook $fishingHook): void
    {
        $this->fishingHook = $fishingHook;
    }

    protected function switchLevel(Level $targetLevel): bool
    {
        $oldLevel = $this->level;
        if (parent::switchLevel($targetLevel)) {
            if ($oldLevel !== null) {
                foreach ($this->usedChunks as $index => $d) {
                    Level::getXZ($index, $X, $Z);
                    $this->unloadChunk($X, $Z, $oldLevel);
                }
            }

            $this->usedChunks = [];
            $this->loadQueue = [];

            $this->level->sendTime($this);
            $this->level->sendDifficulty($this);

            if ($targetLevel->getDimension() != $oldLevel->getDimension()) {
                $pk = new ChangeDimensionPacket();
                $pk->dimension = $targetLevel->getDimension();
                $pk->x = $this->x;
                $pk->y = $this->y;
                $pk->z = $this->z;
                $this->sendDataPacket($pk);

                $this->sendPlayStatus(PlayStatusPacket::PLAYER_SPAWN);
            }

            $targetLevel->getWeather()->sendWeather($this);
            return true;
        }
        return false;
    }

    protected function unloadChunk($x, $z, Level $level = null)
    {
        $level = $level ?? $this->level;
        $index = Level::chunkHash($x, $z);
        if (isset($this->usedChunks[$index])) {
            if ($this->usedChunks[$index]) { //The chunk was sent
                foreach ($level->getChunkEntities($x, $z) as $entity) {
                    if ($entity !== $this) {
                        $entity->despawnFrom($this);
                    }
                }
            } else { //There can still be a pending request
                MCPEChunkCache::getInstance($level)->unregister($this, $x, $z);
            }

            unset($this->activeChunkGenerationRequests[$index]);
            unset($this->usedChunks[$index]);
        }
        $level->unregisterChunkLoader($this, $x, $z);
        $level->unregisterChunkListener($this, $x, $z);
        unset($this->loadQueue[$index]);
    }

    public function setLevel(Level $level = null)
    {
        parent::setLevel($level);

        if ($this->level !== null) {
            $this->chunkCache = MCPEChunkCache::getInstance($this->level);
        }
        return $this;
    }

    /**
     * @return Position
     */
    public function getSpawn()
    {
        if ($this->hasValidSpawnPosition()) {
            return $this->spawnPosition;
        } else {
            $level = $this->server->getDefaultLevel();

            return $level->getSafeSpawn();
        }
    }

    /**
     * @return bool
     */
    public function hasValidSpawnPosition(): bool
    {
        return $this->spawnPosition instanceof WeakPosition and $this->spawnPosition->isValid();
    }

    public function sendChunk(int $x, int $z, string $payload): void
    {
        if(!$this->isConnected()){
            return;
        }

        if (!$this instanceof BedrockPlayer) {
            $this->chunkHack = 2;
        }

        unset($this->activeChunkGenerationRequests[Level::chunkHash($x, $z)]);
        $this->usedChunks[Level::chunkHash($x, $z)] = true;

        $this->sendEncoded($payload);

        if ($this->spawned) {
            foreach ($this->level->getChunkEntities($x, $z) as $entity) {
                if ($entity !== $this and !$entity->closed and $entity->isAlive() and !$entity->isFlaggedForDespawn()) {
                    $entity->despawnFrom($this, false);
                    $entity->spawnTo($this);
                }
            }
        }

        if ($this->spawnChunkLoadCount !== -1 and ++$this->spawnChunkLoadCount >= $this->spawnThreshold) {
            $this->doFirstSpawn();

            $this->spawnChunkLoadCount = -1;
        }
    }


    protected function sendNextChunk()
    {
        if (!$this->isConnected()) {
            return;
        }

        if ($this->level === NULL) {
            return;
        }

        Timings::$playerChunkSendTimer->startTiming();

        $count = 0;
        $limit = $this->chunksPerTick - count($this->activeChunkGenerationRequests);
        foreach ($this->loadQueue as $index => $distance) {
            if ($count >= $limit) {
                break;
            }

            $X = null;
            $Z = null;
            Level::getXZ($index, $X, $Z);
            assert(is_int($X) and is_int($Z));

            ++$count;

            $this->usedChunks[$index] = false;
            $this->level?->registerChunkLoader($this, $X, $Z, true);
            $this->level?->registerChunkListener($this, $X, $Z);

            if (!$this->level?->populateChunk($X, $Z)) {
                continue;
            }

            $this->activeChunkGenerationRequests[$index] = true;
            unset($this->loadQueue[$index]);
            $this->chunkCache->request($this, $X, $Z);
        }

        Timings::$playerChunkSendTimer->stopTiming();
    }

    protected function onTerrainReady(): void
    {
        $this->doFirstSpawn();
    }

    public function doFirstSpawn(): void
    {
        if ($this->spawned || !$this->constructed) {
            return; //avoid player spawning twice (this can only happen on 3.x with a custom malicious client)
        }

        $this->spawned = true;
        $this->setImmobile(false);

        $this->inventory->sendContents($this);
        $this->armorInventory->sendContents($this);
        $this->offHandInventory->sendContents($this);
        $this->inventory->sendHeldItem($this);

        $this->sendPlayStatus(PlayStatusPacket::PLAYER_SPAWN);

        if ($this->hasPermission(Server::BROADCAST_CHANNEL_USERS)) {
            $this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_USERS, $this);
        }
        if ($this->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE)) {
            $this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);
        }

        $ev = new PlayerJoinEvent($this,
            new TranslationContainer(TextFormat::YELLOW . "%multiplayer.player.joined", [
                $this->getDisplayName()
            ])
        );
        $ev->call();
        if (strlen(trim((string)$ev->getJoinMessage())) > 0) {
            $this->server->broadcastMessage($ev->getJoinMessage());
        }

        $this->spawnChunkLoadCount = -1;

        $this->noDamageTicks = 60;

        foreach ($this->usedChunks as $index => $hasSent) {
            if (!$hasSent) {
                continue; //this will happen when the chunk is ready to send
            }
            Level::getXZ($index, $chunkX, $chunkZ);
            foreach ($this->level->getChunkEntities($chunkX, $chunkZ) as $entity) {
                if ($entity !== $this and !$entity->closed and $entity->isAlive() and !$entity->isFlaggedForDespawn()) {
                    $entity->spawnTo($this);
                }
            }
        }

        $this->spawnToAll();

        if($this->getHealth() <= 0){
            $this->respawn();
        }

        $this->joined = true;
    }

    protected function sendRespawnPacket(Vector3 $pos) : void
    {
        $pk = new RespawnPacket();
        $pk->x = $pos->x;
        $pk->y = $pos->y + $this->baseOffset;
        $pk->z = $pos->z;
        $this->sendDataPacket($pk);
    }

    protected function selectChunks(): \Generator
    {
        $radius = $this->server->getAllowedViewDistance($this->viewDistance);
        $radiusSquared = $radius ** 2;

        $centerX = $this->getFloorX() >> 4;
        $centerZ = $this->getFloorZ() >> 4;

        for ($x = 0; $x < $radius; ++$x) {
            for ($z = 0; $z <= $x; ++$z) {
                if (($x ** 2 + $z ** 2) > $radiusSquared) {
                    break; //skip to next band
                }

                //If the chunk is in the radius, others at the same offsets in different quadrants are also guaranteed to be.

                /* Top right quadrant */
                yield Level::chunkHash($centerX + $x, $centerZ + $z);
                /* Top left quadrant */
                yield Level::chunkHash($centerX - $x - 1, $centerZ + $z);
                /* Bottom right quadrant */
                yield Level::chunkHash($centerX + $x, $centerZ - $z - 1);
                /* Bottom left quadrant */
                yield Level::chunkHash($centerX - $x - 1, $centerZ - $z - 1);

                if ($x !== $z) {
                    /* Top right quadrant mirror */
                    yield Level::chunkHash($centerX + $z, $centerZ + $x);
                    /* Top left quadrant mirror */
                    yield Level::chunkHash($centerX - $z - 1, $centerZ + $x);
                    /* Bottom right quadrant mirror */
                    yield Level::chunkHash($centerX + $z, $centerZ - $x - 1);
                    /* Bottom left quadrant mirror */
                    yield Level::chunkHash($centerX - $z - 1, $centerZ - $x - 1);
                }
            }
        }
    }

    protected function orderChunks()
    {
        if ($this->connected === false or $this->viewDistance === -1 or !$this->doOrderChunks) {
            return false;
        }

        Timings::$playerChunkOrderTimer->startTiming();

        $newOrder = [];
        $unloadChunks = $this->usedChunks;

        foreach ($this->selectChunks() as $hash) {
            if (!isset($this->usedChunks[$hash]) or $this->usedChunks[$hash] === false) {
                $newOrder[$hash] = true;
            }
            unset($unloadChunks[$hash]);
        }

        foreach ($unloadChunks as $index => $bool) {
            Level::getXZ($index, $X, $Z);
            $this->unloadChunk($X, $Z);
        }

        $this->loadQueue = $newOrder;

        Timings::$playerChunkOrderTimer->stopTiming();

        return true;
    }

    /**
     * Batch a Data packet into the channel list to send at the end of the tick
     *
     * @param DataPacket $packet
     *
     * @return bool
     */
    public function batchDataPacket(DataPacket $packet): bool
    {
        if ($this->connected === false) {
            return false;
        }

        $timings = Timings::getSendDataPacketTimings($packet);
        $timings->startTiming();
        $ev = new DataPacketSendEvent($this, $packet);
        $ev->call();
        if ($ev->isCancelled()) {
            $timings->stopTiming();
            return false;
        }

        $this->batchedPackets[] = clone $packet;
        $timings->stopTiming();
        return true;
    }

    /**
     * @param DataPacket $packet
     * @param bool $needACK @deprecated
     * @param bool $immediate
     *
     * @return int|bool
     */
    public function sendDataPacket(DataPacket $packet, bool $needACK = false, bool $immediate = false)
    {
        if (!$this->connected) {
            return false;
        }

        //Basic safety restriction. TODO: improve this
        if (!$this->loggedIn and !$packet->canBeSentBeforeLogin()) {
            throw new \InvalidArgumentException("Attempted to send " . get_class($packet) . " to " . $this->getName() . " too early");
        }

        $timings = Timings::getSendDataPacketTimings($packet);
        $timings->startTiming();

        $ev = new DataPacketSendEvent($this, $packet);
        $ev->call();
        if ($ev->isCancelled()) {
            $timings->stopTiming();
            return false;
        }

        if (!$packet->isEncoded) {
            $packet->encode();
        }

        $stream = new MCPEPacketBatch();
        $stream->putPacket($packet);

        if (NetworkCompression::$THRESHOLD >= 0 and strlen($stream->buffer) >= NetworkCompression::$THRESHOLD) {
            $compressionLevel = NetworkCompression::$LEVEL;
            $forceSync = false;
        } else {
            $compressionLevel = 0;
            $forceSync = true;
        }

        if ($immediate) {
            // Skip any queues
            $this->sendEncoded(ProtocolInfo::MCPE_RAKNET_PACKET_ID . NetworkCompression::compress($stream->buffer, $compressionLevel), false, true);
            return true;
        }

        $promise = new CompressBatchPromise();
        $this->batchQueue->push($promise);

        $promise->onResolve(function (CompressBatchPromise $promise): void {
            if ($this->connected and $this->batchQueue->bottom() === $promise) {
                $this->batchQueue->dequeue(); //result unused
                $this->sendEncoded(ProtocolInfo::MCPE_RAKNET_PACKET_ID . $promise->getResult());

                while (!$this->batchQueue->isEmpty()) {
                    /** @var CompressBatchPromise $current */
                    $current = $this->batchQueue->bottom();
                    if ($current->hasResult()) {
                        $this->batchQueue->dequeue();

                        $this->sendEncoded(ProtocolInfo::MCPE_RAKNET_PACKET_ID . $current->getResult());
                    } else {
                        //can't send any more queued until this one is ready
                        break;
                    }
                }
            }
        });

        if (!$forceSync and !$immediate and $this->server->isNetworkCompressionAsync()) {
            $task = new CompressBatchTask($stream->buffer, $compressionLevel, $promise);
            $this->server->getScheduler()->scheduleAsyncTask($task);
        } else {
            $promise->resolve(NetworkCompression::compress($stream->buffer, $compressionLevel));
        }

        $timings->stopTiming();
        return true;
    }

    /**
     * @internal
     */
    public function getCipher(): ?EncryptionContext
    {
        return $this->cipher;
    }

    /**
     * @param string $payload
     * @param bool $needACK
     * @param bool $immediate
     *
     * @return int|bool
     */
    public function sendEncoded(string $payload, bool $needACK = false, bool $immediate = false)
    {
        if (!$this->connected) {
            return false;
        }

        $ev = new RawPacketSendEvent($this, $payload);
        $ev->call();
        if ($ev->isCancelled()) {
            return false;
        }

        $identifier = $this->interface->putBuffer($this, $payload, $needACK, $immediate);

        if ($needACK and $identifier !== null) {
            $this->needACK[$identifier] = false;

            return $identifier;
        }

        return true;
    }

    public function queueEncoded(string $data): bool
    {
        if (!$this->connected) {
            return false;
        }

        $payload = new CompressBatchPromise();
        $payload->resolve($data);
        $this->batchQueue->push($payload);

        $payload->onResolve(function (CompressBatchPromise $promise): void {
            if ($this->connected and $this->batchQueue->bottom() === $promise) {
                $this->batchQueue->dequeue(); //result unused
                $this->sendEncoded(ProtocolInfo::MCPE_RAKNET_PACKET_ID . $promise->getResult());

                while (!$this->batchQueue->isEmpty()) {
                    /** @var CompressBatchPromise $current */
                    $current = $this->batchQueue->bottom();
                    if ($current->hasResult()) {
                        $this->batchQueue->dequeue();

                        $this->sendEncoded(ProtocolInfo::MCPE_RAKNET_PACKET_ID . $current->getResult());
                    } else {
                        //can't send any more queued until this one is ready
                        break;
                    }
                }
            }
        });

        return true;
    }

    /**
     * @param DataPacket $packet
     * @param bool $needACK
     *
     * @return bool|int
     * @deprecated
     *
     */
    public function dataPacket(DataPacket $packet, $needACK = false)
    {
        return $this->sendDataPacket($packet, $needACK, false);
    }

    /**
     * @param DataPacket $packet
     * @param bool $needACK
     *
     * @return bool|int
     * @deprecated
     *
     */
    public function directDataPacket(DataPacket $packet, $needACK = false)
    {
        return $this->sendDataPacket($packet, $needACK, true);
    }

    /**
     * @param Vector3 $pos
     *
     * @return bool
     */
    public function sleepOn(Vector3 $pos): bool
    {
        if (!$this->isOnline()) {
            return false;
        }

        $pos = $pos->floor();
        $b = $this->level->getBlock($pos);

        $ev = new PlayerBedEnterEvent($this, $b);
        $ev->call();
        if ($ev->isCancelled()) {
            return false;
        }

        if ($b instanceof Bed) {
            $b->setOccupied();
        }

        $this->sleeping = clone $pos;

        $this->propertyManager->setBlockPos(self::DATA_PLAYER_BED_POSITION, $pos);
        $this->setPlayerFlag(self::DATA_PLAYER_FLAG_SLEEP, true);

        $this->setSpawn($pos);

        $this->level->setSleepTicks(60);

        return true;
    }

    public function stopSleep() : void
    {
        if ($this->sleeping instanceof Vector3) {
            $b = $this->level->getBlock($this->sleeping);
            if ($b instanceof Bed) {
                $b->setOccupied(false);
            }
            $ev = new PlayerBedLeaveEvent($this, $b);
            $ev->call();

            $this->sleeping = null;
            $this->propertyManager->setBlockPos(self::DATA_PLAYER_BED_POSITION, null);
            $this->setPlayerFlag(self::DATA_PLAYER_FLAG_SLEEP, false);

            $this->level->setSleepTicks(0);

            $pk = new AnimatePacket();
            $pk->entityRuntimeId = $this->id;
            $pk->action = AnimatePacket::ACTION_STOP_SLEEP;
            $this->sendDataPacket($pk);
        }
    }

    public function setSneaking(bool $value = true) : void{
        parent::setSneaking($value);

        if($value){
            $this->updateBoundingBox(1.65, 0.6);
        }else{
            $this->updateBoundingBox(1.8, 0.6);
        }
    }

    public function setGliding(bool $value = true) : void{
        parent::setGliding($value);

        if($value){
            $this->updateBoundingBox(0.6, 0.6);
        }else{
            $this->updateBoundingBox(1.8, 0.6);
        }
    }

    /**
     * Sets the spawnpoint of the player (and the compass direction) to a Vector3, or set it on another world with a
     * Position object
     *
     * @param Vector3|Position $pos
     */
    public function setSpawn(Vector3 $pos)
    {
        if (!($pos instanceof Position)) {
            $level = $this->level;
        } else {
            $level = $pos->getLevel();
        }
        $this->spawnPosition = new WeakPosition($pos->x, $pos->y, $pos->z, $level);
        $pk = new SetSpawnPositionPacket();
        $pk->x = (int)$this->spawnPosition->x;
        $pk->y = (int)$this->spawnPosition->y;
        $pk->z = (int)$this->spawnPosition->z;
        $pk->spawnType = SetSpawnPositionPacket::TYPE_PLAYER_SPAWN;
        $pk->spawnForced = false;
        $this->sendDataPacket($pk);
    }

    /**
     * @return int
     */
    public function getGamemode(): int
    {
        return $this->gamemode;
    }

    /**
     * @param int $gamemode
     * @return int
     * @internal
     *
     * Returns a client-friendly gamemode of the specified real gamemode
     * This function takes care of handling gamemodes known to MCPE (as of 1.1.0.3, that includes Survival, Creative and Adventure)
     *
     * TODO: remove this when Spectator Mode gets added properly to MCPE
     *
     */
    public static function getClientFriendlyGamemode(int $gamemode): int
    {
        $gamemode &= 0x03;
        if ($gamemode === Player::SPECTATOR) {
            return Player::CREATIVE;
        }

        return $gamemode;
    }

    /**
     * Sets the gamemode, and if needed, kicks the Player.
     *
     * @param int $gm
     * @param bool $client if the client made this change in their GUI
     *
     * @return bool
     */
    public function setGamemode(int $gm, bool $client = false): bool
    {
        if ($gm < 0 or $gm > 3 or $this->gamemode === $gm or !$this->isOnline()) {
            return false;
        }

        $ev = new PlayerGameModeChangeEvent($this, $gm);
        $ev->call();
        if ($ev->isCancelled()) {
            if ($client) { //gamemode change by client in the GUI
                $this->sendGamemode();
            }
            return false;
        }

        $this->gamemode = $gm;

        $this->allowFlight = $this->isCreative();
        if ($this->isSpectator()) {
            $this->setGenericFlag(self::DATA_FLAG_HAS_COLLISION, false);
            $this->setFlying(true);
            $this->onGround = false;

            //TODO: HACK! this syncs the onground flag with the client so that flying works properly
            //this is a yucky hack but we don't have any other options :(
            $this->sendPosition($this, null, null, MovePlayerPacket::MODE_TELEPORT);

            $this->despawnFromAll();
        } else {
            $this->setGenericFlag(self::DATA_FLAG_HAS_COLLISION, true);
            $this->checkGroundState(0, 0, 0, 0, 0, 0);
            if($this->isSurvival()){
                $this->setFlying(false);
            }
            $this->spawnToAll();
        }

        $this->namedtag->setInt("playerGameType", $this->gamemode);
        if (!$client) { //Gamemode changed by server, do not send for client changes
            $this->sendGamemode();
        } else {
            Command::broadcastCommandMessage($this, new TranslationContainer("commands.gamemode.success.self", [Server::getGamemodeString($gm)]));
        }

        $this->inventory->sendContents($this);
        $this->inventory->sendHeldItem($this->hasSpawned);
        $this->sendSettings(); //TODO: we might be able to do this with the abilities packet alone
        $this->inventory->sendCreativeContents();

        return true;
    }

    /**
     * @internal
     * Sends the player's gamemode to the client.
     */
    public function sendGamemode()
    {
        $pk = new SetPlayerGameTypePacket();
        $pk->gamemode = Player::getClientFriendlyGamemode($this->gamemode);
        $this->sendDataPacket($pk);
    }

    /**
     * Sends all the option flags
     */
    public function sendSettings()
    {
        $pk = new AdventureSettingsPacket();
        $pk->flags = 0;
        $pk->worldImmutable = $this->isSpectator();
        $pk->noPvp = $this->isSpectator();
        $pk->autoJump = $this->autoJump;
        $pk->allowFlight = $this->allowFlight;
        $pk->noClip = $this->isSpectator();
        $pk->isFlying = $this->flying;
        $pk->userPermission = ($this->isOp() ? AdventureSettingsPacket::PERMISSION_OPERATOR : AdventureSettingsPacket::PERMISSION_NORMAL);
        $this->sendDataPacket($pk);
    }

    /**
     * NOTE: Because Survival and Adventure Mode share some similar behaviour, this method will also return true if the player is
     * in Adventure Mode. Supply the $literal parameter as true to force a literal Survival Mode check.
     *
     * @param bool $literal whether a literal check should be performed
     *
     * @return bool
     */
    public function isSurvival(bool $literal = false): bool
    {
        if ($literal) {
            return $this->gamemode === Player::SURVIVAL;
        } else {
            return ($this->gamemode & 0x01) === 0;
        }
    }

    /**
     * NOTE: Because Creative and Spectator Mode share some similar behaviour, this method will also return true if the player is
     * in Spectator Mode. Supply the $literal parameter as true to force a literal Creative Mode check.
     *
     * @param bool $literal whether a literal check should be performed
     *
     * @return bool
     */
    public function isCreative(bool $literal = false): bool
    {
        if ($literal) {
            return $this->gamemode === Player::CREATIVE;
        } else {
            return ($this->gamemode & 0x01) === 1;
        }
    }

    /**
     * NOTE: Because Adventure and Spectator Mode share some similar behaviour, this method will also return true if the player is
     * in Spectator Mode. Supply the $literal parameter as true to force a literal Adventure Mode check.
     *
     * @param bool $literal whether a literal check should be performed
     *
     * @return bool
     */
    public function isAdventure(bool $literal = false): bool
    {
        if ($literal) {
            return $this->gamemode === Player::ADVENTURE;
        } else {
            return ($this->gamemode & 0x02) > 0;
        }
    }

    /**
     * @return bool
     */
    public function isSpectator(): bool
    {
        return $this->gamemode === Player::SPECTATOR;
    }

    public function isFireProof(): bool
    {
        return $this->isCreative();
    }

    public function setInteractiveTag(string $tag): void{
        $this->propertyManager->setString(self::DATA_INTERACTIVE_TAG, $tag);
    }

    public function removeInteractiveTag(): void{
        $this->propertyManager->removeProperty(self::DATA_INTERACTIVE_TAG);
    }

    public function getDrops(): array
    {
        if (!$this->isCreative()) {
            return parent::getDrops();
        }

        return [];
    }

    public function getXpDropAmount() : int{
        if(!$this->isCreative()){
            return parent::getXpDropAmount();
        }

        return 0;
    }

    public function canBeMovedByCurrents(): bool
    {
        return false;
    }

    protected function checkGroundState($movX, $movY, $movZ, $dx, $dy, $dz): void
    {
        if ($this->isSpectator()) {
            $this->onGround = false;
        } else {
            $bb = clone $this->boundingBox;
            $bb->minY = $this->y - 0.2;
            $bb->maxY = $this->y + 0.2;

            //we're already at the new position at this point; check if there are blocks we might have landed on between
            //the old and new positions (running down stairs necessitates this)
            $bb = $bb->addCoord(-$dx, -$dy, -$dz);

            $this->onGround = $this->isCollided = count($this->level->getCollisionBlocks($bb, true)) > 0;
        }
    }

    protected function checkNearEntities()
    {
        foreach ($this->level->getNearbyEntities($this->boundingBox->grow(1, 0.5, 1), $this) as $entity) {
            $entity->scheduleUpdate();

            if (!$entity->isAlive() or $entity->isFlaggedForDespawn()) {
                continue;
            }

            if ($entity instanceof Arrow and $entity->canBePickedUp()) {
                $item = Item::get(Item::ARROW, $entity->getPotionId() + 1, 1);

                $add = false;
                if (!$this->server->allowInventoryCheats and !$this->isCreative()) {
                    if (!$this->getFloatingInventory()->canAddItem($item) or !$this->inventory->canAddItem($item)) {
                        //The item is added to the floating inventory to allow client to handle the pickup
                        //We have to also check if it can be added to the real inventory before sending packets.
                        continue;
                    }
                    $add = true;
                }

                $ev = new InventoryPickupArrowEvent($this->inventory, $entity);
                if ($entity->getBow() !== null and $entity->getBow()->hasEnchantment(Enchantment::INFINITY)) {
                    $ev->setCancelled(true);
                }
                $ev->call();
                if ($ev->isCancelled()) {
                    continue;
                }

                $pk = new TakeItemEntityPacket();
                $pk->eid = $this->id;
                $pk->target = $entity->getId();
                $this->server->broadcastPacket($entity->getViewers(), $pk);

                if ($add) {
                    $this->getFloatingInventory()->addItem(clone $item);
                }
                $entity->flagForDespawn();
            } elseif ($entity instanceof DroppedItem) {
                if ($entity->getPickupDelay() <= 0) {
                    $item = $entity->getItem();

                    if ($item instanceof Item) {
                        $add = false;
                        if (!$this->server->allowInventoryCheats and !$this->isCreative()) {
                            if (!$this->getFloatingInventory()->canAddItem($item) or !$this->inventory->canAddItem($item)) {
                                continue;
                            }
                            $add = true;
                        }

                        $ev = new InventoryPickupItemEvent($this->inventory, $entity);
                        $ev->call();
                        if ($ev->isCancelled()) {
                            continue;
                        }

                        $pk = new TakeItemEntityPacket();
                        $pk->eid = $this->id;
                        $pk->target = $entity->getId();
                        $this->server->broadcastPacket($entity->getViewers(), $pk);

                        if ($add) {
                            $this->getFloatingInventory()->addItem(clone $item);
                        }

                        $entity->flagForDespawn();
                    }
                }
            }
        }
    }

    protected function handleMovement(Vector3 $newPos): void
    {
        $this->moveRateLimit--;
        if ($this->moveRateLimit < 0) {
            return;
        }

        $oldPos = $this->asLocation();
        $distanceSquared = $newPos->distanceSquared($oldPos);

        $revert = false;

        if ($distanceSquared > 100) {
            //TODO: this is probably too big if we process every movement
            /* !!! BEWARE YE WHO ENTER HERE !!!
             *
             * This is NOT an anti-cheat check. It is a safety check.
             * Without it hackers can teleport with freedom on their own and cause lots of undesirable behaviour, like
             * freezes, lag spikes and memory exhaustion due to sync chunk loading and collision checks across large distances.
             * Not only that, but high-latency players can trigger such behaviour innocently.
             *
             * If you must tamper with this code, be aware that this can cause very nasty results. Do not waste our time
             * asking for help if you suffer the consequences of messing with this.
             */
            $this->server->getLogger()->warning($this->getName() . " moved too fast, reverting movement");
            $this->server->getLogger()->debug("Old position: " . $this->asVector3() . ", new position: " . $newPos);
            $revert = true;
        } elseif (!$this->level->isChunkGenerated($newPos->getFloorX() >> 4, $newPos->getFloorZ() >> 4)) {
            $revert = true;
            $this->nextChunkOrderRun = 0;
        }

        $ev = new PlayerPreMoveEvent($this, $this->asLocation(), Location::fromObject($newPos, $this->level));
        if ($revert) {
            $ev->setCancelled();
        }
        $ev->call();

        if ($ev->isCancelled()) {
            $revert = true;
        }

        $newPos = $ev->getTo();

        if (!$revert and $distanceSquared != 0) {
            $dx = $newPos->x - $this->x;
            $dy = $newPos->y - $this->y;
            $dz = $newPos->z - $this->z;

            //the client likes to clip into blocks like stairs, but we do full server-side prediction of that without
            //help from the client's position changes, so we deduct the expected clip height from the moved distance.
            $expectedClipDistance = $this->ySize * (1 - self::STEP_CLIP_MULTIPLIER);
            $dy -= $expectedClipDistance;

            $this->move($dx, $dy, $dz);

            $diff = $this->distanceSquared($newPos);

            if ($diff > 0) {
                $this->setPosition($newPos);
            }
        }

        if ($revert) {
            $this->revertMovement($oldPos);
        }
    }

    /**
     * Fires movement events and synchronizes player movement, every tick.
     */
    protected function processMostRecentMovements(): void
    {
        $now = microtime(true);
        $multiplier = $this->lastMovementProcess !== null ? ($now - $this->lastMovementProcess) * 20 : 1;
        $exceededRateLimit = $this->moveRateLimit < 0;
        $this->moveRateLimit = min(self::MOVE_BACKLOG_SIZE, max(0, $this->moveRateLimit) + self::MOVES_PER_TICK * $multiplier);
        $this->lastMovementProcess = $now;

        $from = new Location($this->lastX, $this->lastY, $this->lastZ, $this->lastYaw, $this->lastPitch, $this->level);
        $to = $this->getLocation();
        $this->speed = $to->subtract($from);

        $delta = (($this->lastX - $to->x) ** 2) + (($this->lastY - $to->y) ** 2) + (($this->lastZ - $to->z) ** 2);
        $deltaAngle = abs($this->lastYaw - $to->yaw) + abs($this->lastPitch - $to->pitch);

        if ($delta > 0.0001 or $deltaAngle > 1.0) {
            $this->lastX = $to->x;
            $this->lastY = $to->y;
            $this->lastZ = $to->z;

            $this->lastYaw = $to->yaw;
            $this->lastPitch = $to->pitch;

            $ev = new PlayerMoveEvent($this, $from, $to);
            $ev->call();

            if ($ev->isCancelled()) {
                $this->revertMovement($from);
                return;
            }

            if ($to->distanceSquared($ev->getTo()) > 0.01) { //If plugins modify the destination
                $this->teleport($ev->getTo());
                return;
            }

            $this->broadcastMovement();

            $horizontalDistanceTravelled = sqrt((($from->x - $to->x) ** 2) + (($from->z - $to->z) ** 2));
            if ($horizontalDistanceTravelled > 0) {
                //TODO: check swimming (adds 0.015 exhaustion in MCPE)
                if ($this->isSprinting()) {
                    $this->exhaust(0.1 * $horizontalDistanceTravelled, PlayerExhaustEvent::CAUSE_SPRINTING);
                } else {
                    $this->exhaust(0.01 * $horizontalDistanceTravelled, PlayerExhaustEvent::CAUSE_WALKING);
                }

                if ($this->nextChunkOrderRun > 20) {
                    $this->nextChunkOrderRun = 20;
                }
            }
        }
        if ($exceededRateLimit) { //client and server positions will be out of sync if this happens
            $this->server->getLogger()->debug("Player " . $this->getName() . " exceeded movement rate limit, forcing to last accepted position");
            $this->sendPosition($this, $this->yaw, $this->pitch, MovePlayerPacket::MODE_RESET);
        }
    }

	protected function revertMovement(Location $from) : void{
		$this->lastX = $from->x;
		$this->lastY = $from->y;
		$this->lastZ = $from->z;

		$this->lastYaw = $from->yaw;
		$this->lastPitch = $from->pitch;

		$this->setPosition($from);
		$this->sendPosition($from, $from->yaw, $from->pitch, MovePlayerPacket::MODE_RESET);
	}

    public function fall(float $fallDistance) : void{
        if(!$this->flying){
            parent::fall($fallDistance);
        }
    }

	public function setMotion(Vector3 $motion): bool
    {
		if(parent::setMotion($motion)){
			$this->broadcastMotion();

			if($this->motionY > 0){
				$this->startAirTicks = (-(log($this->gravity / ($this->gravity + $this->drag * $this->motionY))) / $this->drag) * 2 + 5;
			}

			return true;
		}
		return false;
	}

	protected function updateMovement(bool $teleport = false): void
    {

	}

	protected function tryChangeMovement() : void{

	}

	public function sendAttributes(bool $sendAll = false){
		$entries = $sendAll ? $this->attributeMap->getAll() : $this->attributeMap->needSend();
		if(count($entries) > 0){
			$pk = new UpdateAttributesPacket();
			$pk->entityRuntimeId = $this->id;
			$pk->entries = $entries;
			$this->sendDataPacket($pk);
			foreach($entries as $entry){
				$entry->markSynchronized();
			}
		}
	}

	public function onUpdate(int $currentTick) : bool{
		if(!$this->loggedIn){
			return false;
		}

		$tickDiff = $currentTick - $this->lastUpdate;

		if($tickDiff <= 0){
			return true;
		}

		$this->messageCounter = 2;

		$this->lastUpdate = $currentTick;

		$this->sendAttributes();

		if(!$this->isAlive() and $this->spawned){
			$this->deadTicks += $tickDiff;
			if($this->deadTicks >= 10){
				$this->despawnFromAll();
			}
			return true;
		}

		if($this->flagForClose[0] !== null){
			$this->close($this->flagForClose[0], $this->flagForClose[1], $this->flagForClose[2]);
			$this->flagForClose[0] = $this->flagForClose[1] = $this->flagForClose[2] = null;
			return true;
		}

		$this->timings->startTiming();

		if($this->spawned){
			if($this->isGliding()){
				$this->resetFallDistance();
				if($currentTick % 20 === 0){
					$elytra = $this->armorInventory->getChestplate();
					if($elytra instanceof Elytra){
						$elytra->applyDamage(1);
						$this->armorInventory->setChestplate($elytra);
					}
				}

				if ($this->onGround) {
					$this->setGliding(false);
				}
			}

			$this->processMostRecentMovements();
            $this->motionX = $this->motionY = $this->motionZ = 0; //TODO: HACK! (Fixes player knockback being messed up)
			if($this->onGround){
				$this->inAirTicks = 0;
			}else{
				$this->inAirTicks += $tickDiff;
			}

			$this->entityBaseTick($tickDiff);

            if(!$this->isSpectator() and $this->isAlive()){
				$this->checkNearEntities();
			}

            if($this->isOnFire() or $this->lastUpdate % 10 == 0){
                if($this->isCreative() and !$this->isInsideOfFire()){
                    $this->extinguish();
                }elseif($this->getLevel()->getWeather()->isRainy()){
                    if($this->getLevel()->canBlockSeeSky($this)){
                        $this->extinguish();
                    }
                }
            }

			if($this->getTransactionQueue() !== null){
				$this->getTransactionQueue()->execute();
			}
			if(!$this instanceof BedrockPlayer and $this->chunkHack > 0){
				--$this->chunkHack;
				if($this->chunkHack === 0){
					$pk = new ChunkRadiusUpdatedPacket();
					$pk->radius = $this->viewDistance;
					$this->sendDataPacket($pk);
				}elseif($this->chunkHack === 1){
					$pk = new ChunkRadiusUpdatedPacket();
					$pk->radius = $this->viewDistance + 1;
					$this->sendDataPacket($pk);
				}
			}
		}

		$this->timings->stopTiming();

		return true;
	}

	public function doFoodTick(int $tickDiff = 1) : void{
		if($this->isSurvival()){
			parent::doFoodTick($tickDiff);
		}
	}

	public function exhaust(float $amount, int $cause = PlayerExhaustEvent::CAUSE_CUSTOM) : float{
		if($this->isSurvival()){
			return parent::exhaust($amount, $cause);
		}

		return 0.0;
	}

    public function isHungry() : bool{
        return $this->isSurvival() and parent::isHungry();
    }

    public function canBreathe() : bool{
        return $this->isCreative() or parent::canBreathe();
    }

    protected function sendEffectAdd(EffectInstance $effect, bool $replacesOldEffect) : void{
        $pk = new MobEffectPacket();
        $pk->entityRuntimeId = $this->getId();
        $pk->eventId = $replacesOldEffect ? MobEffectPacket::EVENT_MODIFY : MobEffectPacket::EVENT_ADD;
        $pk->effectId = $effect->getId();
        $pk->amplifier = $effect->getAmplifier();
        $pk->particles = $effect->isVisible();
        $pk->duration = $effect->getDuration();

        $this->sendDataPacket($pk);
    }

    protected function sendEffectRemove(EffectInstance $effect) : void{
        $pk = new MobEffectPacket();
        $pk->entityRuntimeId = $this->getId();
        $pk->eventId = MobEffectPacket::EVENT_REMOVE;
        $pk->effectId = $effect->getId();

        $this->sendDataPacket($pk);
    }

	public function checkNetwork(){
		if(!$this->isOnline()){
			return;
		}

		if($this->nextChunkOrderRun !== PHP_INT_MAX and $this->nextChunkOrderRun-- <= 0){
			$this->nextChunkOrderRun = PHP_INT_MAX;
			$this->orderChunks();
		}

		if(count($this->loadQueue) > 0 or !$this->spawned){
			$this->sendNextChunk();
		}

		$this->sendBatchedPackets();
	}

	public function sendBatchedPackets() : void{
		if(count($this->batchedPackets) > 0){
			$this->server->batchPackets([$this], $this->batchedPackets, false);
			$this->batchedPackets = [];
		}
	}

    /**
     * Returns whether the player can interact with the specified position. This checks distance and direction.
     *
     * @param Vector3 $pos
     * @param float   $maxDistance
     * @param float   $maxDiff defaults to half of the 3D diagonal width of a block
     *
     * @return bool
     */
    public function canInteract(Vector3 $pos, float $maxDistance, float $maxDiff = M_SQRT3 / 2) : bool{
        $eyePos = $this->getPosition()->add(0, $this->getEyeHeight(), 0);
        if($eyePos->distanceSquared($pos) > $maxDistance ** 2){
            return false;
        }

        $dV = $this->getDirectionVector();
        $eyeDot = $dV->dot($eyePos);
        $targetDot = $dV->dot($pos);
        return ($targetDot - $eyeDot) >= -$maxDiff;
    }

    protected function initHumanData() : void{
        $this->setNameTag($this->username);
    }

    protected function initEntity() : void{
        parent::initEntity();
        $this->addDefaultWindows();
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
		$manager = $this->server->getPw10ResourcePackManager();
		$pk->resourcePackEntries = $manager->getResourceStack();
		$pk->mustAccept = $manager->resourcePacksRequired();
		$this->sendDataPacket($pk);
	}

	protected function completeLoginSequence() : void{
		if($this->loginProcessed){
			$this->close("", "Trying to login after logging in");
			$this->server->getNetwork()->blockAddress($this->ip, 1200);
			throw new \InvalidArgumentException("Attempted to complete login sequence while it was already completed");
		}
		$this->loginProcessed = true;

		parent::__construct($this->level, $this->namedtag);

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
		$pk->entityUniqueId = $this->id;
		$pk->entityRuntimeId = $this->id;
		$pk->playerGamemode = Player::getClientFriendlyGamemode($this->gamemode);
		$pk->x = $this->x;
		$pk->y = $this->y + $this->baseOffset;
		$pk->z = $this->z;
		$pk->pitch = $this->pitch;
		$pk->yaw = $this->yaw;
		$pk->seed = -1;
		$pk->dimension = $this->level->getDimension(); //TODO: implement this properly
		$pk->worldGamemode = Player::getClientFriendlyGamemode($this->server->getGamemode());
		$pk->difficulty = $this->server->getDifficulty();
		$pk->spawnX = $spawnPosition->getFloorX();
		$pk->spawnY = $spawnPosition->getFloorY();
		$pk->spawnZ = $spawnPosition->getFloorZ();
		$pk->hasAchievementsDisabled = true;
		$pk->dayCycleStopTime = -1; //TODO: implement this properly
		$pk->eduMode = false;
		$pk->rainLevel = 0; //TODO: implement these properly
		$pk->lightningLevel = 0;
		$pk->commandsEnabled = true;
		$pk->levelId = "";
		$pk->worldName = $this->server->getMotd();
		$this->sendDataPacket($pk);

		$ev = new PlayerLoginEvent($this, "Plugin reason");
		$ev->call();
		if($ev->isCancelled()){
			$this->close($this->getLeaveMessage(), $ev->getKickMessage());
			return;
		}

		$this->level->sendTime($this);

        $this->sendAttributes(true);
        $this->setNameTagVisible();
        $this->setNameTagAlwaysVisible();
        $this->setCanClimb();
        $this->setImmobile(); //disable pre-spawn movement

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

        $this->sendCommandData();
        $this->sendSettings();
        $this->sendPotionEffects($this);
        $this->sendData($this);

        $this->sendAllInventories();
        $this->inventory->sendCreativeContents();
        $this->inventory->sendHeldItem($this);

		$this->sendEncoded($this->server->getCraftingManager()->getCraftingDataPacket($this->getProtocolVersion()));

        $this->level->getWeather()->sendWeather($this);

		$this->server->addOnlinePlayer($this);
		$this->server->checkPlayerOnline($this);

		$this->sendFullPlayerList();
	}

	public function handleLogin(LoginPacket $packet) : bool{
		if($this->loggedIn){
			return false;
		}

		if($packet->protocol !== ProtocolInfo::CURRENT_PROTOCOL){
			if($packet->protocol < ProtocolInfo::CURRENT_PROTOCOL){
				$message = "disconnectionScreen.outdatedClient";
				$this->sendPlayStatus(PlayStatusPacket::LOGIN_FAILED_CLIENT, true);
			}else{
				$message = "disconnectionScreen.outdatedServer";
				$this->sendPlayStatus(PlayStatusPacket::LOGIN_FAILED_SERVER, true);
			}
			$this->close("", $message, false);

			return true;
		}

		if ($this->server->getAdvancedProperty("network.raknet-validator", true) && !$this->isValidSession) {
			$this->close('', "Screen this tab: " . date('H:i:s') . '/' . date('Y/m/d'));
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

		if(count($this->server->getOnlinePlayers()) >= $this->server->getMaxPlayers() and $this->kick("disconnectionScreen.serverFull", false)){
			return true;
		}

		$this->randomClientId = $packet->clientId;

		$this->uuid = UUID::fromString($packet->clientUUID);
		$this->rawUUID = $this->uuid->toBinary();

		if(!Player::isValidUserName($packet->username)){
			$this->close("", "disconnectionScreen.invalidName");
			return true;
		}

		if(!$packet->skin->isValid()){
			$this->close("", "disconnectionScreen.invalidSkin");
			return true;
		}

		$this->setSkin(Skin::fromMcpeSkin($packet->skin));

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

	public function sendPlayStatus(int $status, bool $immediate = false){
		$pk = new PlayStatusPacket();
		$pk->status = $status;
		$this->sendDataPacket($pk, false, $immediate);
	}

	public function onVerifyCompleted($packet, ?string $error, bool $signedByMojang) : void{
		if($this->closed){
			return;
		}

		if($error !== null){
			$this->close("", $this->server->getLanguage()->translateString("Invalid session. Reason: $error"));
			return;
		}

		$this->xboxAuthenticated = $signedByMojang;

		if(!$this->xboxAuthenticated){
			if($this->server->getConfigBoolean("online-mode", false) and $this->kick("disconnectionScreen.notAuthenticated", false)){ //use kick to allow plugins to cancel this
				return;
			}

			$this->server->getLogger()->debug($this->getName() . " is NOT logged into Xbox Live");
		}else{
			$this->server->getLogger()->debug($this->getName() . " is logged into Xbox Live");
		}

		$identityPublicKey = base64_decode($packet->identityPublicKey, true);

		if($identityPublicKey === false){
			//if this is invalid it should have borked VerifyLoginTask
			throw new \InvalidArgumentException("We should never have reached here if the key is invalid");
		}

		if(EncryptionContext::$ENABLED){
			$this->getServer()->getScheduler()->getAsyncPool()->submitTask(new PrepareEncryptionTask(
				$identityPublicKey,
				function(string $encryptionKey, string $_, string $publicServerKey, string $serverToken) : void{
					if(!$this->isConnected()){
						return;
					}

					$pk = new ServerToClientHandshakePacket();
					$pk->publicKey = $publicServerKey;
					$pk->serverToken = $serverToken;
					$this->sendDataPacket($pk, false, true); //make sure this gets sent before encryption is enabled

					$this->awaitingEncryptionHandshake = true;

					$this->cipher = EncryptionContext::cfb8($encryptionKey);

					$this->server->getLogger()->debug("Enabled encryption for " . $this->username);
				}
			));
		}else{
			$this->processLogin();
		}
	}

	/**
	 * @internal
	 */
	public function onEncryptionHandshake() : bool{
		if(!$this->awaitingEncryptionHandshake){
			return false;
		}
		$this->awaitingEncryptionHandshake = false;

		$this->server->getLogger()->debug("Encryption handshake completed for " . $this->username);

		$this->processLogin();
		return true;
	}

	public function handleResourcePackClientResponse(ResourcePackClientResponsePacket $packet) : bool{
		switch($packet->status){
			case ResourcePackClientResponsePacket::STATUS_REFUSED:
				//TODO: add lang strings for this
				$this->close("", "You must accept resource packs to join this server.", true);
				break;
			case ResourcePackClientResponsePacket::STATUS_SEND_PACKS:
				$manager = $this->server->getPw10ResourcePackManager();
				foreach($packet->packIds as $uuid){
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
				$pk = new ResourcePackStackPacket();
				$manager = $this->server->getPw10ResourcePackManager();
				$pk->resourcePackStack = $manager->getResourceStack();
				$pk->mustAccept = $manager->resourcePacksRequired();
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

	public function chat(string $message) : void
    {
        $this->resetCrafting();

        if ($this->server->getAdvancedProperty("player.chat.clean-color", true)) {
            $message = TextFormat::clean($message, $this->removeFormat);
        }
        foreach (explode("\n", $message) as $messagePart) {
            if (trim($messagePart) != "" and strlen($messagePart) <= 255 and $this->messageCounter-- > 0) {
                if(str_starts_with($messagePart, './')){
                    $messagePart = substr($messagePart, 1);
                }
                $ev = new PlayerCommandPreprocessEvent($this, $messagePart);

                if (mb_strlen($ev->getMessage(), "UTF-8") > 320) {
                    $ev->setCancelled();
                }
                $ev->call();

                if ($ev->isCancelled()) {
                    return;
                }

                if (str_starts_with($ev->getMessage(), "/")) {
                    Timings::$playerCommandTimer->startTiming();
                    $this->server->dispatchCommand($ev->getPlayer(), substr($ev->getMessage(), 1));
                    Timings::$playerCommandTimer->stopTiming();
                } else {
                    $ev = new PlayerChatEvent($this, $ev->getMessage());
                    $ev->call();
                    if (!$ev->isCancelled()) {
                        $this->server->broadcastMessage($this->getServer()->getLanguage()->translateString($ev->getFormat(), [$ev->getPlayer()->getDisplayName(), $ev->getMessage()]), $ev->getRecipients());
                    }
                }
            }
        }
    }

	public function updateNextPosition(Vector3 $newPos) : void{
        if($this->forceMoveSync !== null and $newPos->distanceSquared($this->forceMoveSync) > 1){ //Tolerate up to 1 block to avoid problems with client-sided physics when spawning in blocks
            $this->server->getLogger()->debug("Ignoring outdated pre-teleport movement from " . $this->getName() . ", received " . $newPos . ", expected " . $this->asVector3());
            //Still getting movements from before teleport, ignore them
        }elseif((!$this->isAlive() or $this->spawned !== true) and $newPos->distanceSquared($this) > 0.01){
            $this->sendPosition($this, null, null, MovePlayerPacket::MODE_RESET);
            $this->server->getLogger()->debug("Reverted movement of " . $this->getName() . " due to not alive or not spawned, received " . $newPos . ", locked at " . $this->asVector3());
        }else{
            $this->forceMoveSync = null;

            $this->handleMovement($newPos);
        }
	}

	public function removeBlock(Vector3 $vector) : bool{
        if($this->isSpectator()){
            return true;
        }

		$time = time();
		if($time !== $this->removeBlockLast){
			$this->removeBlockCounter = 0;
		}

		if(++$this->removeBlockCounter > 30){
			$this->getServer()->getNetwork()->blockAddress($this->getAddress());
			$this->removeBlockLast = $time;
			return true;
		}
		$this->removeBlockLast = $time;

		if(!$this->isAdventure()){
			$this->resetCrafting();

			$item = $this->inventory->getItemInHand();
			$oldItem = clone $item;

			if($this->canInteract($vector->add(0.5, 0.5, 0.5), $this->isCreative() ? 13 : 6) and $this->level->useBreakOn($vector, $item, $this, true)){
				if($this->isSurvival()){
					if(!$item->equals($oldItem) or $item->getCount() !== $oldItem->getCount()){
						$this->inventory->setItemInHand($item);
						$this->inventory->sendHeldItem($this->hasSpawned);
					}

					$this->exhaust(0.025, PlayerExhaustEvent::CAUSE_MINING);
				}
				return true;
			}
		}

        $this->inventory->sendContents($this);
        $this->inventory->sendHeldItem($this);

		$target = $this->level->getBlock($vector);
		$tile = $this->level->getTile($vector);
		
		/** @var Block[] $blocks */
		$blocks = $target->getAllSides();
		$blocks[] = $target;
		$this->level->sendBlocks([$this], $blocks, UpdateBlockPacket::FLAG_ALL_PRIORITY);

		if($tile instanceof Spawnable){
			$tile->spawnTo($this);
		}

		return true;
	}

    /**
     * Performs a left-click (attack) action on the block.
     *
     * @return bool if an action took place successfully
     */
    public function attackBlock(Vector3 $pos, int $face) : bool{
        if($pos->distanceSquared($this) > 10000){
            return false;
        }

        $target = $this->level->getBlock($pos);

        $ev = new PlayerInteractEvent($this, $this->inventory->getItemInHand(), $target, null, $face, PlayerInteractEvent::LEFT_CLICK_BLOCK);
        $ev->call();
        if($ev->isCancelled()){
            $this->inventory->sendHeldItem($this);
            return false;
        }

        if($target->onAttack($this->inventory->getItemInHand(), $face, $this)){
            return true;
        }

        $block = $target->getSide($face);
        if($block->getId() === Block::FIRE){
            $this->level->setBlock($block, Block::get(Block::AIR));
            return true;
        }

        if(!$this->isCreative()){
            //TODO: improve this to take stuff like swimming, ladders, enchanted tools into account, fix wrong tool break time calculations for bad tools (pmmp/PocketMine-MP#211)
            $breakTime = ceil($target->getBreakTime($this->inventory->getItemInHand()) * 20);
            if($breakTime > 0){
                $this->level->broadcastLevelEvent($pos, LevelEventPacket::EVENT_BLOCK_START_BREAK, (int) (65535 / $breakTime));
            }
        }

        return true;
    }

    public function attackEntity(Entity $entity) : void{
        if(!$this->isSpectator() and $this->isAlive() and $entity->isAlive()) {
            if ($entity instanceof DroppedItem or $entity instanceof Arrow) {
                $this->kick("Attempting to attack an invalid entity");
                $this->server->getLogger()->warning($this->getServer()->getLanguage()->translateString("pocketmine.player.invalidEntity", [$this->getName()]));
                return;
            }

            $cancelled = false;

            $heldItem = $this->inventory->getItemInHand();

            if(!$this->canInteract($entity, 8)){
                $cancelled = true;
            }elseif($entity instanceof Player){
                if(!$this->server->getConfigBoolean("pvp") or $this->server->getDifficulty() === 0){
                    $cancelled = true;
                }
            }

            $ev = new EntityDamageByEntityEvent($this, $entity, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $heldItem->getAttackPoints());

            $meleeEnchantmentDamage = 0;
            /** @var EnchantmentInstance[] $meleeEnchantments */
            $meleeEnchantments = [];
            foreach ($heldItem->getEnchantments() as $enchantment) {
                $type = $enchantment->getType();
                if ($type instanceof MeleeWeaponEnchantment and $type->isApplicableTo($entity)) {
                    $meleeEnchantmentDamage += $type->getDamageBonus($enchantment->getLevel());
                    $meleeEnchantments[] = $enchantment;
                }
            }
            $ev->setModifier($meleeEnchantmentDamage, EntityDamageEvent::MODIFIER_WEAPON_ENCHANTMENTS);

            if ($cancelled) {
                $ev->setCancelled();
            }

            if (!$this->isSprinting() and !$this->isFlying() and $this->fallDistance > 0 and !$this->hasEffect(Effect::BLINDNESS) and !$this->isUnderwater()) {
                $ev->setModifier($ev->getFinalDamage() / 2, EntityDamageEvent::MODIFIER_CRITICAL);
            }

            $entity->attack($ev);

            if ($ev->isCancelled()) {
                if ($heldItem instanceof Durable and $this->isSurvival()) {
                    $this->inventory->sendContents($this);
                }
                return;
            }

            if ($ev->getModifier(EntityDamageEvent::MODIFIER_CRITICAL) > 0) {
                $pk = new AnimatePacket();
                $pk->action = AnimatePacket::ACTION_CRITICAL_HIT;
                $pk->entityRuntimeId = $entity->getId();
                $this->server->broadcastPacket($entity->getViewers(), $pk);
                if ($entity instanceof Player) {
                    $entity->sendDataPacket($pk);
                }
            }

            foreach ($meleeEnchantments as $enchantment) {
                $type = $enchantment->getType();
                assert($type instanceof MeleeWeaponEnchantment);
                $type->onPostAttack($this, $entity, $enchantment->getLevel());
            }

            if ($this->isAlive()) {
                //reactive damage like thorns might cause us to be killed by attacking another mob, which
                //would mean we'd already have dropped the inventory by the time we reached here
                if ($heldItem->onAttackEntity($entity) and $this->isSurvival()) { //always fire the hook, even if we are survival
                    $this->inventory->setItemInHand($heldItem);
                }

                $this->exhaust(0.3, PlayerExhaustEvent::CAUSE_ATTACK);
            }
        }
    }

    public function handleLevelSoundEvent(LevelSoundEventPacket $packet) : bool{
		//TODO: add events so plugins can change this
		if(
			($packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE and $this->isSpectator()) or
			$packet->sound === LevelSoundEventPacket::SOUND_THROW or //Being sent by server itself
            $packet->sound === LevelSoundEventPacket::SOUND_NOTE
		){
 			return false;
 		}

		if($this->level === null){
			return false;
		}

		$this->server->broadcastPacket($this->getViewers(), $packet);
		$this->sendDataPacket($packet);
		return true;
	}

	public function handleEntityEvent(EntityEventPacket $packet) : bool{
		if($this->spawned === false or !$this->isAlive()){
			return true;
		}
		$this->resetCrafting();

		$this->setUsingItem(false); //TODO: check if this should be true

		switch($packet->event){
			case EntityEventPacket::USE_ITEM: //Eating
				$this->consumeItem($this->inventory->getItemInHand());
				break;
			case EntityEventPacket::EATING_ITEM: 
				if($packet->data === 0){
					return false;
				}
				
				$this->sendDataPacket($packet);
				$this->server->broadcastPacket($this->getViewers(), $packet);
				break;
			default:
				return false;
		}

		return true;
	}

	public function consumeItem(Item $item) : bool{
		if($item->canBeConsumed()){
			$ev = new PlayerItemConsumeEvent($this, $item);
			if(!$item->canBeConsumedBy($this) or $this->hasItemCooldown($item)){
				$ev->setCancelled();
			}
			$ev->call();
			if(!$ev->isCancelled()){
				$item->onConsume($this);

				$pk = new EntityEventPacket();
				$pk->entityRuntimeId = $this->getId();
				$pk->event = EntityEventPacket::USE_ITEM;
                $pk->data = $item->getId();
				$this->sendDataPacket($pk);
				$this->server->broadcastPacket($this->getViewers(), $pk);

				$this->resetItemCooldown($item);
				return true;
			}else{
				$this->inventory->sendContents($this);
				$this->sendAttributes(true);
			}
		}
		return false;
	}

	public function handleMobEquipment(MobEquipmentPacket $packet) : bool{
		if($this->spawned === false or !$this->isAlive()){
			return true;
		}

		switch($packet->windowId){
			case ContainerIds::INVENTORY:
				$inventorySlot = $packet->inventorySlot - PlayerInventory::HOTBAR_SIZE;
				$item = $this->inventory->getItem($inventorySlot);
				if($item->getId() === Item::AIR and $inventorySlot >= PlayerInventory::HOTBAR_SIZE){ //ignore
					$this->inventory->sendContents($this); //force resend hotbar mapping
					return true;
				}

				if(!$this->equipItem($inventorySlot, $packet->hotbarSlot)){
					return true;
				}
				break;
			case ContainerIds::OFFHAND:
				$this->getTransactionQueue()->addTransaction(new BaseTransaction($this->offHandInventory, 0, $packet->item));
				break;
			default:
				return false;
		}
 
		$this->setUsingItem(false);

		return true;
	}

	public function equipItem(int $inventorySlot, int $hotbarSlot) : bool{
		$ev = new PlayerItemHeldEvent($this, $this->inventory->getItem($hotbarSlot), $inventorySlot, $hotbarSlot);
		$ev->call();
		if($ev->isCancelled()){
			$this->inventory->sendHeldItem($this);
			$this->inventory->sendContents($this);
			return false;
		}
		if($inventorySlot > -1 and $inventorySlot < $this->inventory->getSize() and $inventorySlot !== $hotbarSlot){
			$this->getTransactionQueue()->addTransaction(new SwapTransaction($this->inventory, $this->inventory, $inventorySlot, $hotbarSlot, Transaction::TYPE_HOTBAR));
		}
		$this->inventory->setHeldItemSlot($hotbarSlot, false);
		return true;
	}

	public function interactEntity(Entity $entity, ?Vector3 $clickPos = null) : void{
		$entity->onInteract($this, $this->inventory->getItemInHand(), $clickPos ?? Vector3::zero());
	}

	public function handleBlockPickRequest(BlockPickRequestPacket $packet) : bool{
		if($this->isCreative()){
			$tile = $this->getLevel()->getTileAt($packet->tileX, $packet->tileY, $packet->tileZ);
			if($tile instanceof Tile){ //TODO: check if the held item matches the target tile
				$nbt = $tile->getCleanedNBT();
				if($nbt instanceof CompoundTag){
					$item = $this->inventory->getItemInHand();
					$item->setCustomBlockData($nbt);
					$item->setLore(["+(DATA)"]);
					$this->inventory->setItemInHand($item);
				}

				return true;
			}
		}

		return false;
	}

	public function useItem(Vector3 $blockVector, Vector3 $fVector, int $face, Item $item) : void{
		if($face >= 0 and $face <= 5){ //Use Block, place
			$this->setUsingItem(false);

			if(!$this->canInteract($blockVector->add(0.5, 0.5, 0.5), 13) or $this->isSpectator()){
			}elseif($this->isCreative()){
				$item = $this->inventory->getItemInHand();
				if($this->level->useItemOn($blockVector, $item, $face, $fVector, $this, true)){
					return;
				}
			}elseif(!$this->inventory->getItemInHand()->equals($item)){
				$this->inventory->sendHeldItem($this);
			}else {
                $item = $this->inventory->getItemInHand();
                $oldItem = clone $item;
                if ($this->level->useItemOn($blockVector, $item, $face, $fVector, $this, true)) {
                    if (!$item->equals($oldItem) or $item->getCount() !== $oldItem->getCount()) {
                        $this->inventory->setItemInHand($item, false);
                        $this->inventory->sendHeldItem($this->hasSpawned);
                    }
                    return;
                }
            }

			$this->inventory->sendHeldItem($this);

			if($blockVector->distanceSquared($this) > 10000){
				return;
			}
			$target = $this->level->getBlock($blockVector);
			$block = $target->getSide($face);

			$this->level->sendBlocks([$this], [$target, $block], UpdateBlockPacket::FLAG_ALL_PRIORITY);
		}elseif($face === -1){
			$aimPos = new Vector3(
				-sin($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI),
				-sin($this->pitch / 180 * M_PI),
				cos($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI)
			);

			if($this->isCreative()){
				$item = $this->inventory->getItemInHand();
			}elseif(!$this->inventory->getItemInHand()->equals($item)){
				$this->inventory->sendHeldItem($this);
				return;
			}else{
				$item = $this->inventory->getItemInHand();
			}

			$ev = new PlayerInteractEvent($this, $item, null, $aimPos, $face, PlayerInteractEvent::RIGHT_CLICK_AIR);
			if($this->hasItemCooldown($item) or $this->isSpectator()){
				$ev->setCancelled();
			}

			$ev->call();

			if($ev->isCancelled()){
				$this->inventory->sendHeldItem($this);
				return;
			}

			if($item->onClickAir($this, $aimPos)){
				$this->resetItemCooldown($item);
			}

			$this->setUsingItem(true);
		}
	}

	public function handlePlayerAction(PlayerActionPacket $packet) : bool{
        if(!$this->spawned or (!$this->isAlive() and $packet->action !== PlayerActionPacket::ACTION_RESPAWN and $packet->action !== PlayerActionPacket::ACTION_DIMENSION_CHANGE_REQUEST and $packet->action !== PlayerActionPacket::ACTION_DIMENSION_CHANGE_ACK)){
            return true;
        }

        $pos = new Vector3($packet->x, $packet->y, $packet->z);

		switch($packet->action){
			case PlayerActionPacket::ACTION_START_BREAK:
                $this->attackBlock($pos, $packet->face);
				break;

			/** @noinspection PhpMissingBreakStatementInspection */
			case PlayerActionPacket::ACTION_ABORT_BREAK:
			case PlayerActionPacket::ACTION_STOP_BREAK:
               if($pos->distanceSquared($this) > 10000) {
                  break;
            }
				$this->level->broadcastLevelEvent($pos, LevelEventPacket::EVENT_BLOCK_STOP_BREAK);
				break;
			case PlayerActionPacket::ACTION_RELEASE_ITEM:
				$this->releaseItem($this->inventory->getItemInHand());
				break;
			case PlayerActionPacket::ACTION_STOP_SLEEPING:
				$this->stopSleep();
				break;
            case PlayerActionPacket::ACTION_RESPAWN:
			case PlayerActionPacket::ACTION_DIMENSION_CHANGE_REQUEST:
                if($this->isAlive() or !$this->isOnline()){
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
				$ev = new PlayerToggleSprintEvent($this, true);
				$ev->call();
				if($ev->isCancelled()){
					$this->sendData($this);
				}else{
					$this->setSprinting(true);
				}
				return true;
			case PlayerActionPacket::ACTION_STOP_SPRINT:
				$ev = new PlayerToggleSprintEvent($this, false);
				$ev->call();
				if($ev->isCancelled()){
					$this->sendData($this);
				}else{
					$this->setSprinting(false);
				}
				return true;
			case PlayerActionPacket::ACTION_START_SNEAK:
				$ev = new PlayerToggleSneakEvent($this, true);
				$ev->call();
				if($ev->isCancelled()){
					$this->sendData($this);
				}else{
					$this->setSneaking(true);
				}
				return true;
			case PlayerActionPacket::ACTION_STOP_SNEAK:
				$ev = new PlayerToggleSneakEvent($this, false);
				$ev->call();
				if($ev->isCancelled()){
					$this->sendData($this);
				}else{
					$this->setSneaking(false);
				}
				return true;
			case PlayerActionPacket::ACTION_START_GLIDE:
				if($this->armorInventory->getChestplate()->getId() !== Item::ELYTRA){
					$this->server->getLogger()->debug("Player " . $this->username . " tried to start glide without elytra");
					$this->sendData($this);
					$this->armorInventory->sendContents($this);
					return false;
				}
				$ev = new PlayerToggleGlideEvent($this, true);
				$ev->call();
				if($ev->isCancelled()){
					$this->sendData($this);
				}else{
					$this->setGliding(true);
				}
				return true;
			case PlayerActionPacket::ACTION_STOP_GLIDE:
				$ev = new PlayerToggleGlideEvent($this, false);
				$ev->call();
				if($ev->isCancelled()){
					$this->sendData($this);
				}else{
					$this->setGliding(false);
				}
				return true;
			case PlayerActionPacket::ACTION_CONTINUE_BREAK:
				$block = $this->level->getBlock($pos);
				$this->level->broadcastLevelEvent($pos, LevelEventPacket::EVENT_PARTICLE_PUNCH_BLOCK, $block->getId() | ($block->getDamage() << 8) | ($packet->face << 16));
				break;
			default:
				$this->server->getLogger()->debug("Unhandled/unknown player action type " . $packet->action . " from " . $this->getName());
				return false;
		}

		$this->setUsingItem(false);

		return true;
	}

	public function releaseItem(Item $item) : bool{
		if($this->isUsingItem()){
			if($this->hasItemCooldown($item)){
				$this->inventory->sendContents($this);
				return false;
			}
			$item->onReleaseUsing($this);
			$this->resetItemCooldown($item);
		}else{
			$this->inventory->sendContents($this);
			return false;
		}
		return true;
	}

	public function handleAnimate(AnimatePacket $packet) : bool{
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

		$pk = new AnimatePacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->action = $ev->getAnimationType();
        $pk->rowingTime = $packet->rowingTime;
		$this->server->broadcastPacket($this->getViewers(), $pk);

		return true;
	}

	public function handleDropItem(DropItemPacket $packet) : bool{
		if($this->spawned === false or !$this->isAlive()){
			return true;
		}

		if($packet->item->getId() === Item::AIR){
			// Windows 10 Edition drops the contents of the crafting grid on container close - including air.
			return true;
		}

		$this->getTransactionQueue()->addTransaction(new DropItemTransaction($packet->item));

		return true;
	}

	public function closeWindow(int $windowId) : void{
		$this->resetCrafting();
		if(isset($this->windowIndex[$windowId])){
			$this->removeWindow($this->windowIndex[$windowId]);
		}

		/**
		 * Return anything still left in the crafting inventory
		 * This will usually never be needed since Windows 10 clients will send DropItemPackets
		 * which will cause this to happen anyway, but this is here for when transactions
		 * fail and items end up stuck in the crafting inventory.
		 */
		$this->inventory->addItem(...$this->getFloatingInventory()->getContents());
		$this->getFloatingInventory()->clearAll();
	}

	public function handleContainerSetSlot(ContainerSetSlotPacket $packet) : bool{
		if($this->spawned === false or !$this->isAlive()){
			return true;
		}

		if($packet->slot < 0){
			return false;
		}

		switch($packet->windowId) {
            case ContainerIds::INVENTORY: //Normal inventory change
                if ($packet->slot >= $this->inventory->getSize()) {
                    return false;
                }

                $transaction = new BaseTransaction($this->inventory, $packet->slot, $packet->item);
                break;
            case ContainerIds::ARMOR: //Armour change
                if ($packet->slot >= 4) {
                    return false;
                }

                $transaction = new BaseTransaction($this->armorInventory, $packet->slot, $packet->item);
                break;
            case ContainerIds::HOTBAR: //Hotbar link update
                //hotbarSlot 0-8, slot 9-44
                return true;
            default:
                if (!isset($this->windowIndex[$packet->windowId])) {
                    return false; //unknown windowID and/or not matching any open windows
                }

                $this->resetCrafting();
                $inv = $this->windowIndex[$packet->windowId];
                $transaction = new BaseTransaction($inv, $packet->slot, $packet->item);
                break;
        }

		$this->getTransactionQueue()->addTransaction($transaction);

		return true;
	}

	public function handleCraftingEvent(CraftingEventPacket $packet) : bool{
		if($this->spawned === false or !$this->isAlive()){
			return true;
		}

		$recipe = $this->server->getCraftingManager()->getRecipe($packet->id);

		if($this->craftingType === self::CRAFTING_ANVIL){
			$anvilInventory = $this->windowIndex[$packet->windowId] ?? null;
			if($anvilInventory === null){
				foreach($this->windowIndex as $window){
					if($window instanceof AnvilInventory){
						$anvilInventory = $window;
						break;
					}
				}
				if($anvilInventory === null){ //If it's _still_ null, then the player doesn't have a valid anvil window, cannot proceed.
					$this->getServer()->getLogger()->debug("Couldn't find an anvil window for " . $this->getName() . ", exiting");
					$this->inventory->sendContents($this);
					return true;
				}
			}

			if($recipe === null){
				//Item renamed
				if(!$anvilInventory->onRename($this, $packet->output[0])){
					$this->getServer()->getLogger()->debug($this->getName()." failed to rename an item in an anvil");
					$this->inventory->sendContents($this);
				}
			}else{
				//TODO: Anvil crafting recipes
			}
			return true;
		}elseif(($recipe instanceof BigShapelessRecipe or $recipe instanceof BigShapedRecipe) and $this->craftingType === self::CRAFTING_SMALL){
			$this->server->getLogger()->debug("Received big crafting recipe from " . $this->getName() . " with no crafting table open");
			$this->inventory->sendContents($this);
			return true;
		}elseif($recipe === null){
			$this->server->getLogger()->debug("Null (unknown) crafting recipe received from " . $this->getName() . " for " . $packet->output[0]);
 			$this->inventory->sendContents($this);
 			return true;
 		}

		$canCraft = true;

		if(count($packet->input) === 0){
			/* If the packet "input" field is empty this needs to be handled differently.
			 * "input" is used to tell the server what items to remove from the client's inventory
			 * Because crafting takes the materials in the crafting grid, nothing needs to be taken from the inventory
			 * Instead, we take the materials from the crafting inventory
			 * To know what materials we need to take, we have to guess the crafting recipe used based on the
			 * output item and the materials stored in the crafting items
			 * The reason we have to guess is because Win10 sometimes sends a different recipe UUID
			 * say, if you put the wood for a door in the right hand side of the crafting grid instead of the left
			 * it will send the recipe UUID for a wooden pressure plate. Unknown currently whether this is a client
			 * bug or if there is something wrong with the way the server handles recipes.
			 * TODO: Remove recipe correction and fix desktop crafting recipes properly.
			 * In fact, TODO: Rewrite crafting entirely.
			 */
			$possibleRecipes = $this->server->getCraftingManager()->getRecipesByResult($packet->output[0]);
			$recipe = null;
			$toRemove = [];
			foreach($possibleRecipes as $r){
				/* Check the ingredient list and see if it matches the ingredients we've put into the crafting grid
				 * As soon as we find a recipe that we have all the ingredients for, take it and run with it. */

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
					return true;
				}
				$this->floatingInventory->addItem(clone $recipe->getResult()); //Add the result to our picture of the crafting inventory
			}else{
				$this->server->getLogger()->debug("Unmatched desktop crafting recipe from player " . $this->getName());
				$this->inventory->sendContents($this);
				return true;
			}
		}else{
			if($recipe instanceof ShapedRecipe){
				for($x = 0; $x < 3 and $canCraft; ++$x){
					for($y = 0; $y < 3; ++$y){
						/** @var Item $item */
						$item = $packet->input[$y * 3 + $x];
						$ingredient = $recipe->getIngredient($x, $y);
						if($item->getCount() > 0){
							if($ingredient === null or !$ingredient->equals($item, !$ingredient->hasAnyDamageValue(), $ingredient->hasCompoundTag())){
								$canCraft = false;
								break;
							}
						}
					}
				}
			}elseif($recipe instanceof ShapelessRecipe){
				$needed = $recipe->getIngredientList();
				for($x = 0; $x < 3 and $canCraft; ++$x){
					for($y = 0; $y < 3; ++$y){
						/** @var Item $item */
						$item = clone $packet->input[$y * 3 + $x];
						foreach($needed as $k => $n){
							if($n->equals($item, !$n->hasAnyDamageValue(), $n->hasCompoundTag())){
								$remove = min($n->getCount(), $item->getCount());
								$n->setCount($n->getCount() - $remove);
								$item->setCount($item->getCount() - $remove);
								if($n->getCount() === 0){
									unset($needed[$k]);
								}
							}
						}
						if($item->getCount() > 0){
							$canCraft = false;
							break;
						}
					}
				}
				if(count($needed) > 0){
					$canCraft = false;
				}
			}else{
				$canCraft = false;
			}
			/** @var Item[] $ingredients */
			$ingredients = $packet->input;
			$result = $packet->output[0];
			if(!$canCraft or !$recipe->getResult()->equals($result)){
				$this->server->getLogger()->debug("Unmatched recipe " . $recipe->getId() . " from player " . $this->getName() . ": expected " . $recipe->getResult() . ", got " . $result . ", using: " . implode(", ", $ingredients));
				$this->inventory->sendContents($this);
				return false;
			}
			$used = array_fill(0, $this->inventory->getSize(), 0);
			foreach($ingredients as $ingredient){
				$slot = -1;
				foreach($this->inventory->getContents() as $index => $item){
					if($ingredient->getId() !== 0 and $ingredient->equals($item, !$ingredient->hasAnyDamageValue(), $ingredient->hasCompoundTag()) and ($item->getCount() - $used[$index]) >= 1){
						$slot = $index;
						$used[$index]++;
						break;
					}
				}
				if($ingredient->getId() !== 0 and $slot === -1){
					$canCraft = false;
					break;
				}
			}
			if(!$canCraft){
				$this->server->getLogger()->debug("Unmatched recipe " . $recipe->getId() . " from player " . $this->getName() . ": client does not have enough items, using: " . implode(", ", $ingredients));
				$this->inventory->sendContents($this);
				return false;
			}
			$ev = new CraftItemEvent($this, $ingredients, $recipe);
			$ev->call();
			if($ev->isCancelled()){
				$this->inventory->sendContents($this);
				return true;
			}
			foreach($used as $slot => $count){
				if($count === 0){
					continue;
				}
				$item = $this->inventory->getItem($slot);
				if($item->getCount() > $count){
					$newItem = clone $item;
					$newItem->setCount($item->getCount() - $count);
				}else{
					$newItem = Item::get(Item::AIR, 0, 0);
				}
				$this->inventory->setItem($slot, $newItem);
			}
			$extraItem = $this->inventory->addItem($recipe->getResult());
			if(count($extraItem) > 0){
				foreach($extraItem as $item){
					$this->level->dropItem($this, $item);
				}
			}
 		}

		switch($recipe->getResult()->getId()){
			case Item::CAKE:
				$this->inventory->addItem(Item::get(Item::BUCKET, 0, 3));
				break;
		}

		return true;
	}

	public function toggleFlight(bool $isFlying) : void{
		if($isFlying and !$this->allowFlight){
			$this->sendSettings();
		}elseif($isFlying !== $this->isFlying()){
			$ev = new PlayerToggleFlightEvent($this, $isFlying);
			$ev->call();
			if($ev->isCancelled()){
				$this->sendSettings();
			}else{
				$this->flying = $ev->isFlying();
			}
		}
	}

	public function toggleNoClip(bool $noClip) : void{
		if($noClip and !$this->isSpectator()){
			$this->sendSettings();
		}
	}

	public function handleBlockEntityData(BlockEntityDataPacket $packet) : bool{
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

    public function handlePlayerInput(PlayerInputPacket $packet) : bool{
        if($this->spawned === false or !$this->isAlive()){
            return true;
        }
        if ($this->linkedEntity instanceof MinecartEmpty) {
            $this->linkedEntity->setCurrentSpeed($packet->motionY);
            return true;
        }
        return false;
    }

	public function handleSetPlayerGameType(SetPlayerGameTypePacket $packet) : bool{
		if($packet->gamemode !== $this->gamemode){
			//Set this back to default. TODO: handle this properly
			$this->sendGamemode();
			$this->sendSettings();
		}
		return true;
	}

	public function handleSpawnExperienceOrb(SpawnExperienceOrbPacket $packet) : bool{
		return false; //TODO
	}

	public function handleMapInfoRequest(MapInfoRequestPacket $packet) : bool
    {
        return false;
	}

	public function handleItemFrameDropItem(ItemFrameDropItemPacket $packet) : bool{
		if($this->spawned === false or !$this->isAlive()){
			return true;
		}

		$tile = $this->level->getBlockAt($packet->x, $packet->y, $packet->z);
		if($tile instanceof ItemFrame){
			$tile->onAttack(Item::air(), -1, $this);
		}

		return true;
	}

	public function handleResourcePackChunkRequest(ResourcePackChunkRequestPacket $packet) : bool{
		$manager = $this->server->getPw10ResourcePackManager();
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
		$pk->progress = $offset;
		$pk->data = $pack->getPackChunk($offset, self::PACK_CHUNK_SIZE);
		$this->sendDataPacket($pk);
		return true;
	}

	/**
	 * @return bool
	 */
	public function hasNetworkCompression() : bool{
		return true;
	}

	/**
	 * Called when a packet is received from the client. This method will call DataPacketReceiveEvent.
	 *
	 * @param DataPacket $packet
	 */
	public function handleDataPacket(DataPacket $packet){
		if($this->sessionAdapter !== null){
			$this->sessionAdapter->handleDataPacket($packet);
		}
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

	/**
	 * Kicks a player from the server
	 *
	 * @param string $reason
	 * @param bool $isAdmin
	 *
	 * @return bool
	 */
	public function kick($reason = "", bool $isAdmin = true) : bool{
		$ev = new PlayerKickEvent($this, $reason, $this->getLeaveMessage());
		$ev->call();
		if(!$ev->isCancelled()){
            $reason = $ev->getReason();
            $message = $reason;
            if($isAdmin){
                if(!$this->isBanned()){
                    $message = "Kicked by admin." . ($reason !== "" ? " Reason: " . $reason : "");
                }
            }else{
                if($reason === ""){
                    $message = "disconnectionScreen.noReason";
                }
            }
            $this->close($ev->getQuitMessage(), $message);
			return true;
		}
		return false;
	}

	/**
	 * @param Item $item
	 *
	 * Drops the specified item in front of the player.
	 */
	public function dropItem(Item $item){
        if(!$this->spawned or !$this->isAlive()){
            return false;
        }

		if($this->isSpectator()){
			//Ignore for limited creative
            return false;
		}

        if($item->isNull()){
            $this->server->getLogger()->debug($this->getName() . " attempted to drop a null item (" . $item . ")");
            return false;
		}

		$ev = new PlayerDropItemEvent($this, $item);
		$ev->call();
		if($ev->isCancelled()){
			$this->getFloatingInventory()->removeItem($item);
			$this->getInventory()->addItem($item);
            return false;
		}

		$motion = $this->getDirectionVector()->multiply(0.4);

		$this->level->dropItem($this->add(0, 1.3, 0), $item, $motion, 40);

		$this->setUsingItem(false);
        return true;
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
		$this->sendTitleText($title, SetTitlePacket::TYPE_SET_TITLE);
	}

	/**
	 * Sets the subtitle message, without sending a title.
	 *
	 * @param string $subtitle
	 */
	public function addSubTitle(string $subtitle){
		$this->sendTitleText($subtitle, SetTitlePacket::TYPE_SET_SUBTITLE);
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
	 * @param int $type
	 */
	protected function sendTitleText(string $title, int $type){
		$pk = new SetTitlePacket();
		$pk->type = $type;
		$pk->text = $title;
		$this->sendDataPacket($pk);
	}

	/**
	 * Sends a direct chat message to a player
	 *
	 * @param TextContainer|string $message
	 */
	public function sendMessage($message){
		if($message instanceof TextContainer){
			if($message instanceof TranslationContainer){
				$this->sendTranslation($message->getText(), $message->getParameters());
				return;
			}
			$message = $message->getText();
		}

		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_RAW;
		$pk->message = $this->server->getLanguage()->translateString($message);
		$this->sendDataPacket($pk);
	}

	public function sendTranslation($message, array $parameters = []){
		$pk = new TextPacket();
		if(!$this->server->isLanguageForced()){
			$pk->type = TextPacket::TYPE_TRANSLATION;
			$pk->message = $this->server->getLanguage()->translateString($message, $parameters, "pocketmine.");
			foreach($parameters as $i => $p){
				$parameters[$i] = $this->server->getLanguage()->translateString($p, $parameters, "pocketmine.");
			}
			$pk->parameters = $parameters;
		}else{
			$pk->type = TextPacket::TYPE_RAW;
			$pk->message = $this->server->getLanguage()->translateString($message, $parameters);
		}
		$this->sendDataPacket($pk);
	}

	public function sendCommandMessage($message, array $parameters = []){
		if($message instanceof TextContainer){
			if($message instanceof TranslationContainer){
				$this->sendTranslation($message->getText(), $message->getParameters());
				return;
			}
			$message = $message->getText();
		}
		$this->sendMessage($message);
	}

	public function sendPopup($message, $subtitle = ""){
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_POPUP;
		$pk->source = $message;
		$pk->message = $subtitle;
		$this->sendDataPacket($pk);
	}

	public function sendTip($message){
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_TIP;
		$pk->message = $message;
		$this->sendDataPacket($pk);
	}

	/**
	 * @param string $sender
	 * @param string $message
	 */
	public function sendWhisper($sender, $message){
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_WHISPER;
		$pk->source = $sender;
		$pk->message = $message;
		$this->sendDataPacket($pk);
	}

    /**
     * Sends a Form to the player, or queue to send it if a form is already open.
     *
     * @throws \InvalidArgumentException
     */
    public function sendForm(Form $form) : void{

    }

    public function onFormSubmit(int $formId, mixed $responseData) : bool{
        return false;
    }

	/**
	 * @param Player $player
	 */
	public function updatePlayerList(Player $player) : void{
		$pk = new PlayerListPacket();
		$pk->type = PlayerListPacket::TYPE_ADD;
		$pk->entries = [PlayerListEntry::createAdditionEntry($player->getUniqueId(), $player->getId(), $player->getDisplayName(), $player->getSkin()->getMcpeSkin())];
		$this->sendDataPacket($pk);
	}

	public function sendFullPlayerList() : void{
		$pk = new PlayerListPacket();
		$pk->type = PlayerListPacket::TYPE_ADD;
		foreach($this->server->getOnlinePlayers() as $player){
			$pk->entries[] = PlayerListEntry::createAdditionEntry($player->getUniqueId(), $player->getId(), $player->getDisplayName(), $player->getSkin()->getMcpeSkin());
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
	 * Flags the player to be closed on the next tick.
	 * 
	 * @param string $message Message to be broadcasted
	 * @param string $reason  Reason showed in console
	 * @param bool   $notify
	 */
	public function flagForClose($message = "", $reason = "generic reason", $notify = true){
		$this->flagForClose[0] = $message;
		$this->flagForClose[1] = $reason;
		$this->flagForClose[2] = $notify;
	}

	/**
	 * Note for plugin developers: use kick() with the isAdmin
	 * flag set to kick without the "Kicked by admin" part instead of this method.
	 *
	 * @param string $message Message to be broadcasted
	 * @param string $reason  Reason showed in console
	 * @param bool   $notify
	 */
	final public function close($message = "", $reason = "generic reason", $notify = true): void
    {
		if($this->connected and !$this->closed){

			try{
				if($notify and strlen((string) $reason) > 0){
					$this->sendDisconnect($reason);
					$notify = false;
				}

                $this->removeEffect(Effect::HEALTH_BOOST);

				$this->sessionAdapter = null;
				$this->chunkCache = null;
				$this->connected = false;

				$this->server->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_USERS, $this);
				$this->server->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);

				$this->stopSleep();

                $message = $this->getLeaveMessage();
                if($this->joined) {
                    $ev = new PlayerQuitEvent($this, $message, $reason);
                    $ev->call();
                    if ($ev->getQuitMessage() != "") {
                        $this->server->broadcastMessage($ev->getQuitMessage());
                    }

                    if ($this->getFloatingInventory() instanceof FloatingInventory) {
                        foreach ($this->getFloatingInventory()->getContents() as $craftingItem) {
                            $this->inventory->addItem($craftingItem);
                        }
                        $this->getFloatingInventory()->clearAll();
                    }

                    try{
                        $this->save();
                    }catch(\Throwable $e){
                        $this->server->getLogger()->critical("Failed to save player data for " . $this->getName());
                        $this->server->getLogger()->logException($e);
                    }
                }
				$this->joined = false;

                if($this->isValid()){
                    foreach($this->usedChunks as $index => $d){
                        Level::getXZ($index, $chunkX, $chunkZ);
                        $this->level->unregisterChunkLoader($this, $chunkX, $chunkZ);
                        $this->level->unregisterChunkListener($this, $chunkX, $chunkZ);
                        foreach($this->level->getChunkEntities($chunkX, $chunkZ) as $entity){
                            $entity->despawnFrom($this);
                        }
                        unset($this->usedChunks[$index]);
                    }
                }
				$this->usedChunks = [];
				$this->loadQueue = [];

                if($this->loggedIn){
                    foreach($this->server->getOnlinePlayers() as $player){
                        if(!$player->canSee($this)){
                            $player->showPlayer($this);
                        }
                    }
                    $this->hiddenPlayers = [];
                }

                $this->removeAllWindows(true);
				$this->windows = [];
				$this->windowIndex = [];

                if($this->constructed){
                    parent::close();
                }else{
                    $this->closed = true;
                }
				$this->spawned = false;

				if($this->loggedIn){
					$this->loggedIn = false;
					$this->server->removeOnlinePlayer($this);
				}

				$this->server->getLogger()->info($this->getServer()->getLanguage()->translateString("pocketmine.player.logOut", [
					TextFormat::AQUA . $this->getName() . TextFormat::WHITE,
					$this->ip,
					$this->port,
					$this->getServer()->getLanguage()->translateString($reason),
					$this->clientVersion,
					$this->getProtocolVersion()
				]));

				$this->spawnPosition = null;

				if($this->perm !== null){
					$this->perm->clearPermissions();
					$this->perm = null;
				}

				$this->inventory = null;
				$this->transactionQueue = null;

			}catch(\Throwable $e){
				$this->server->getLogger()->logException($e);
			}finally{
				$this->interface->close($this, $notify ? $reason : "");
				$this->server->removePlayer($this);
			}
		}
	}

	public function sendDisconnect(string $reason) : void{
		$pk = new DisconnectPacket();
		$pk->message = $reason;
		$this->sendDataPacket($pk, false, true);
	}

	public function __debugInfo(){
		return [];
	}

	/**
	 * Handles player data saving
	 *
	 * @param bool $async
	 */
	public function save($async = false){
		if($this->closed){
			throw new \InvalidStateException("Tried to save closed player");
		}

		parent::saveNBT();

		if($this->isValid()){
			$this->namedtag->setString("Level", $this->level->getFolderName());
		}

		if($this->hasValidSpawnPosition()){
			$this->namedtag->setString("SpawnLevel", $this->spawnPosition->getLevel()->getFolderName());
			$this->namedtag->setInt("SpawnX", (int) $this->spawnPosition->x);
			$this->namedtag->setInt("SpawnY", (int) $this->spawnPosition->y);
			$this->namedtag->setInt("SpawnZ", (int) $this->spawnPosition->z);

            if(!$this->isAlive()){
                //hack for respawn after quit
                $this->namedtag->setTag("Pos", new ListTag([
                    new DoubleTag($this->spawnPosition->x),
                    new DoubleTag($this->spawnPosition->y),
                    new DoubleTag($this->spawnPosition->z),
                ]));
            }
		}

		$this->namedtag->setInt("playerGameType", $this->gamemode);
		$this->namedtag->setLong("lastPlayed", (int) floor(microtime(true) * 1000));

		if($this->username != "" and $this->namedtag instanceof CompoundTag){
			$this->server->saveOfflinePlayerData($this->username, $this->namedtag, $async);
		}
	}

	public function getName() : string{
		return $this->username;
	}

	/**
	 * Returns if player logged in using Xbox Live
	 *
	 * @return bool
	 */
	public function isXboxAuthenticated() : bool{
		return $this->xboxAuthenticated;
	}

	/**
	 * Gets client's language
	 *
	 * @return string
	 */
	public function getLanguageCode() : string{
		return $this->languageCode;
	}

	/**
	 * Gets client's device model
	 *
	 * @return string
	 */
	public function getDeviceModel() : string{
		return $this->deviceModel;
	}

	/**
	 * Gets client's device OS
	 *
	 * @return int
	 */
	public function getDeviceOS() : int{
		return $this->deviceOS;
	}

	/**
	 * Gets client's current input mode
	 *
	 * @return int
	 */
	public function getCurrentInputMode() : int{
		return $this->currentInputMode;
	}

	/**
	 * Gets client's default input mode
	 *
	 * @return int
	 */
	public function getDefaultInputMode() : int{
		return $this->defaultInputMode;
	}

	/**
	 * Gets client's UI profile.
	 *
	 * @return int
	 */
	public function getUIProfile() : int{
		return $this->uiProfile;
	}

	/**
	 * @internal
	 *
	 * Sets client's ping
	 *
	 * @param int $ping
	 */
	public function setPing(int $ping){
		$this->ping = $ping;
	}

	/**
	 * Gets client's ping
	 *
	 * @return int
	 */
	public function getPing() : int{
		return $this->ping;
	}

	/**
	 * @return string
	 */
	public function getLowerCaseName() : string{
		return $this->iusername;
	}

    public function kill() : void{
        if(!$this->spawned){
            return;
        }

        parent::kill();

        $this->sendRespawnPacket($this->getSpawn());
    }

	protected function callDeathEvent(){
		$message = "death.attack.generic";

		$params = [
			$this->getDisplayName()
		];

		$cause = $this->getLastDamageCause();

		switch($cause === null ? EntityDamageEvent::CAUSE_CUSTOM : $cause->getCause()){
			case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
				if($cause instanceof EntityDamageByEntityEvent){
					$e = $cause->getDamager();
					if($e instanceof Player){
						$message = "death.attack.player";
						$params[] = $e->getDisplayName();
						break;
					}elseif($e instanceof Living){
						$message = "death.attack.mob";
						$params[] = $e->getNameTag() !== "" ? $e->getNameTag() : $e->getName();
						break;
					}else{
						$params[] = "Unknown";
					}
				}
				break;
			case EntityDamageEvent::CAUSE_PROJECTILE:
				if($cause instanceof EntityDamageByEntityEvent){
					$e = $cause->getDamager();
					if($e instanceof Player){
						$message = "death.attack.arrow";
						$params[] = $e->getDisplayName();
					}elseif($e instanceof Living){
						$message = "death.attack.arrow";
						$params[] = $e->getNameTag() !== "" ? $e->getNameTag() : $e->getName();
						break;
					}else{
						$params[] = "Unknown";
					}
				}
				break;
			case EntityDamageEvent::CAUSE_SUICIDE:
				$message = "death.attack.generic";
				break;
			case EntityDamageEvent::CAUSE_VOID:
				$message = "death.attack.outOfWorld";
				break;
			case EntityDamageEvent::CAUSE_FALL:
				if($cause instanceof EntityDamageEvent){
					if($cause->getFinalDamage() > 2){
						$message = "death.fell.accident.generic";
						break;
					}
				}
				$message = "death.attack.fall";
				break;

			case EntityDamageEvent::CAUSE_SUFFOCATION:
				$message = "death.attack.inWall";
				break;

			case EntityDamageEvent::CAUSE_LAVA:
				$message = "death.attack.lava";
				break;

			case EntityDamageEvent::CAUSE_FIRE:
				$message = "death.attack.onFire";
				break;

			case EntityDamageEvent::CAUSE_FIRE_TICK:
				$message = "death.attack.inFire";
				break;

			case EntityDamageEvent::CAUSE_DROWNING:
				$message = "death.attack.drown";
				break;

			case EntityDamageEvent::CAUSE_CONTACT:
				if($cause instanceof EntityDamageByBlockEvent){
					if($cause->getDamager()->getId() === Block::CACTUS){
						$message = "death.attack.cactus";
					}
				}
				break;

			case EntityDamageEvent::CAUSE_BLOCK_EXPLOSION:
			case EntityDamageEvent::CAUSE_ENTITY_EXPLOSION:
				if($cause instanceof EntityDamageByEntityEvent){
					$e = $cause->getDamager();
					if($e instanceof Player){
						$message = "death.attack.explosion.player";
						$params[] = $e->getDisplayName();
					}elseif($e instanceof Living){
						$message = "death.attack.explosion.player";
						$params[] = $e->getNameTag() !== "" ? $e->getNameTag() : $e->getName();
						break;
					}
				}else{
					$message = "death.attack.explosion";
				}
				break;

			case EntityDamageEvent::CAUSE_MAGIC:
				$message = "death.attack.magic";
				break;

			case EntityDamageEvent::CAUSE_CUSTOM:
				break;

			default:
				break;
		}

		$ev = new PlayerDeathEvent($this, $this->getDrops(), new TranslationContainer($message, $params));
		$ev->call();

		if(!$ev->getKeepInventory()){
			foreach($ev->getDrops() as $item){
				$this->level->dropItem($this, $item);
			}

            if($this->floatingInventory !== null){
                $this->floatingInventory->clearAll();
            }

			if($this->inventory !== null){
				$this->inventory->clearAll();
			}

            if($this->offHandInventory !== null){
                $this->offHandInventory->clearAll();
            }
		}

        //TODO: allow this number to be manipulated during PlayerDeathEvent
        $this->level->dropExperience($this, $this->getXpDropAmount());
        $this->setXpAndProgress(0, 0);

		if($ev->getDeathMessage() != ""){
			$this->server->broadcast($ev->getDeathMessage(), Server::BROADCAST_CHANNEL_USERS);
		}
	}

    protected function onDeathUpdate(int $tickDiff) : bool{
        parent::onDeathUpdate($tickDiff);
        return false; //never flag players for despawn
    }

    protected function applyPostDamageEffects(EntityDamageEvent $source) : void{
        parent::applyPostDamageEffects($source);

        $this->exhaust(0.3, PlayerExhaustEvent::CAUSE_DAMAGE);
    }

	public function attack(EntityDamageEvent $source): void
    {
		if(!$this->isAlive()){
			return;
		}

        if ($this->isSpectator()) {
            return;
        }

		if($this->isCreative()
			and $source->getCause() !== EntityDamageEvent::CAUSE_SUICIDE
			and $source->getCause() !== EntityDamageEvent::CAUSE_VOID
		){
			$source->setCancelled();
		}elseif($this->allowFlight and $source->getCause() === EntityDamageEvent::CAUSE_FALL){
			$source->setCancelled();
		}

        parent::attack($source);
	}

    public function broadcastEntityEvent(int $eventId, ?int $eventData = null, ?array $players = null) : void{
        if($this->spawned and $players === null){
            $players = $this->getViewers();
            $players[] = $this;
        }
        parent::broadcastEntityEvent($eventId, $eventData, $players);
    }

	public function sendPosition(Vector3 $pos, float $yaw = null, float $pitch = null, int $mode = MovePlayerPacket::MODE_NORMAL, array $targets = null, float $baseOffsetOverride = null){
		$yaw = $yaw ?? $this->yaw;
		$pitch = $pitch ?? $this->pitch;

		$pk = new MovePlayerPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->x = $pos->x;
        $pk->y = $pos->y + ($baseOffsetOverride ?? $this->baseOffset);
		$pk->z = $pos->z;
		$pk->bodyYaw = $yaw;
		$pk->pitch = $pitch;
		$pk->yaw = $yaw;
		$pk->mode = $mode;

		if($targets !== null){
			if(in_array($this, $targets, true)){
				$this->forceMoveSync = $pos->asVector3();
				$this->ySize = 0;
			}
			$this->server->broadcastPacket($targets, $pk);
		}else{
			$this->forceMoveSync = $pos->asVector3();
			$this->ySize = 0;
			$this->sendDataPacket($pk);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function teleport(Vector3 $pos, float $yaw = null, float $pitch = null) : bool{
		if(parent::teleport($pos, $yaw, $pitch)){

            $this->removeAllWindows();

            $this->stopSleep();

			$this->sendPosition($this, $this->yaw, $this->pitch, MovePlayerPacket::MODE_TELEPORT, null, 0.0);
			$this->sendPosition($this, $this->yaw, $this->pitch, MovePlayerPacket::MODE_TELEPORT, $this->getViewers(), 0.0);

			$this->spawnToAll();

			$this->resetFallDistance();
			$this->nextChunkOrderRun = 0;

            if($this->spawnChunkLoadCount !== -1){
                $this->spawnChunkLoadCount = 0;
            }

            $this->forceMoveSync = $pos->asVector3();

            //TODO: workaround for player last pos not getting updated
            //Entity::updateMovement() normally handles this, but it's overridden with an empty function in Player
            $this->resetLastMovements();
			return true;
		}

		return false;
	}

	/**
	 * @deprecated This functionality is now performed in {@link Player#teleport}.
	 *
	 * @param Vector3    $pos
	 * @param float|null $yaw
	 * @param float|null $pitch
	 *
	 * @return bool
	 */
	public function teleportImmediate(Vector3 $pos, float $yaw = null, float $pitch = null) : bool{
		return $this->teleport($pos, $yaw, $pitch);
	}

    protected function respawn() : void{
        if($this->respawnLocked){
            return;
        }
        $this->respawnLocked = true;
        if($this->server->isHardcore()){
            $this->setBanned(true);
            return;
        }

        $this->actuallyRespawn();
    }

    protected function actuallyRespawn() : void{
        $ev = new PlayerRespawnEvent($this, $this->getSpawn());
        $ev->call();

        $realSpawn = Position::fromObject($ev->getRespawnPosition()->add(0.5, 0, 0.5), $ev->getRespawnPosition()->getLevel());
        $this->teleport($realSpawn);

        $this->setSprinting(false);
        $this->setSneaking(false);
        $this->setFlying(false);

        $this->extinguish();
        $this->setAirSupplyTicks($this->getMaxAirSupplyTicks());
        $this->deadTicks = 0;
        $this->noDamageTicks = 60;

        $this->removeAllEffects();
        $this->setHealth($this->getMaxHealth());
        $this->setFood(20);

        foreach($this->attributeMap->getAll() as $attr){
            $attr->resetToDefault();
        }

        $this->sendData($this);
        $this->sendData($this->getViewers());

        $this->sendSettings();
        $this->sendAllInventories();

        $this->spawnToAll();
        $this->scheduleUpdate();

        $this->respawnLocked = false;
    }

    protected function addDefaultWindows() : void{
        $this->addWindow($this->getInventory(), ContainerIds::INVENTORY, true);
        $this->addWindow($this->getOffHandInventory(), ContainerIds::OFFHAND, true);
        $this->addWindow($this->getArmorInventory(), ContainerIds::ARMOR, true);

        //TODO: more windows
    }

    /**
     * Returns the window ID which the inventory has for this player, or -1 if the window is not open to the player.
     *
     * @param Inventory $inventory
     *
     * @return int
     */
    public function getWindowId(Inventory $inventory) : int{
        return $this->windows[spl_object_hash($inventory)] ?? ContainerIds::NONE;
    }

    /**
     * Returns the inventory window open to the player with the specified window ID, or null if no window is open with
     * that ID.
     *
     * @param int $windowId
     *
     * @return Inventory|null
     */
    public function getWindow(int $windowId) : ?Inventory{
        return $this->windowIndex[$windowId] ?? null;
    }

    /**
     * Opens an inventory window to the player. Returns the ID of the created window, or the existing window ID if the
     * player is already viewing the specified inventory.
     *
     * @param Inventory $inventory
     * @param int|null  $forceId Forces a special ID for the window
     * @param bool      $isPermanent Prevents the window being removed if true.
     *
     * @return int
     *
     * @throws InvalidArgumentException if a forceID which is already in use is specified
     * @throws InvalidStateException if trying to add a window without forceID when no slots are free
     */
    public function addWindow(Inventory $inventory, int $forceId = null, bool $isPermanent = false) : int{
        $this->removeAllWindows(false);
        if(($id = $this->getWindowId($inventory)) !== ContainerIds::NONE){
            return $id;
        }

        if($forceId === null){
            $cnt = $this->windowCnt;
            do{
                $cnt = max(ContainerIds::FIRST, ($cnt + 1) % self::RESERVED_WINDOW_ID_RANGE_START);
                if($cnt === $this->windowCnt){ //wraparound, no free slots
                    throw new InvalidStateException("No free window IDs found");
                }
            }while(isset($this->windowIndex[$cnt]));
            $this->windowCnt = $cnt;
        }else{
            $cnt = $forceId;
            if(isset($this->windowIndex[$cnt]) or ($cnt >= self::RESERVED_WINDOW_ID_RANGE_START && $cnt <= self::RESERVED_WINDOW_ID_RANGE_END)){
                throw new InvalidArgumentException("Requested force ID $forceId already in use");
            }
        }

        $this->windowIndex[$cnt] = $inventory;
        $this->windows[spl_object_hash($inventory)] = $cnt;
        if($inventory->open($this)){
            if($isPermanent){
                $this->permanentWindows[$cnt] = true;
            }
            return $cnt;
        }else{
            $this->removeWindow($inventory);

            return -1;
        }
    }

    /**
     * Removes an inventory window from the player.
     *
     * @param Inventory $inventory
     * @param bool      $force Forces removal of permanent windows such as normal inventory, cursor
     *
     * @throws InvalidArgumentException if trying to remove a fixed inventory window without the `force` parameter as true
     */
    public function removeWindow(Inventory $inventory, bool $force = false): void{
        $id = $this->windows[$hash = spl_object_hash($inventory)] ?? null;

        if ($id !== null and !$force and isset($this->permanentWindows[$id])) {
            throw new InvalidArgumentException("Cannot remove fixed window $id (" . get_class($inventory) . ") from " . $this->getName());
        }

        if ($id !== null) {
            (new InventoryCloseEvent($inventory, $this))->call();
            $inventory->close($this);
            unset($this->windows[$hash], $this->windowIndex[$id], $this->permanentWindows[$id]);
        }
    }

    /**
     * Removes all inventory windows from the player. By default this WILL NOT remove permanent windows.
     *
     * @param bool $removePermanentWindows Whether to remove permanent windows.
     */
    public function removeAllWindows(bool $removePermanentWindows = false): void{
        foreach($this->windowIndex as $id => $window){
            if(!$removePermanentWindows and isset($this->permanentWindows[$id])){
                continue;
            }

            $this->removeWindow($window, $removePermanentWindows);
        }
    }

	public function clearCursor() : void{
		//There's nothing we can do with cursor on 1.1.
		/** @see BedrockPlayer::clearCursor() */
	}

    protected function sendAllInventories(): void{
        foreach ($this->windowIndex as $id => $inventory) {
            $inventory->sendContents($this);
        }
    }

	public function resetCrafting() : void{
		$this->craftingType = self::CRAFTING_SMALL;
	}

	public function getProtocolVersion() : int{
		return ProtocolInfo::CURRENT_PROTOCOL;
	}

	public function setMetadata(string $metadataKey, MetadataValue $newMetadataValue){
		$this->server->getPlayerMetadata()->setMetadata($this, $metadataKey, $newMetadataValue);
	}

	public function getMetadata(string $metadataKey){
		return $this->server->getPlayerMetadata()->getMetadata($this, $metadataKey);
	}

	public function hasMetadata(string $metadataKey) : bool{
		return $this->server->getPlayerMetadata()->hasMetadata($this, $metadataKey);
	}

	public function removeMetadata(string $metadataKey, Plugin $owningPlugin){
		$this->server->getPlayerMetadata()->removeMetadata($this, $metadataKey, $owningPlugin);
	}

	public function onChunkChanged(Chunk $chunk){
        $hasSent = $this->usedChunks[$hash = Level::chunkHash($chunk->getX(), $chunk->getZ())] ?? false;
        if($hasSent){
            $this->usedChunks[$hash] = false;
            $this->nextChunkOrderRun = 0;
        }
	}

	public function onChunkLoaded(Chunk $chunk){

	}

	public function onChunkPopulated(Chunk $chunk){

	}

	public function onChunkUnloaded(Chunk $chunk){

	}

	public function onBlockChanged(Vector3 $block){

	}

	public function onTileChanged(Tile $tile){

	}

	public function getLoaderId() : int{
		return $this->loaderId;
	}

	public function isLoaderActive() : bool{
		return $this->isConnected();
	}

	public function __set(string $name, $value) : void{
		$this->customData[$name] = $value;
	}

	public function &__get(string $name){
		return $this->customData[$name];
	}

	public function __isset(string $name) : bool{
		return isset($this->customData[$name]);
	}

	public function __unset(string $name) : void{
		unset($this->customData[$name]);
	}
}


