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

/**
 * PocketMine-MP is the Minecraft: PE multiplayer server software
 * Homepage: http://www.pocketmine.net/
 */
namespace pocketmine;

use pmmp\thread\Thread as NativeThread;
use pmmp\thread\ThreadSafeArray;
use pocketmine\block\BlockFactory;
use pocketmine\command\CommandReader;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\command\SimpleCommandMap;
use pocketmine\entity\Attribute;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\HandlerList;
use pocketmine\event\level\LevelCreationEvent;
use pocketmine\event\level\LevelInitEvent;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\player\PlayerDataSaveEvent;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\event\Timings;
use pocketmine\event\TimingsHandler;
use pocketmine\event\TranslationContainer;
use pocketmine\inventory\CraftingManager;
use pocketmine\inventory\InventoryType;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\lang\BaseLang;
use pocketmine\level\format\io\leveldb\LevelDB;
use pocketmine\level\format\io\LevelProvider;
use pocketmine\level\format\io\LevelProviderManager;
use pocketmine\level\format\io\region\Anvil;
use pocketmine\level\format\io\region\McRegion;
use pocketmine\level\format\io\region\PMAnvil;
use pocketmine\level\generator\biome\Biome;
use pocketmine\level\generator\Flat;
use pocketmine\level\generator\Generator;
use pocketmine\level\generator\hell\Nether;
use pocketmine\level\generator\normal\Normal;
use pocketmine\level\generator\VoidGenerator;
use pocketmine\level\Level;
use pocketmine\level\LevelException;
use pocketmine\metadata\EntityMetadataStore;
use pocketmine\metadata\LevelMetadataStore;
use pocketmine\metadata\PlayerMetadataStore;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\AdvancedNetworkInterface;
use pocketmine\network\bedrock\adapter\ProtocolAdapterFactory;
use pocketmine\network\bedrock\CompressBatchTask as BedrockCompressBatchedTask;
use pocketmine\network\bedrock\NetworkCompression as BedrockNetworkCompression;
use pocketmine\network\bedrock\PacketTranslator;
use pocketmine\network\bedrock\protocol\DataPacket as BedrockPacket;
use pocketmine\network\bedrock\protocol\ProtocolInfo as BedrockProtocolInfo;
use pocketmine\network\CompressBatchPromise;
use pocketmine\network\mcpe\CompressBatchTask as McpeCompressBatchedTask;
use pocketmine\network\mcpe\encryption\EncryptionContext;
use pocketmine\network\mcpe\NetworkCompression as McpeNetworkCompression;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\RakLibInterface;
use pocketmine\network\Network;
use pocketmine\network\query\QueryHandler;
use pocketmine\network\rcon\RCON;
use pocketmine\network\upnp\UPnP;
use pocketmine\permission\BanList;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\FolderPluginLoader;
use pocketmine\plugin\PharPluginLoader;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginLoadOrder;
use pocketmine\plugin\PluginManager;
use pocketmine\plugin\ScriptPluginLoader;
use pocketmine\resourcepacks\ResourcePackManager;
use pocketmine\scheduler\FileWriteTask;
use pocketmine\scheduler\ServerScheduler;
use pocketmine\snooze\SleeperHandler;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\thread\log\AttachableThreadSafeLogger;
use pocketmine\thread\ThreadCrashException;
use pocketmine\thread\ThreadSafeClassLoader;
use pocketmine\tile\Tile;
use pocketmine\utils\Binary;
use pocketmine\utils\Config;
use pocketmine\utils\Internet;
use pocketmine\utils\MainLogger;
use pocketmine\utils\Process;
use pocketmine\utils\ServerKiller;
use pocketmine\utils\Terminal;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;
use pocketmine\utils\UUID;
use pocketmine\utils\VersionString;
use pocketmine\utils\Zlib;
use raklib\utils\InternetAddress;
use RuntimeException;
use function array_key_exists;
use function array_shift;
use function array_sum;
use function asort;
use function assert;
use function base64_encode;
use function bccomp;
use function class_exists;
use function cli_set_process_title;
use function count;
use function define;
use function explode;
use function extension_loaded;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function floor;
use function function_exists;
use function getmypid;
use function getopt;
use function implode;
use function ini_get;
use function ini_set;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_string;
use function is_subclass_of;
use function json_decode;
use function max;
use function microtime;
use function min;
use function mkdir;
use function pcntl_signal;
use function pcntl_signal_dispatch;
use function preg_replace;
use function random_bytes;
use function realpath;
use function register_shutdown_function;
use function rename;
use function round;
use function spl_object_id;
use function sprintf;
use function str_repeat;
use function str_replace;
use function stripos;
use function strlen;
use function strrpos;
use function strtolower;
use function substr;
use function time;
use function touch;
use function trim;
use function usleep;
use const DIRECTORY_SEPARATOR;
use const PHP_INT_MAX;
use const PHP_INT_SIZE;
use const SIGHUP;
use const SIGINT;
use const SIGTERM;

/**
 * The class that manages everything
 */
class Server{
	public const BROADCAST_CHANNEL_ADMINISTRATIVE = "pocketmine.broadcast.admin";
	public const BROADCAST_CHANNEL_USERS = "pocketmine.broadcast.user";

	private static ?Server $instance = null;

    public static function getInstance() : ?Server{
        return self::$instance;
    }

	private static ?ThreadSafeArray $sleeper = null;

	private SleeperHandler $tickSleeper;

	private ?BanList $banByName = null;
	private ?BanList $banByIP = null;
	private ?Config $operators = null;
	private ?Config $whitelist = null;

	private bool $isRunning = true;
	private bool $hasStopped = false;

    private ?PluginManager $pluginManager = null;

	private float $profilingTickRate = 20;

	private ?ServerScheduler $scheduler = null;

	/**
	 * Counts the ticks since the server start
	 *
	 * @var int
	 */
	private int $tickCounter = 0;
	private float $nextTick = 0.0;
	private array $tickAverage = [20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20];
	private array $useAverage = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
	private float  $currentTPS = 20.0;
	private float $currentUse = 0;

	private bool $doTitleTick = true;
	private bool $dispatchSignals = false;

	private AttachableThreadSafeLogger $logger;

	private MemoryManager $memoryManager;

	private ?CommandReader $console = null;

	private ?SimpleCommandMap $commandMap = null;

	private CraftingManager $craftingManager;

	private ResourcePackManager $pw10ResourcePackManager;
	private ResourcePackManager $bedrockResourcePackManager;

	private ConsoleCommandSender $consoleSender;

	private int $maxPlayers = 1;

	private bool $autoSave = true;

    private ?RCON $rcon = null;

	private EntityMetadataStore $entityMetadata;
	private PlayerMetadataStore $playerMetadata;
	private LevelMetadataStore $levelMetadata;

	private Network $network;

	private bool $networkCompressionAsync = true;

	private bool $autoTickRate = true;
	private int $autoTickRateLimit = 20;
	private bool $alwaysTickPlayers = false;
	private int $baseTickRate = 1;

	private int $autoSaveTicker = 0;
	private int $autoSaveTicks = 6000;

	private BaseLang $baseLang;

	private bool $forceLanguage = false;

	private UUID $serverID;

	private ThreadSafeClassLoader $autoloader;
	private string $dataPath;
	private string $pluginPath;

	private ?QueryHandler $queryHandler = null;
	private ?QueryRegenerateEvent $queryRegenerateTask = null;

	private Config $properties;
    private Config $config;
    private Config $advancedConfig;

	private array $propertyCache = [];

	private int $port;

    /** @var Player[]  */
	private array $players = [];
	private array $playerList = [];
	private array $identifiers = [];

	private array $levels = [];

	private ?Level $levelDefault = null;

	public bool $allowInventoryCheats = false;

	public function getName() : string{
		return \pocketmine\NAME;
	}

	public function isRunning() : bool{
		return $this->isRunning === true;
	}

	public function getPocketMineVersion() : string{
		return \pocketmine\VERSION;
	}

	public function getVersion() : string{
		return ProtocolInfo::MINECRAFT_VERSION;
	}

	public function getBedrockVersion() : string{
		return BedrockProtocolInfo::MINECRAFT_VERSION;
	}

	public function getApiVersion() : string{
		return \pocketmine\API_VERSION;
	}

	public function getDataPath() : string{
		return $this->dataPath;
	}

	public function getPluginPath() : string{
		return $this->pluginPath;
	}

	public function getMaxPlayers() : int{
		return $this->maxPlayers;
	}

	public function getPort() : int{
		return $this->port;
	}

	public function getViewDistance() : int{
		return max(2, $this->getConfigInt("view-distance", 8));
	}

	public function getAllowedViewDistance(int $distance) : int{
		return max(2, min($distance, $this->memoryManager->getViewDistance($this->getViewDistance())));
	}

	public function getIp() : string{
		return $this->getConfigString("server-ip", "0.0.0.0");
	}

	public function getServerUniqueId(): UUID{
		return $this->serverID;
	}

	public function getAutoSave() : bool{
		return $this->autoSave;
	}

	public function setAutoSave(bool $value):  void{
		$this->autoSave = $value;
		foreach($this->getLevels() as $level){
			$level->setAutoSave($this->autoSave);
		}
	}

	public function getLevelType() : string{
		return $this->getConfigString("level-type", "DEFAULT");
	}

	public function getGenerateStructures() : bool{
		return $this->getConfigBoolean("generate-structures", true);
	}

	public function getGamemode() : int{
		return $this->getConfigInt("gamemode", 0) & 0b11;
	}

	public function getForceGamemode() : bool{
		return $this->getConfigBoolean("force-gamemode", false);
	}

	public static function getGamemodeString(int $mode) : string{
        return match ((int)$mode) {
            Player::SURVIVAL => "%gameMode.survival",
            Player::CREATIVE => "%gameMode.creative",
            Player::ADVENTURE => "%gameMode.adventure",
            Player::SPECTATOR => "%gameMode.spectator",
            default => "UNKNOWN",
        };

    }

	public static function getGamemodeName(int $mode) : string{
        return match ($mode) {
            Player::SURVIVAL => "Survival",
            Player::CREATIVE => "Creative",
            Player::ADVENTURE => "Adventure",
            Player::SPECTATOR => "Spectator",
            default => throw new \InvalidArgumentException("Invalid gamemode $mode"),
        };
	}

	public static function getGamemodeFromString(string $str) : int{
        return match (strtolower(trim($str))) {
            (string)Player::SURVIVAL, "survival", "s" => Player::SURVIVAL,
            (string)Player::CREATIVE, "creative", "c" => Player::CREATIVE,
            (string)Player::ADVENTURE, "adventure", "a" => Player::ADVENTURE,
            (string)Player::SPECTATOR, "spectator", "view", "v" => Player::SPECTATOR,
            default => -1,
        };
    }

	public function getDifficulty() : int{
		return $this->getConfigInt("difficulty", Level::DIFFICULTY_EASY);
	}

	public function hasWhitelist() : bool{
		return $this->getConfigBoolean("white-list", false);
	}

	public function getAllowFlight() : bool{
		return $this->getConfigBoolean("allow-flight", false);
	}

	public function isHardcore() : bool{
		return $this->getConfigBoolean("hardcore", false);
	}

	public function getDefaultGamemode() : int{
		return $this->getConfigInt("gamemode", 0) & 0b11;
	}

	public function getMotd() : string{
		return $this->getConfigString("motd", "Minecraft: PE Server");
	}

	public function getLoader() : ThreadSafeClassLoader{
		return $this->autoloader;
	}

	public function getLogger() : AttachableThreadSafeLogger{
		return $this->logger;
	}

	public function getEntityMetadata(): EntityMetadataStore{
		return $this->entityMetadata;
	}

	public function getPlayerMetadata():PlayerMetadataStore{
		return $this->playerMetadata;
	}

	public function getLevelMetadata():LevelMetadataStore{
		return $this->levelMetadata;
	}

	public function getPluginManager(): ?PluginManager{
        return $this->pluginManager;
    }

    public function getCraftingManager():CraftingManager{
        return $this->craftingManager;
    }

	public function getPw10ResourcePackManager() : ResourcePackManager{
		return $this->pw10ResourcePackManager;
	}

	public function getBedrockResourcePackManager() : ResourcePackManager{
		return $this->bedrockResourcePackManager;
	}

	public function getScheduler(): ServerScheduler{
		return $this->scheduler;
	}
	public function getTick() : int{
		return $this->tickCounter;
	}

	public function getTicksPerSecond() : float{
		return round($this->currentTPS, 2);
	}

	public function getTicksPerSecondAverage() : float{
		return round(array_sum($this->tickAverage) / count($this->tickAverage), 2);
	}

	public function getTickUsage() : float{
		return round($this->currentUse * 100, 2);
	}

	public function getTickUsageAverage() : float{
		return round((array_sum($this->useAverage) / count($this->useAverage)) * 100, 2);
	}

	public function getCommandMap(): SimpleCommandMap{
		return $this->commandMap;
	}

    /**
     * @return Player[]
     */
	public function getOnlinePlayers() : array{
		return $this->playerList;
	}

	public function getPlayers() : array{
		return $this->players;
	}

	public function shouldSavePlayerData() : bool{
		return (bool) $this->getProperty("player.save-player-data", true);
	}

    public function getOfflinePlayer(string $name): Player|OfflinePlayer{
        $name = strtolower($name);
        $result = $this->getPlayerExact($name);

        if($result === null){
            $result = new OfflinePlayer($this, $name);
        }

        return $result;
    }

	public function getOfflinePlayerData(string $name) : CompoundTag{
		$name = strtolower($name);
		$path = $this->getDataPath() . "players/";
		if($this->shouldSavePlayerData()){
			if(file_exists($path . "$name.dat")){
				try{
					$contents = @file_get_contents($path . "$name.dat");
					if($contents === false){
						throw new RuntimeException("Failed to read player data file \"$path\" (permission denied?)");
					}

					$decompressed = @Zlib::decompress($contents);
					if($decompressed === false){
						throw new RuntimeException("Failed to decompress raw player data for \"$name\"");
					}

					return (new BigEndianNbtSerializer())->read($decompressed)->mustGetCompoundTag();
				}catch(\Throwable $e){ //zlib decode error / corrupt data
					rename($path . "$name.dat", $path . "$name.dat.bak");
					$this->logger->notice($this->getLanguage()->translateString("pocketmine.data.playerCorrupted", [$name]));
				}
			}else{
				$this->logger->notice($this->getLanguage()->translateString("pocketmine.data.playerNotFound", [$name]));
			}
		}
		$spawn = $this->getDefaultLevel()->getSafeSpawn();
		return CompoundTag::create()
			->setLong("firstPlayed", (int) (microtime(true) * 1000))
			->setLong("lastPlayed", (int) (microtime(true) * 1000))
			->setTag("Pos", new ListTag([
				new DoubleTag($spawn->x),
				new DoubleTag($spawn->y),
				new DoubleTag($spawn->z)
			]))
			->setString("Level", $this->getDefaultLevel()->getName())
			->setTag("Inventory", new ListTag([], NBT::TAG_Compound))
			->setTag("EnderChestInventory", new ListTag([], NBT::TAG_Compound))
			->setInt("playerGameType", $this->getGamemode())
			->setTag("Motion", new ListTag([
				new DoubleTag( 0.0),
				new DoubleTag(0.0),
				new DoubleTag(0.0)
			]))
			->setTag("Rotation", new ListTag([
				new FloatTag(0.0),
				new FloatTag(0.0)
			]))
			->setFloat("FallDistance", 0.0)
			->setInt("Fire", 0)
			->setShort("Air", 300)
			->setByte("OnGround", 1)
			->setByte("Invulnerable", 0)
			->setString("NameTag", $name);
	}

	public function saveOfflinePlayerData(string $name, CompoundTag $nbtTag, bool $async = false):void{
		$ev = new PlayerDataSaveEvent($nbtTag, $name);
		$ev->setCancelled(!$this->shouldSavePlayerData());

		$ev->call();

		if(!$ev->isCancelled()){
			$nbt = new BigEndianNbtSerializer();
			try{
				$data = Zlib::compress($nbt->write(new TreeRoot($ev->getSaveData())), ZLIB_ENCODING_GZIP);

				if($async){
					$this->getScheduler()->scheduleAsyncTask(new FileWriteTask($this->getDataPath() . "players/" . strtolower($name) . ".dat", $data));
				}else{
					file_put_contents($this->getDataPath() . "players/" . strtolower($name) . ".dat", $data);
				}
			}catch(\Throwable $e){
				$this->logger->critical($this->getLanguage()->translateString("pocketmine.data.saveError", [$name, $e->getMessage()]));
				$this->logger->logException($e);
			}
		}
	}

	public function getPlayer(string $name): ?Player
    {
		$found = null;
		$name = strtolower($name);
		$delta = PHP_INT_MAX;
		foreach($this->getOnlinePlayers() as $player){
			if(stripos($player->getName(), $name) === 0){
				$curDelta = strlen($player->getName()) - strlen($name);
				if($curDelta < $delta){
					$found = $player;
					$delta = $curDelta;
				}
				if($curDelta === 0){
					break;
				}
			}
		}

		return $found;
	}

	public function getPlayerExact(string $name): ?Player{
		$name = strtolower($name);
		foreach($this->getOnlinePlayers() as $player){
			if($player->getLowerCaseName() === $name){
				return $player;
			}
		}

		return null;
	}

	public function matchPlayer(string $partialName) : array{
		$partialName = strtolower($partialName);
		$matchedPlayers = [];
		foreach($this->getOnlinePlayers() as $player){
			if($player->getLowerCaseName() === $partialName){
				$matchedPlayers = [$player];
				break;
			}elseif(stripos($player->getName(), $partialName) !== false){
				$matchedPlayers[] = $player;
			}
		}

		return $matchedPlayers;
	}

	public function getLevels() : array{
		return $this->levels;
	}

	public function getDefaultLevel():?Level{
		return $this->levelDefault;
	}

	public function setDefaultLevel(?Level $level):void{
		if($level === null or ($this->isLevelLoaded($level->getFolderName()) and $level !== $this->levelDefault)){
			$this->levelDefault = $level;
		}
	}

	public function isLevelLoaded(string $name) : bool{
		return $this->getLevelByName($name) instanceof Level;
	}

	public function getLevel(int $levelId):?Level
    {
        if (isset($this->levels[$levelId])) {
            return $this->levels[$levelId];
        }

        return null;
    }

    public function getLevelByName(string $name):?Level{
        foreach($this->getLevels() as $level){
            if($level->getFolderName() === $name){
                return $level;
            }
        }

        return null;
    }

	public function unloadLevel(Level $level, bool $forceUnload = false) : bool{
		if($level === $this->getDefaultLevel() and !$forceUnload){
			throw new \InvalidStateException("The default level cannot be unloaded while running, please switch levels.");
		}
		if($level->unload($forceUnload) === true){
			unset($this->levels[$level->getId()]);

			return true;
		}

		return false;
	}

	public function loadLevel(string $name, string $path = "") : bool{
		if(trim($name) === ""){
			throw new LevelException("Invalid empty level name");
		}

		$path = ($path === "" ? ($this->getDataPath() . "worlds") : $path) . DIRECTORY_SEPARATOR .  $name . "/";

		if($this->isLevelLoaded($name)){
			return true;
		}elseif(!$this->isLevelGenerated($name, $path)) {
			$this->logger->notice($this->getLanguage()->translateString("pocketmine.level.notFound", [$name]));

			return false;
		}

		$provider = LevelProviderManager::getProvider($path);

		if($provider === null){
			$this->logger->error($this->getLanguage()->translateString("pocketmine.level.loadError", [$name, "Unknown provider"]));

			return false;
		}

		try{

			($event = new LevelCreationEvent($name, Level::class))->call();

			$levelClass = $event->getLevelClass();

			$level = new $levelClass($this, $name, $path, $provider);

			if (!($level instanceof Level)) {
				throw new RuntimeException(sprintf("%s must extends %s", $levelClass, Level::class));
			}
		}catch(\Throwable $e){

			$this->logger->error($this->getLanguage()->translateString("pocketmine.level.loadError", [$name, $e->getMessage()]));
			$this->logger->logException($e);
			return false;
		}

		$this->levels[$level->getId()] = $level;

		(new LevelLoadEvent($level))->call();

		$level->setTickRate($this->baseTickRate);

		return true;
	}

	public function generateLevel(string $name, ?string $path = null, int $seed = null, $generator = null, array $options = []) : bool{
		if(trim($name) === "" or $this->isLevelGenerated($name)){
			return false;
		}

		$seed = $seed ?? Binary::readInt(random_bytes(4));

		if(!isset($options["preset"])){
			$options["preset"] = $this->getConfigString("generator-settings", "");
		}

		if(!($generator !== null and class_exists($generator, true) and is_subclass_of($generator, Generator::class))){
			$generator = Generator::getGenerator($this->getLevelType());
		}

		if(($provider = LevelProviderManager::getProviderByName($providerName = $this->getProperty("level-settings.default-format", "pmanvil"))) === null){
			$provider = LevelProviderManager::getProviderByName($providerName = "pmanvil");
		}

		try{
			if ($path === null) $path = $this->getDataPath() . "worlds/" . $name . "/";
			/** @var LevelProvider $provider */
			$provider::generate($path, $name, $seed, $generator, $options);

			$level = new Level($this, $name, $path, (string) $provider);
			$this->levels[$level->getId()] = $level;

			$level->setTickRate($this->baseTickRate);
		}catch(\Throwable $e){
			$this->logger->error($this->getLanguage()->translateString("pocketmine.level.generateError", [$name, $e->getMessage()]));
			$this->logger->logException($e);
			return false;
		}

		(new LevelInitEvent($level))->call();

		(new LevelLoadEvent($level))->call();

		$this->getLogger()->notice($this->getLanguage()->translateString("pocketmine.level.backgroundGeneration", [$name]));

		$centerX = $level->getSpawnPosition()->getX() >> 4;
		$centerZ = $level->getSpawnPosition()->getZ() >> 4;

		$order = [];

		for($X = -3; $X <= 3; ++$X){
			for($Z = -3; $Z <= 3; ++$Z){
				$distance = $X ** 2 + $Z ** 2;
				$chunkX = $X + $centerX;
				$chunkZ = $Z + $centerZ;
				$index = Level::chunkHash($chunkX, $chunkZ);
				$order[$index] = $distance;
			}
		}

		asort($order);

		foreach($order as $index => $distance){
			Level::getXZ($index, $chunkX, $chunkZ);
			$level->populateChunk($chunkX, $chunkZ, true);
		}

		return true;
	}

	public function isLevelGenerated(string $name, string $path = "") : bool{
		if(trim($name) === ""){
			return false;
		}

		if ($path === "") {
			$path = $this->getDataPath() . "worlds". DIRECTORY_SEPARATOR .  $name . "/";
		}

		if(!($this->getLevelByName($name) instanceof Level)) {

			if(LevelProviderManager::getProvider($path) === null){
				return false;
			}
		}

		return true;
	}

	public function findEntity(int $entityId):?Entity{
		foreach($this->levels as $level){
			assert(!$level->isClosed());
			if(($entity = $level->getEntity($entityId)) instanceof Entity){
				return $entity;
			}
		}

		return null;
	}

	public function getProperty(string $variable, mixed $defaultValue = null):mixed{
		if(!array_key_exists($variable, $this->propertyCache)){
			$v = getopt("", ["$variable::"]);
			if(isset($v[$variable])){
				$this->propertyCache[$variable] = $v[$variable];
			}else{
				$this->propertyCache[$variable] = $this->config->getNested($variable);
			}
		}

		return $this->propertyCache[$variable] ?? $defaultValue;
	}

	public function getAdvancedProperty(string $variable, mixed $defaultValue = null):mixed{
		$vars = explode(".", $variable);
		$base = array_shift($vars);
		$cfg = $this->advancedConfig;
		if($cfg->exists($base)){
			$base = $cfg->get($base);
		}else{
			return $defaultValue;
		}
		while(count($vars) > 0){
			$baseKey = array_shift($vars);
			if(is_array($base) and isset($base[$baseKey])){
				$base = $base[$baseKey];
			}else{
				return $defaultValue;
			}
		}
		return $base;
	}

	public function getConfigString(string $variable, string $defaultValue = "") : string{
		$v = getopt("", ["$variable::"]);
		if(isset($v[$variable])){
			return (string) $v[$variable];
		}

		return $this->properties->exists($variable) ? (string) $this->properties->get($variable) : $defaultValue;
	}

	public function setConfigString(string $variable, string $value):void{
		$this->properties->set($variable, $value);
	}

	public function getConfigInt(string $variable, int $defaultValue = 0) : int{
		$v = getopt("", ["$variable::"]);
		if(isset($v[$variable])){
			return (int) $v[$variable];
		}

		return $this->properties->exists($variable) ? (int) $this->properties->get($variable) : (int) $defaultValue;
	}

	public function setConfigInt(string $variable, int $value):void{
		$this->properties->set($variable, $value);
	}

	public function getConfigBoolean(string $variable, bool $defaultValue = false) : bool{
		$v = getopt("", ["$variable::"]);
        $value = $v[$variable] ?? ($this->properties->exists($variable) ? $this->properties->get($variable) : $defaultValue);

		if(is_bool($value)){
			return $value;
		}

        return match (strtolower($value)) {
            "on", "true", "1", "yes" => true,
            default => false,
        };

    }

	public function setConfigBool(string $variable, bool $value): void{
		$this->properties->set($variable, $value ? "1" : "0");
	}

    public function getPluginCommand(string $name):?PluginIdentifiableCommand{
        if(($command = $this->commandMap->getCommand($name)) instanceof PluginIdentifiableCommand){
            return $command;
        }else{
            return null;
        }
    }

	public function getNameBans():?BanList{
		return $this->banByName;
	}

	public function getIPBans():?BanList{
		return $this->banByIP;
	}

	public function addOp(string $name):void{
		$this->operators->set(strtolower($name), true);

		if(($player = $this->getPlayerExact($name)) !== null){
			$player->recalculatePermissions();
		}
		$this->operators->save(true);
	}

	public function removeOp(string $name):void{
		$this->operators->remove(strtolower($name));

		if(($player = $this->getPlayerExact($name)) !== null){
			$player->recalculatePermissions();
		}
		$this->operators->save();
	}

	public function addWhitelist(string $name):void{
		$this->whitelist->set(strtolower($name), true);
		$this->whitelist->save(true);
	}

	public function removeWhitelist(string $name):void{
		$this->whitelist->remove(strtolower($name));
		$this->whitelist->save();
	}

	public function isWhitelisted(string $name) : bool{
		return !$this->hasWhitelist() or $this->operators->exists($name, true) or $this->whitelist->exists($name, true);
	}

	public function isOp(string $name) : bool{
		return $this->operators->exists($name, true);
	}

	public function getWhitelisted():?Config{
		return $this->whitelist;
	}

	public function getOps():?Config{
		return $this->operators;
	}

	public function reloadWhitelist():void{
		$this->whitelist->reload();
	}

	public function getCommandAliases() : array{
		$section = $this->getProperty("aliases");
		$result = [];
		if(is_array($section)){
			foreach($section as $key => $value){
				$commands = [];
				if(is_array($value)){
					$commands = $value;
				}else{
					$commands[] = $value;
				}

				$result[$key] = $commands;
			}
		}

		return $result;
	}

	public static function microSleep(int $microseconds) : void{
		if(self::$sleeper === null){
			self::$sleeper = new ThreadSafeArray();
		}
		self::$sleeper->synchronized(function(int $ms) : void{
			Server::$sleeper->wait($ms);
		}, $microseconds);
	}

	public function __construct(ThreadSafeClassLoader $autoloader, AttachableThreadSafeLogger $logger, string $dataPath, string $pluginPath) {
		self::$instance = $this;
		self::$sleeper = new ThreadSafeArray;
		$this->tickSleeper = new SleeperHandler();
		$this->autoloader = $autoloader;
		$this->logger = $logger;

		try{

			if(!file_exists($dataPath . "worlds/")){
				mkdir($dataPath . "worlds/", 0777);
			}

			if(!file_exists($dataPath . "players/")){
				mkdir($dataPath . "players/", 0777);
			}

			if(!file_exists($pluginPath)){
				mkdir($pluginPath, 0777);
			}

			$this->dataPath = realpath($dataPath) . DIRECTORY_SEPARATOR;
			$this->pluginPath = realpath($pluginPath) . DIRECTORY_SEPARATOR;

			$consoleNotifier = new SleeperNotifier();
			$this->console = new CommandReader($consoleNotifier);
			$this->tickSleeper->addNotifier($consoleNotifier, function() : void{
				$this->checkConsole();
			});
			$this->console->start(NativeThread::INHERIT_CONSTANTS);

			$version = new VersionString($this->getPocketMineVersion());

			$this->logger->info("Loading pocketmine.yml...");
			if(!file_exists($this->dataPath . "pocketmine.yml")){
				$content = file_get_contents(\pocketmine\RESOURCE_PATH . "pocketmine.yml");
				if($version->isDev()){
					$content = str_replace("preferred-channel: stable", "preferred-channel: beta", $content);
				}
				@file_put_contents($this->dataPath . "pocketmine.yml", $content);
			}
			$this->config = new Config($this->dataPath . "pocketmine.yml", Config::YAML, []);

			$this->logger->info("Loading genisysmax.yml...");
			if(!file_exists($this->dataPath . "submarine.yml")){
				$content = file_get_contents(\pocketmine\RESOURCE_PATH . "submarine.yml");
				@file_put_contents($this->dataPath . "submarine.yml", $content);
			}
			$this->advancedConfig = new Config($this->dataPath . "submarine.yml", Config::YAML, []);
			
			$this->logger->setLogToFile(!$this->getAdvancedProperty("server.disable-logging", false));

			$this->logger->info("Loading server properties...");
			$this->properties = new Config($this->dataPath . "server.properties", Config::PROPERTIES, [
				"motd" => "Minecraft: PE Server",
				"server-port" => 19132,
				"white-list" => false,
				"max-players" => 20,
				"allow-flight" => false,
				"spawn-animals" => true,
				"spawn-mobs" => true,
				"gamemode" => 0,
				"force-gamemode" => false,
				"hardcore" => false,
				"pvp" => true,
				"difficulty" => Level::DIFFICULTY_EASY,
				"generator-settings" => "",
				"level-name" => "world",
				"level-seed" => "",
				"level-type" => "DEFAULT",
				"enable-query" => true,
                "enable-rcon" => false,
                "rcon.password" => substr(base64_encode(random_bytes(20)), 3, 10),
				"auto-save" => true,
				"online-mode" => false,
				"view-distance" => 8
			]);

			if($this->getAdvancedProperty("port-range.enabled", false)){
 				$start = (int) $this->getAdvancedProperty("port-range.start");
 				$end = (int) $this->getAdvancedProperty("port-range.end");

 				$port = $start;
 				while(!Utils::isPortAvailable($port) and $port <= $end){
 					++$port;
 				}

				if($port > $end){
					$this->logger->warning("There were no free ports in the range! Using the default port.");
					$this->port = $this->getConfigInt("server-port", 19132);
				}else{
					$this->port = $port;
				}
				
 			}else{
 				$this->port = $this->getConfigInt("server-port", 19132);
 			}

			$this->forceLanguage = $this->getProperty("settings.force-language", false);
			$this->baseLang = new BaseLang($this->getProperty("settings.language", BaseLang::FALLBACK_LANGUAGE));
			$this->logger->info($this->getLanguage()->translateString("language.selected", [$this->getLanguage()->getName(), $this->getLanguage()->getLang()]));

			$this->memoryManager = new MemoryManager($this);

			$this->logger->info($this->getLanguage()->translateString("pocketmine.server.start", [TextFormat::AQUA . $this->getVersion() . TextFormat::RESET . " + " . TextFormat::AQUA . $this->getBedrockVersion() . TextFormat::RESET]));

			if(($poolSize = $this->getProperty("settings.async-workers", "auto")) === "auto"){
				$poolSize = ServerScheduler::$WORKERS;
				$processors = Utils::getCoreCount() - 2;

				if($processors > 0){
					$poolSize = max(1, $processors);
				}
			}

			ServerScheduler::$WORKERS = $poolSize;

			if($this->getProperty("network.batch-threshold", 256) >= 0){
				McpeNetworkCompression::$THRESHOLD = (int) $this->getProperty("network.batch-threshold", 256);
			}else{
				McpeNetworkCompression::$THRESHOLD = -1;
			}
			McpeNetworkCompression::$LEVEL = $this->getProperty("network.compression-level", 7);
			$this->networkCompressionAsync = $this->getProperty("network.async-compression", true);

			BedrockNetworkCompression::$THRESHOLD = McpeNetworkCompression::$THRESHOLD;
			BedrockNetworkCompression::$LEVEL = McpeNetworkCompression::$LEVEL;

			$this->autoTickRate = (bool) $this->getProperty("level-settings.auto-tick-rate", true);
			$this->autoTickRateLimit = (int) $this->getProperty("level-settings.auto-tick-rate-limit", 20);
			$this->alwaysTickPlayers = $this->getProperty("level-settings.always-tick-players", false);
			$this->baseTickRate = (int) $this->getProperty("level-settings.base-tick-rate", 1);

			$this->doTitleTick = (bool) $this->getProperty("console.title-tick", true);

			$this->scheduler = new ServerScheduler();

            if($this->getConfigBoolean("enable-rcon", false) === true){
                try{
                    $this->rcon = new RCON(
                        $this,
                        $this->getConfigString("rcon.password", ""),
                        new InternetAddress(($ip = $this->getIp()) != "" ? $ip : "0.0.0.0", $this->getConfigInt("rcon.port", $this->getPort()), 4),
                        $this->getConfigInt("rcon.max-clients", 50)
                    );
                }catch(\Throwable $e){
                    $this->getLogger()->critical("RCON can't be started: " . $e->getMessage());
                }
            }

			$this->entityMetadata = new EntityMetadataStore();
			$this->playerMetadata = new PlayerMetadataStore();
			$this->levelMetadata = new LevelMetadataStore();

			$this->operators = new Config($this->dataPath . "ops.txt", Config::ENUM);
			$this->whitelist = new Config($this->dataPath . "white-list.txt", Config::ENUM);
			if(file_exists($this->dataPath . "banned.txt") and !file_exists($this->dataPath . "banned-players.txt")){
				@rename($this->dataPath . "banned.txt", $this->dataPath . "banned-players.txt");
			}
			@touch($this->dataPath . "banned-players.txt");
			$this->banByName = new BanList($this->dataPath . "banned-players.txt");
			$this->banByName->load();
			@touch($this->dataPath . "banned-ips.txt");
			$this->banByIP = new BanList($this->dataPath . "banned-ips.txt");
			$this->banByIP->load();

			$this->maxPlayers = $this->getConfigInt("max-players", 20);
			$this->setAutoSave($this->getConfigBoolean("auto-save", true));

            if($this->getConfigBoolean("hardcore", false) === true and $this->getDifficulty() < Level::DIFFICULTY_HARD){
                $this->setConfigInt("difficulty", Level::DIFFICULTY_HARD);
            }

			define('pocketmine\DEBUG', (int) $this->getProperty("debug.level", 1));

			if(((int) ini_get('zend.assertions')) > 0 and ((bool) $this->getProperty("debug.assertions.warn-if-enabled", true)) !== false){
				$this->logger->warning("Debugging assertions are enabled, this may impact on performance. To disable them, set `zend.assertions = -1` in php.ini.");
			}

			ini_set('assert.exception', '1');

			if($this->logger instanceof MainLogger){
				$this->logger->setLogDebug(\pocketmine\DEBUG > 1);
			}

			if(\pocketmine\DEBUG >= 0){
				@cli_set_process_title($this->getName() . " " . $this->getPocketMineVersion() . " " . $this->getPort());
			}

			if(!extension_loaded("pocketmine_chunkutils")){
				$this->logger->warning("pocketmine_chunkutils extension is not loaded, this will have a major impact on performance of Anvil worlds");
			}
			if(!extension_loaded("chunkutils2")){
				$this->logger->warning("chunkutils2 extension is not loaded, this will have a major impact on performance of Minecraft: Bedrock chunks");
			}

			$this->logger->info($this->getLanguage()->translateString("pocketmine.server.networkStart", [$this->getIp() === "" ? "*" : $this->getIp(), $this->getPort()]));
			define("BOOTUP_RANDOM", random_bytes(16));
			$this->serverID = Utils::getMachineUniqueId($this->getIp() . $this->getPort());

			$this->getLogger()->debug("Server unique id: " . $this->getServerUniqueId());
			$this->getLogger()->debug("Machine unique id: " . Utils::getMachineUniqueId());

			$this->network = new Network($this);
            $this->network->setName($this->getMotd());

			$this->logger->info($this->getLanguage()->translateString("pocketmine.server.info", [
				$this->getName(),
				($version->isDev() ? TextFormat::YELLOW : "") . $version->getFullVersion(true) . TextFormat::WHITE,
				implode(", ", DEVELOPERS),
				$this->getApiVersion()
			]));

			Timings::init();
			EncryptionContext::$ENABLED = (bool) $this->getProperty("network.enable-encryption", true);

			$this->consoleSender = new ConsoleCommandSender();
			$this->commandMap = new SimpleCommandMap($this);

			Entity::init();
			Tile::init();
			InventoryType::init();
            BlockFactory::init();
			Enchantment::init();
			Item::init();
            Item::initCreativeItems();
			Biome::init();
			Effect::init();
			Attribute::init();
			$this->craftingManager = new CraftingManager();

			$this->logger->info("Loading PW10 resource packs...");
			$this->pw10ResourcePackManager = new ResourcePackManager($this, $this->getDataPath() . "pw10_packs" . DIRECTORY_SEPARATOR);
			$this->logger->debug("Successfully loaded " . count($this->pw10ResourcePackManager->getResourceStack()) . " resource packs");

			$this->logger->info("Loading bedrock resource packs...");
			$this->bedrockResourcePackManager = new ResourcePackManager($this, $this->getDataPath() . "bedrock_packs" . DIRECTORY_SEPARATOR);
			$this->logger->debug("Successfully loaded " . count($this->bedrockResourcePackManager->getResourceStack()) . " resource packs");

            TimingsHandler::setEnabled($this->getProperty("settings.enable-profiling", false));

			$this->pluginManager = new PluginManager($this, $this->commandMap);
			$this->pluginManager->subscribeToPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this->consoleSender);
			$this->profilingTickRate = (float) $this->getProperty("settings.profile-report-trigger", 20);
			$this->allowInventoryCheats = $this->getAdvancedProperty("inventory.allow-cheats", false);
			$this->pluginManager->registerInterface(PharPluginLoader::class);
			$this->pluginManager->registerInterface(ScriptPluginLoader::class);
			if ($this->getAdvancedProperty("server.folder-plugin-loader", false)) {
				$this->pluginManager->registerInterface(FolderPluginLoader::class);
			}

			register_shutdown_function([$this, "crashDump"]);

			$this->queryRegenerateTask = new QueryRegenerateEvent($this);
			$this->network->registerInterface(new RakLibInterface($this));
			
			include($dataPath . "src/pocketmine/API.php");
			\API::init();

			include($dataPath . "src/pocketmine/GenisysMaxAPI.php");
			\pocketmine\GenisysMaxAPI::init();

			$this->pluginManager->loadPlugins($this->pluginPath);

			$this->enablePlugins(PluginLoadOrder::PRESTARTUP);
			$this->enablePlugins(PluginLoadOrder::STARTUP);

			LevelProviderManager::addProvider(Anvil::class);
			LevelProviderManager::addProvider(McRegion::class);
			LevelProviderManager::addProvider(PMAnvil::class);
			if(extension_loaded("leveldb")){
				$this->logger->debug($this->getLanguage()->translateString("pocketmine.debug.enable"));
				LevelProviderManager::addProvider(LevelDB::class);
			}


			Generator::addGenerator(Flat::class, "flat");
			Generator::addGenerator(VoidGenerator::class, "void");
			Generator::addGenerator(Normal::class, "normal");
			Generator::addGenerator(Normal::class, "default");
			Generator::addGenerator(Nether::class, "hell");
			Generator::addGenerator(Nether::class, "nether");

			foreach((array) $this->getProperty("worlds", []) as $name => $worldSetting){
				if($this->loadLevel($name) === false){
					$seed = $this->getProperty("worlds.$name.seed", time());
					$options = explode(":", $this->getProperty("worlds.$name.generator", Generator::getGenerator("default")));
					$generator = Generator::getGenerator(array_shift($options));
					if(count($options) > 0){
						$options = [
							"preset" => implode(":", $options)
						];
					}else{
						$options = [];
					}

					$this->generateLevel($name, null, $seed, $generator, $options);
				}
			}

			if($this->getDefaultLevel() === null){
				$default = $this->getConfigString("level-name", "world");
				if(trim($default) == ""){
					$this->getLogger()->warning("level-name cannot be null, using default");
					$default = "world";
					$this->setConfigString("level-name", "world");
				}
				if($this->loadLevel($default) === false){
					$seed = getopt("", ["level-seed::"])["level-seed"] ?? $this->properties->get("level-seed", time());
					if(!is_numeric($seed) or bccomp($seed, "9223372036854775807") > 0){
						$seed = Utils::javaStringHash($seed);
					}elseif(PHP_INT_SIZE === 8){
						$seed = (int) $seed;
					}
					$this->generateLevel($default, null, $seed === 0 ? time() : $seed);
				}

				$this->setDefaultLevel($this->getLevelByName($default));
			}

			if($this->getAdvancedProperty("server.readonly-properties", false)){
				$this->properties->save(true);
			}

			if(!($this->getDefaultLevel() instanceof Level)){
				$this->getLogger()->emergency($this->getLanguage()->translateString("pocketmine.level.defaultError"));
				$this->forceShutdown();

				return;
			}

			if($this->getProperty("ticks-per.autosave", 6000) > 0){
				$this->autoSaveTicks = (int) $this->getProperty("ticks-per.autosave", 6000);
			}

			$this->enablePlugins(PluginLoadOrder::POSTWORLD);

			$this->start();
		}catch(\Throwable $e){
			$this->exceptionHandler($e);
		}
	}

	public function getConsoleSender() : ConsoleCommandSender{
		return $this->consoleSender;
	}

	public function broadcastMessage($message, array $recipients = null) : int{
		if(!is_array($recipients)){
			return $this->broadcast($message, self::BROADCAST_CHANNEL_USERS);
		}

		/** @var Player[] $recipients */
		foreach($recipients as $recipient){
			$recipient->sendMessage($message);
		}

		return count($recipients);
	}

	public function broadcastTip(string $tip, array $recipients = null) : int{
		if(!is_array($recipients)){
			/** @var Player[] $recipients */
			$recipients = [];
			foreach($this->pluginManager->getPermissionSubscriptions(self::BROADCAST_CHANNEL_USERS) as $permissible){
				if($permissible instanceof Player and $permissible->hasPermission(self::BROADCAST_CHANNEL_USERS)){
					$recipients[spl_object_id($permissible)] = $permissible; // do not send messages directly, or some might be repeated
				}
			}
		}

		/** @var Player[] $recipients */
		foreach($recipients as $recipient){
			$recipient->sendTip($tip);
		}

		return count($recipients);
	}

	public function broadcastPopup(string $popup, array $recipients = null) : int{
		if(!is_array($recipients)){
			/** @var Player[] $recipients */
			$recipients = [];

			foreach($this->pluginManager->getPermissionSubscriptions(self::BROADCAST_CHANNEL_USERS) as $permissible){
				if($permissible instanceof Player and $permissible->hasPermission(self::BROADCAST_CHANNEL_USERS)){
					$recipients[spl_object_id($permissible)] = $permissible; // do not send messages directly, or some might be repeated
				}
			}
		}

		/** @var Player[] $recipients */
		foreach($recipients as $recipient){
			$recipient->sendPopup($popup);
		}

		return count($recipients);
	}

	public function broadcastTitle(string $title, string $subtitle = "", int $fadeIn = -1, int $stay = -1, int $fadeOut = -1, array $recipients = null) : int{
		if(!is_array($recipients)){
			/** @var Player[] $recipients */
			$recipients = [];

			foreach($this->pluginManager->getPermissionSubscriptions(self::BROADCAST_CHANNEL_USERS) as $permissible){
				if($permissible instanceof Player and $permissible->hasPermission(self::BROADCAST_CHANNEL_USERS)){
					$recipients[spl_object_id($permissible)] = $permissible; // do not send messages directly, or some might be repeated
				}
			}
		}

		/** @var Player[] $recipients */
		foreach($recipients as $recipient){
			$recipient->addTitle($title, $subtitle, $fadeIn, $stay, $fadeOut);
		}

		return count($recipients);
	}

	public function broadcast($message, string $permissions) : int{
		/** @var CommandSender[] $recipients */
		$recipients = [];
		foreach(explode(";", $permissions) as $permission){
			foreach($this->pluginManager->getPermissionSubscriptions($permission) as $permissible){
				if($permissible instanceof CommandSender and $permissible->hasPermission($permission)){
					$recipients[spl_object_id($permissible)] = $permissible; // do not send messages directly, or some might be repeated
				}
			}
		}

		foreach($recipients as $recipient){
			$recipient->sendMessage($message);
		}

		return count($recipients);
	}

	public function broadcastPacket(array $players, DataPacket $packet): void{
		$packet->encode();
		$this->batchPackets($players, [$packet], false);
	}

	public function batchPackets(array $players, array $packets, bool $forceSync = false, bool $immediate = false): void{
		Timings::$serverBatchPacketsTimer->startTiming();

		$bedrockTargets = [];
		$targets = [];
		foreach($players as $player) {
            if ($player->isConnected()) {
                if (isset($this->identifiers[spl_object_id($player)])) {
                    if ($player instanceof BedrockPlayer) {
                        $bedrockTargets[$player->getProtocolVersion()][] = $this->identifiers[spl_object_id($player)];
                    } else {
                        $targets[] = $this->identifiers[spl_object_id($player)];
                    }
                }
            }
        }

		if(count($targets) > 0){
			$batch = new BatchPacket();

			foreach($packets as $packet){
				$batch->addPacket($packet);
			}

			if(McpeNetworkCompression::$THRESHOLD >= 0 and strlen($batch->payload) >= McpeNetworkCompression::$THRESHOLD){
				$batch->setCompressionLevel(McpeNetworkCompression::$LEVEL);
			}else{
				$batch->setCompressionLevel(0); //Do not compress packets under the threshold
				$forceSync = true;
			}

			if(!$forceSync and !$immediate and $this->networkCompressionAsync){
				$promise = new CompressBatchPromise();

				$task = new McpeCompressBatchedTask($batch->payload, $batch->getCompressionLevel(), $promise);
				$this->getScheduler()->scheduleAsyncTask($task);

				$promise->onResolve(function(CompressBatchPromise $promise) use ($immediate, $targets) : void{
					$this->broadcastPacketsCallback($promise->getResult(), $targets, $immediate);
				});
			}else{
				$this->broadcastPacketsCallback(McpeNetworkCompression::compress($batch->payload, $batch->getCompressionLevel()), $targets, $immediate);
			}
		}
		foreach($bedrockTargets as $protocolVersion => $targets){
			$batch = new BatchPacket();

			$empty = true;
			foreach($packets as $packet){
				if(!$packet instanceof BedrockPacket){
					$packet = PacketTranslator::translate($packet);
				}

				if($packet !== null){
					$adapter = ProtocolAdapterFactory::get($protocolVersion);
					if($adapter !== null){
						$packet = $adapter->processServerToClient($packet);
					}
				}
				if($packet !== null){
					$empty = false;
					$batch->addPacket($packet);
				}
			}

			if(!$empty){
				if(BedrockNetworkCompression::$THRESHOLD >= 0 and strlen($batch->payload) >= BedrockNetworkCompression::$THRESHOLD){
					$batch->setCompressionLevel(BedrockNetworkCompression::$LEVEL);
				}else{
					$batch->setCompressionLevel(0); //Do not compress packets under the threshold
					$forceSync = true;
				}

				if(!$forceSync and !$immediate and $this->networkCompressionAsync){
					$promise = new CompressBatchPromise();

					$task = new BedrockCompressBatchedTask($batch->payload, $batch->getCompressionLevel(), $promise);
					$this->getScheduler()->scheduleAsyncTask($task);

					$promise->onResolve(function(CompressBatchPromise $promise) use ($immediate, $targets) : void{
						$this->broadcastPacketsCallback($promise->getResult(), $targets, $immediate);
					});
				}else{
					$this->broadcastPacketsCallback(BedrockNetworkCompression::compress($batch->payload, $batch->getCompressionLevel()), $targets, $immediate);
				}
			}
		}

		Timings::$serverBatchPacketsTimer->stopTiming();
	}

	public function broadcastPacketsCallback(string $encoded, array $identifiers, bool $immediate = false):void{
		foreach($identifiers as $i){
			if(isset($this->players[$i])){
				$this->players[$i]->sendEncoded(ProtocolInfo::MCPE_RAKNET_PACKET_ID . $encoded, false, $immediate);
			}
		}
	}

	public function enablePlugins(int $type):void{
		foreach($this->pluginManager->getPlugins() as $plugin){
			if(!$plugin->isEnabled() and $plugin->getDescription()->getOrder() === $type){
				$this->enablePlugin($plugin);
			}
		}

		if($type === PluginLoadOrder::POSTWORLD){
			$this->commandMap->registerServerAliases();
			DefaultPermissions::registerCorePermissions();
		}
	}

	public function enablePlugin(Plugin $plugin):void{
		$this->pluginManager->enablePlugin($plugin);
	}

	public function disablePlugins():void{
		$this->pluginManager->disablePlugins();
	}

	public function checkConsole():void{
		Timings::$serverCommandTimer->startTiming();
		while(($line = $this->console->getLine()) !== null){
			$ev = new ServerCommandEvent($this->consoleSender, $line);
			$ev->call();
			if(!$ev->isCancelled()){
				$this->dispatchCommand($ev->getSender(), $ev->getCommand());
			}
		}
		Timings::$serverCommandTimer->stopTiming();
	}

	public function dispatchCommand(CommandSender $sender, string $commandLine) : bool{
		if($this->commandMap->dispatch($sender, $commandLine)){
			return true;
		}


		$sender->sendCommandMessage(new TranslationContainer(TextFormat::RED . "%commands.generic.notFound"));

		return false;
	}

	public function reload():void{
		$this->logger->info("Saving levels...");

		foreach($this->levels as $level){
			$level->save();
		}

		$this->pluginManager->disablePlugins();
		$this->pluginManager->clearPlugins();
		$this->commandMap->clearCommands();

		$this->logger->info("Reloading properties...");
		$this->properties->reload();
		$this->maxPlayers = $this->getConfigInt("max-players", 20);

		if($this->getConfigBoolean("hardcore", false) === true and $this->getDifficulty() < Level::DIFFICULTY_HARD){
			$this->setConfigInt("difficulty", Level::DIFFICULTY_HARD);
		}

		$this->banByIP->load();
		$this->banByName->load();
		$this->reloadWhitelist();
		$this->operators->reload();

		foreach($this->getIPBans()->getEntries() as $entry){
			$this->getNetwork()->blockAddress($entry->getName(), -1);
		}

		$this->pluginManager->registerInterface(PharPluginLoader::class);
		$this->pluginManager->registerInterface(ScriptPluginLoader::class);
		$this->pluginManager->loadPlugins($this->pluginPath);
		$this->enablePlugins(PluginLoadOrder::PRESTARTUP);
		$this->enablePlugins(PluginLoadOrder::STARTUP);
		$this->enablePlugins(PluginLoadOrder::POSTWORLD);
		TimingsHandler::reload();
	}

	public function shutdown(): void{
		$this->isRunning = false;
	}

	public function forceShutdown():void{
		if($this->hasStopped){
			return;
		}

		try{
			$this->hasStopped = true;

			$this->shutdown();
            if($this->rcon instanceof RCON){
                $this->getLogger()->debug("Stopping rcon");
                $this->rcon->stop();
            }

			if($this->getProperty("network.upnp-forwarding", false) === true){
				$this->logger->info("[UPnP] Removing port forward...");
				UPnP::RemovePortForward($this->getPort());
			}

			if($this->pluginManager instanceof PluginManager){
				$this->getLogger()->debug("Disabling all plugins");
				$this->pluginManager->disablePlugins();
			}

			foreach($this->players as $player){
				$player->close($player->getLeaveMessage(), $this->getProperty("settings.shutdown-message", "Server closed"));
			}

			$this->getLogger()->debug("Unloading all levels");
			foreach($this->getLevels() as $level){
				$this->unloadLevel($level, true);
			}

			$this->getLogger()->debug("Removing event handlers");
			HandlerList::unregisterAll();

			if($this->scheduler instanceof ServerScheduler){
				$this->getLogger()->debug("Shutting down task scheduler");
				$this->scheduler->shutdown();
			}

			if($this->getAdvancedProperty("server.readonly-properties", false)){
				$this->getLogger()->debug("Saving properties");
				$this->properties->save();
			}

			$this->getLogger()->debug("Closing console");
			$this->console->shutdown();
			$this->console->notify();

			if($this->network instanceof Network){
				$this->getLogger()->debug("Stopping network interfaces");
				foreach($this->network->getInterfaces() as $interface){
					$interface->shutdown();
					$this->network->unregisterInterface($interface);
				}
			}

			$killer = new ServerKiller(8);
			$killer->start(NativeThread::INHERIT_NONE);

			usleep(10000); //Fixes ServerKiller not being able to start on single-core machines
		}catch(\Throwable $e){
			$this->logger->logException($e);
			$this->logger->emergency("Crashed while crashing, killing process");
			@kill(getmypid());
		}
	}

	public function getQueryInformation():?QueryRegenerateEvent{
		return $this->queryRegenerateTask;
	}

	public function start():void{
		if($this->getConfigBoolean("enable-query", true) === true){
			$this->queryHandler = new QueryHandler();
		}

		foreach($this->getIPBans()->getEntries() as $entry){
			$this->network->blockAddress($entry->getName(), -1);
		}

		if($this->getProperty("network.upnp-forwarding", false)){
			$this->logger->info("[UPnP] Trying to port forward...");
			UPnP::PortForward($this->getPort());
		}

		$this->tickCounter = 0;

		if(function_exists("pcntl_signal")){
			pcntl_signal(SIGTERM, [$this, "handleSignal"]);
			pcntl_signal(SIGINT, [$this, "handleSignal"]);
			pcntl_signal(SIGHUP, [$this, "handleSignal"]);
			$this->dispatchSignals = true;
		}

		$this->logger->info($this->getLanguage()->translateString("pocketmine.server.defaultGameMode", [self::getGamemodeString($this->getGamemode())]));

		$this->logger->info($this->getLanguage()->translateString("pocketmine.server.startFinished", [round(microtime(true) - \pocketmine\START_TIME, 3)]));

		$this->tickProcessor();
		$this->forceShutdown();
	}

	public function handleSignal($signo): void{
		if($signo === SIGTERM or $signo === SIGINT or $signo === SIGHUP){
			$this->shutdown();
		}
	}

	public function exceptionHandler(\Throwable $e, ?array $trace = null) : void{
		while(@ob_end_flush()){}
		global $lastError;

        if($trace === null){
            $trace = $e->getTrace();
        }

        //If this is a thread crash, this logs where the exception came from on the main thread, as opposed to the
        //crashed thread. This is intentional, and might be useful for debugging
        //Assume that the thread already logged the original exception with the correct stack trace
        $this->logger->logException($e, $trace);

        if($e instanceof ThreadCrashException){
            $info = $e->getCrashInfo();
            $type = $info->getType();
            $errstr = $info->getMessage();
            $errfile = $info->getFile();
            $errline = $info->getLine();
            $printableTrace = $info->getTrace();
            $thread = $info->getThreadName();
        }else{
            $type = get_class($e);
            $errstr = $e->getMessage();
            $errfile = $e->getFile();
            $errline = $e->getLine();
            $printableTrace = Utils::printableTraceWithMetadata($trace);
            $thread = "Main";
        }

		$errstr = preg_replace('/\s+/', ' ', trim($errstr));

		$lastError = [
			"type" => $type,
			"message" => $errstr,
			"fullFile" => $errfile,
			"file" => Utils::cleanPath($errfile),
			"line" => $errline,
			"trace" => $printableTrace,
			"thread" => $thread
		];

		global $lastExceptionError, $lastError;
		$lastExceptionError = $lastError;
		$this->crashDump();
	}

    public function crashDump() : void{
        while(@ob_end_flush()){}
        if(!$this->isRunning){
            return;
        }

        $this->hasStopped = false;

        ini_set("error_reporting", '0');
        ini_set("memory_limit", '-1'); //Fix error dump not dumped on memory problems
        try{
            $this->logger->emergency($this->getLanguage()->translateString("pocketmine.crash.create"));
            $dump = new CrashDump($this);

            $this->logger->emergency($this->getLanguage()->translateString("pocketmine.crash.submit", [$dump->getPath()]));

            if($this->getProperty("auto-report.enabled", true) !== false){
                $report = true;

                $stamp = $this->getDataPath() . "crashdumps/.last_crash";
                $crashInterval = 120; //2 minutes
                if(file_exists($stamp) and !($report = (filemtime($stamp) + $crashInterval < time()))){
                    $this->logger->debug("Not sending crashdump due to last crash less than $crashInterval seconds ago");
                }
                @touch($stamp); //update file timestamp

                $plugin = $dump->getData()["plugin"];
                if(is_string($plugin)){
                    $p = $this->pluginManager->getPlugin($plugin);
                    if($p instanceof Plugin and !($p->getPluginLoader() instanceof PharPluginLoader)){
                        $this->logger->debug("Not sending crashdump due to caused by non-phar plugin");
                        $report = false;
                    }
                }

                if($dump->getData()["error"]["type"] === \ParseError::class){
                    $report = false;
                }

                if(strrpos(\pocketmine\GIT_COMMIT, "-dirty") !== false or \pocketmine\GIT_COMMIT === str_repeat("00", 20)){
                    $this->logger->debug("Not sending crashdump due to locally modified");
                    $report = false; //Don't send crashdumps for locally modified builds
                }

                if($report){
                    $url = ((bool) $this->getProperty("auto-report.use-https", true) ? "https" : "http") . "://" . $this->getProperty("auto-report.host", "crash.pmmp.io") . "/submit/api";
                    $postUrlError = "Unknown error";
                    $reply = Internet::postURL($url, [
                        "report" => "yes",
                        "name" => $this->getName() . " " . $this->getPocketMineVersion(),
                        "email" => "crash@pocketmine.net",
                        "reportPaste" => base64_encode($dump->getEncodedData())
                    ], 10, [], $postUrlError);

                    if($reply !== false and ($data = json_decode($reply)) !== null){
                        if(isset($data->crashId) and isset($data->crashUrl)){
                            $reportId = $data->crashId;
                            $reportUrl = $data->crashUrl;
                            $this->logger->emergency($this->getLanguage()->translateString("pocketmine.crash.archive", [$reportUrl, $reportId]));
                        }elseif(isset($data->error)){
                            $this->logger->emergency("Automatic crash report submission failed: $data->error");
                        }
                    }else{
                        $this->logger->emergency("Failed to communicate with crash archive: $postUrlError");
                    }
                }
            }
        }catch(\Throwable $e){
            $this->logger->logException($e);
            try{
                $this->logger->critical($this->getLanguage()->translateString("pocketmine.crash.error", [$e->getMessage()]));
            }catch(\Throwable $e){}
        }

        $this->forceShutdown();
        $this->isRunning = false;

        //Force minimum uptime to be >= 120 seconds, to reduce the impact of spammy crash loops
        $spacing = ((int) \pocketmine\START_TIME) - time() + 120;
        if($spacing > 0){
            echo "--- Waiting $spacing seconds to throttle automatic restart (you can kill the process safely now) ---" . PHP_EOL;
            sleep($spacing);
        }
        @Process::kill(Process::pid());
        exit(1);
    }

    public function __debugInfo(){
		return [];
	}

	public function getTickSleeper() : SleeperHandler{
		return $this->tickSleeper;
	}

	private function tickProcessor():void{
		$this->nextTick = microtime(true);
		while($this->isRunning){
			$this->tick();

			//sleeps are self-correcting - if we undersleep 1ms on this tick, we'll sleep an extra ms on the next tick
			$this->tickSleeper->sleepUntil($this->nextTick);
		}
	}

	public function addPlayer($identifier, Player $player):void{
		$this->players[$identifier] = $player;
		$this->identifiers[spl_object_id($player)] = $identifier;
	}

    public function getPlayerId(int $spl_object_id):?Player{
        if(isset($this->identifiers[$spl_object_id])){
            $identifier = $this->identifiers[$spl_object_id];
            return $this->players[$identifier];
        }
        return null;
    }

    public function removePlayer(Player $player):void{
        if(isset($this->identifiers[$hash = spl_object_id($player)])){
            $identifier = $this->identifiers[$hash];
            unset($this->players[$identifier]);
            unset($this->identifiers[$hash]);
            return;
        }

        foreach($this->players as $identifier => $p){
            if($player === $p){
                unset($this->players[$identifier]);
                unset($this->identifiers[spl_object_id($player)]);
                break;
            }
        }
    }

	public function addOnlinePlayer(Player $player):void{
		$this->updatePlayerList($player);
		$this->playerList[$player->getRawUniqueId()] = $player;
	}

	public function checkPlayerOnline(Player $player): void{
        $nickname = $player->getLowerCaseName();
        foreach($this->getOnlinePlayers() as $other){
            if ($other !== $player and $other->loggedIn and $other->getLowerCaseName() === $nickname) {
                $player->close($player->getLeaveMessage(), "disconnectionScreen.loggedinOtherLocation");
                return;
            }
        }
    }

	public function removeOnlinePlayer(Player $player):void{
		if(isset($this->playerList[$player->getRawUniqueId()])){
			unset($this->playerList[$player->getRawUniqueId()]);
			$this->removePlayerList($player->getUniqueId());
		}
	}

	public function updatePlayerList(Player $player) : void{
		foreach($this->playerList as $onlinePlayer){
			$onlinePlayer->updatePlayerList($player);
		}
	}

	public function removePlayerList(UUID $uuid) : void{
		foreach($this->playerList as $onlinePlayer){
			$onlinePlayer->removePlayerList($uuid);
		}
	}

	private function checkTickUpdates(int $currentTick, float $tickTime):void{
		foreach($this->players as $p) {
            if ($p instanceof Player) {
                if (!$p->loggedIn and ($tickTime - $p->creationTime) >= 10) {
                    $p->close("", "Login timeout");
                } elseif ($this->alwaysTickPlayers and $p->joined) {
                    $p->onUpdate($currentTick);
                }
            }
        }

		//Do level ticks
		foreach($this->getLevels() as $level){
			if($level->getTickRate() > $this->baseTickRate and --$level->tickRateCounter > 0){
				continue;
			}
			try{
				$levelTime = microtime(true);
				$level->doTick($currentTick);
				$tickMs = (microtime(true) - $levelTime) * 1000;
				$level->tickRateTime = $tickMs;

				if($this->autoTickRate){
					if($tickMs < 50 and $level->getTickRate() > $this->baseTickRate){
						$level->setTickRate($r = $level->getTickRate() - 1);
						if($r > $this->baseTickRate){
							$level->tickRateCounter = $level->getTickRate();
						}
						$this->getLogger()->debug("Raising level \"{$level->getName()}\" tick rate to {$level->getTickRate()} ticks");
					}elseif($tickMs >= 50){
						if($level->getTickRate() === $this->baseTickRate){
							$level->setTickRate(max($this->baseTickRate + 1, min($this->autoTickRateLimit, (int) floor($tickMs / 50))));
							$this->getLogger()->debug(sprintf("Level \"%s\" took %gms, setting tick rate to %d ticks", $level->getName(), (int) round($tickMs, 2), $level->getTickRate()));
						}elseif(($tickMs / $level->getTickRate()) >= 50 and $level->getTickRate() < $this->autoTickRateLimit){
							$level->setTickRate($level->getTickRate() + 1);
							$this->getLogger()->debug(sprintf("Level \"%s\" took %gms, setting tick rate to %d ticks", $level->getName(), (int) round($tickMs, 2), $level->getTickRate()));
						}
						$level->tickRateCounter = $level->getTickRate();
					}
				}
			}catch(\Throwable $e){
				$this->logger->critical($this->getLanguage()->translateString("pocketmine.level.tickError", [$level->getName(), $e->getMessage()]));
				$this->logger->logException($e);
			}
		}
	}

	public function doAutoSave():void{
		if($this->getAutoSave()){
			Timings::$worldSaveTimer->startTiming();
			foreach($this->players as $index => $player){
				if($player->joined){
					$player->save(true);
				}elseif(!$player->isConnected()){
					$this->removePlayer($player);
				}
			}

			foreach($this->getLevels() as $level){
				$level->save(false);
			}
			Timings::$worldSaveTimer->stopTiming();
		}
	}

	public function getLanguage():BaseLang{
		return $this->baseLang;
	}

	public function isLanguageForced() : bool{
		return $this->forceLanguage;
	}

	public function getNetwork():Network{
		return $this->network;
	}

	public function getMemoryManager(): MemoryManager
    {
		return $this->memoryManager;
	}

    public function isNetworkCompressionAsync(): bool
    {
        return $this->networkCompressionAsync;
    }

	private function titleTick():void{
		Timings::$titleTickTimer->startTiming();
		$d = Utils::getRealMemoryUsage();

		$u = Utils::getMemoryUsage(true);
		$usage = sprintf("%g/%g/%g/%g MB @ %d threads", round(($u[0] / 1024) / 1024, 2), round(($d[0] / 1024) / 1024, 2), round(($u[1] / 1024) / 1024, 2), round(($u[2] / 1024) / 1024, 2), Utils::getThreadCount());

		echo "\x1b]0;" . $this->getName() . " " .
			$this->getPocketMineVersion() .
			" | Online " . count($this->players) . "/" . $this->getMaxPlayers() .
			" | Memory " . $usage .
			" | U " . round($this->network->getUpload() / 1024, 2) .
			" D " . round($this->network->getDownload() / 1024, 2) .
			" kB/s | TPS " . $this->getTicksPerSecondAverage() .
			" | Load " . $this->getTickUsageAverage() . "%\x07";

		$this->network->resetStatistics();

		Timings::$titleTickTimer->stopTiming();
	}

    public function handlePacket(AdvancedNetworkInterface $interface, string $address, int $port, string $payload){
        try{
            if(strlen($payload) > 2 and substr($payload, 0, 2) === "\xfe\xfd" and $this->queryHandler instanceof QueryHandler){
                $this->queryHandler->handle($interface, $address, $port, $payload);
            }else{
                $this->logger->debug("Unhandled raw packet from $address $port: " . base64_encode($payload));
            }
        }catch(\Throwable $e){
            if($this->logger instanceof MainLogger){
                $this->logger->logException($e);
            }

            $this->getNetwork()->blockAddress($address, 600);
        }
    }

	private function tick() : bool{
		$tickTime = microtime(true);
		if(($tickTime - $this->nextTick) < -0.025){ //Allow half a tick of diff
			return false;
		}

		Timings::$serverTickTimer->startTiming();

		++$this->tickCounter;

		Timings::$connectionTimer->startTiming();
		$this->network->processInterfaces();

		Timings::$connectionTimer->stopTiming();

		Timings::$schedulerTimer->startTiming();
		$this->scheduler->mainThreadHeartbeat($this->tickCounter);
		Timings::$schedulerTimer->stopTiming();

		$this->checkTickUpdates($this->tickCounter, $tickTime);

		foreach($this->players as $player) {
            if ($player instanceof Player) {
                $player->checkNetwork();
            }
        }

		if($this->tickCounter % 20 === 0){
			if($this->doTitleTick and Terminal::hasFormattingCodes()){
				$this->titleTick();
			}
			$this->currentTPS = 20;
			$this->currentUse = 0;

            $this->queryRegenerateTask = new QueryRegenerateEvent($this);
            $this->queryRegenerateTask->call();

            $this->network->updateName();
		}

		if($this->autoSave and ++$this->autoSaveTicker >= $this->autoSaveTicks){
			$this->autoSaveTicker = 0;
			$this->doAutoSave();
		}

		if(($this->tickCounter % 100) === 0){
			foreach($this->levels as $level){
				$level->clearCache();
			}

			if($this->getTicksPerSecondAverage() < 12){
				$this->logger->warning($this->getLanguage()->translateString("pocketmine.server.tickOverload"));
			}
		}

		if($this->dispatchSignals and $this->tickCounter % 5 === 0){
			pcntl_signal_dispatch();
		}

		$this->getMemoryManager()->check();

		Timings::$serverTickTimer->stopTiming();

		$now = microtime(true);
		$this->currentTPS = min(20, 1 / max(0.001, $now - $tickTime));
		$this->currentUse = min(1, ($now - $tickTime) / 0.05);

		TimingsHandler::tick($this->currentTPS <= $this->profilingTickRate);

		$idx = $this->tickCounter % 20;
		$this->tickAverage[$idx] = $this->currentTPS;
		$this->useAverage[$idx] = $this->currentUse;

		if(($this->nextTick - $tickTime) < -1){
			$this->nextTick = $tickTime;
		}else{
			$this->nextTick += 0.05;
		}

		return true;
	}

	public function __sleep(){
		throw new \BadMethodCallException("Cannot serialize Server instance");
	}
}


