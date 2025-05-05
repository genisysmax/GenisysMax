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
 * All the entity classes
 */
namespace pocketmine\entity;

use ErrorException;
use InvalidArgumentException;
use InvalidStateException;
use pocketmine\BedrockPlayer;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\Fire;
use pocketmine\block\Lava;
use pocketmine\block\Portal;
use pocketmine\block\Water;
use pocketmine\entity\object\EnderCrystal;
use pocketmine\entity\object\FallingBlock;
use pocketmine\entity\object\Item;
use pocketmine\entity\object\MinecartEmpty;
use pocketmine\entity\object\PrimedTNT;
use pocketmine\entity\object\XPOrb;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\Egg;
use pocketmine\entity\projectile\EnderPearl;
use pocketmine\entity\projectile\ExpBottle;
use pocketmine\entity\projectile\FishingHook;
use pocketmine\entity\projectile\Snowball;
use pocketmine\entity\projectile\SplashPotion;
use pocketmine\entity\weather\Lightning;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\player\PlayerEntityInteractEvent;
use pocketmine\event\Timings;
use pocketmine\event\TimingsHandler;
use pocketmine\item\Item as ItemItem;
use pocketmine\level\format\Chunk;
use pocketmine\level\generator\biome\Biome;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\level\weather\Weather;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\metadata\Metadatable;
use pocketmine\metadata\MetadataValue;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\bedrock\PacketTranslator;
use pocketmine\network\bedrock\protocol\SetActorDataPacket as BedrockSetActorDataPacket;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\network\mcpe\protocol\MoveEntityPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\network\mcpe\protocol\SetEntityDataPacket;
use pocketmine\network\mcpe\protocol\SetEntityLinkPacket;
use pocketmine\network\mcpe\protocol\SetEntityMotionPacket;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\Random;
use ReflectionClass;
use function abs;
use function assert;
use function count;
use function floor;
use function get_class;
use function is_a;
use function is_array;
use function is_infinite;
use function is_nan;
use function lcg_value;

abstract class Entity extends Location implements Metadatable, EntityIds{

	public const MOTION_THRESHOLD = 0.00001;
	protected const STEP_CLIP_MULTIPLIER = 0.4;

	public const NETWORK_ID = -1;

	public const int DATA_TYPE_BYTE = 0;
	public const int DATA_TYPE_SHORT = 1;
	public const int DATA_TYPE_INT = 2;
	public const int DATA_TYPE_FLOAT = 3;
	public const int DATA_TYPE_STRING = 4;
	public const int DATA_TYPE_COMPOUND_TAG = 5;
	public const int DATA_TYPE_POS = 6;
	public const int DATA_TYPE_LONG = 7;
	public const int DATA_TYPE_VECTOR3F = 8;

	public const DATA_FLAGS = 0;
	public const DATA_HEALTH = 1; //int (minecart/boat)
	public const DATA_VARIANT = 2; //int
	public const DATA_COLOR = 3, DATA_COLOUR = 3; //byte
	public const DATA_NAMETAG = 4; //string
	public const DATA_OWNER_EID = 5; //long
	public const DATA_TARGET_EID = 6; //long
	public const DATA_AIR = 7; //short
	public const DATA_POTION_COLOR = 8; //int (ARGB!)
	public const DATA_POTION_AMBIENT = 9; //byte
    public const DATA_JUMP_DURATION = 10; //byte
	public const DATA_HURT_TIME = 11; //int (minecart/boat)
	public const DATA_HURT_DIRECTION = 12; //int (minecart/boat)
	public const DATA_PADDLE_TIME_LEFT = 13; //float
	public const DATA_PADDLE_TIME_RIGHT = 14; //float
	public const DATA_EXPERIENCE_VALUE = 15; //int (xp orb)
	public const DATA_MINECART_DISPLAY_BLOCK = 16; //int (id | (data << 16))
	public const DATA_MINECART_DISPLAY_OFFSET = 17; //int
	public const DATA_MINECART_HAS_DISPLAY = 18; //byte (must be 1 for minecart to show block inside)
    public const DATA_HORSE_TYPE = 19; //byte
    public const DATA_CREEPER_SWELL = 19; //int
    public const DATA_CREEPER_SWELL_PREVIOUS = 20; //int
    public const DATA_CREEPER_SWELL_DIRECTION = 21; //byte
    public const DATA_CHARGE_AMOUNT = 22; //int8, used for ghasts and also crossbow charging
	public const DATA_ENDERMAN_HELD_ITEM_ID = 23; //short
	public const DATA_ENDERMAN_HELD_ITEM_DAMAGE = 24; //short
	public const DATA_ENTITY_AGE = 25; //short

	/* 27 (byte) player-specific flags
	 * 28 (int) player "index"?
	 * 29 (block coords) bed position */
	public const DATA_FIREBALL_POWER_X = 30; //float
	public const DATA_FIREBALL_POWER_Y = 31;
	public const DATA_FIREBALL_POWER_Z = 32;
	/* 33 (unknown)
	 * 34 (float) fishing bobber
	 * 35 (float) fishing bobber
	 * 36 (float) fishing bobber */
	public const DATA_POTION_AUX_VALUE = 37; //short
	public const DATA_LEAD_HOLDER_EID = 38; //long
	public const DATA_SCALE = 39; //float
	public const DATA_INTERACTIVE_TAG = 40; //string (button text)
	public const DATA_NPC_SKIN_ID = 41; //string
	public const DATA_URL_TAG = 42; //string
	public const DATA_MAX_AIR = 43; //short
	public const DATA_MARK_VARIANT = 44; //int
	public const DATA_CONTAINER_TYPE = 45; //byte (ContainerComponent)
	public const DATA_CONTAINER_BASE_SIZE = 46; //int (ContainerComponent)
	public const DATA_CONTAINER_EXTRA_SLOTS_PER_STRENGTH = 47; //int (used for llamas, inventory size is baseSize + thisProp * strength)
	public const DATA_BLOCK_TARGET = 48; //block coords (ender crystal)
	public const DATA_WITHER_INVULNERABLE_TICKS = 49; //int
	public const DATA_WITHER_TARGET_1 = 50; //long
	public const DATA_WITHER_TARGET_2 = 51; //long
	public const DATA_WITHER_TARGET_3 = 52; //long
	/* 53 (short) */
	public const DATA_BOUNDING_BOX_WIDTH = 54; //float
	public const DATA_BOUNDING_BOX_HEIGHT = 55; //float
	public const DATA_FUSE_LENGTH = 56; //int
	public const DATA_RIDER_SEAT_POSITION = 57; //vector3f
	public const DATA_RIDER_ROTATION_LOCKED = 58; //byte
	public const DATA_RIDER_MAX_ROTATION = 59; //float
	public const DATA_RIDER_MIN_ROTATION = 60; //float
	public const DATA_AREA_EFFECT_CLOUD_RADIUS = 61; //float
	public const DATA_AREA_EFFECT_CLOUD_WAITING = 62; //int
	public const DATA_AREA_EFFECT_CLOUD_PARTICLE_ID = 63; //int
	/* 64 (int) shulker-related */
	public const DATA_SHULKER_ATTACH_FACE = 65; //byte
	/* 66 (short) shulker-related */
	public const DATA_SHULKER_ATTACH_POS = 67; //block coords
	public const DATA_TRADING_PLAYER_EID = 68; //long

	/* 70 (byte) command-block */
	public const DATA_COMMAND_BLOCK_COMMAND = 71; //string
	public const DATA_COMMAND_BLOCK_LAST_OUTPUT = 72; //string
	public const DATA_COMMAND_BLOCK_TRACK_OUTPUT = 73; //byte
	public const DATA_CONTROLLING_RIDER_SEAT_NUMBER = 74; //byte
	public const DATA_STRENGTH = 75; //int
	public const DATA_MAX_STRENGTH = 76; //int
	/* 77 (int)
	 * 78 (int) */
    public const DATA_FLAGS2 = 91; //long (extended data flags)

	public const DATA_FLAG_ONFIRE = 0;
	public const DATA_FLAG_SNEAKING = 1;
	public const DATA_FLAG_RIDING = 2;
	public const DATA_FLAG_SPRINTING = 3;
	public const DATA_FLAG_ACTION = 4;
	public const DATA_FLAG_INVISIBLE = 5;
	public const DATA_FLAG_TEMPTED = 6;
	public const DATA_FLAG_INLOVE = 7;
	public const DATA_FLAG_SADDLED = 8;
	public const DATA_FLAG_POWERED = 9;
	public const DATA_FLAG_IGNITED = 10;
	public const DATA_FLAG_BABY = 11;
	public const DATA_FLAG_CONVERTING = 12;
	public const DATA_FLAG_CRITICAL = 13;
	public const DATA_FLAG_CAN_SHOW_NAMETAG = 14;
	public const DATA_FLAG_ALWAYS_SHOW_NAMETAG = 15;
	public const DATA_FLAG_IMMOBILE = 16, DATA_FLAG_NO_AI = 16;
	public const DATA_FLAG_SILENT = 17;
	public const DATA_FLAG_WALLCLIMBING = 18;
	public const DATA_FLAG_CAN_CLIMB = 19;
	public const DATA_FLAG_SWIMMER = 20;
	public const DATA_FLAG_CAN_FLY = 21;
	public const DATA_FLAG_RESTING = 22;
	public const DATA_FLAG_SITTING = 23;
	public const DATA_FLAG_ANGRY = 24;
	public const DATA_FLAG_INTERESTED = 25;
	public const DATA_FLAG_CHARGED = 26;
	public const DATA_FLAG_TAMED = 27;
	public const DATA_FLAG_LEASHED = 28;
	public const DATA_FLAG_SHEARED = 29;
	public const DATA_FLAG_GLIDING = 30;
	public const DATA_FLAG_ELDER = 31;
	public const DATA_FLAG_MOVING = 32;
	public const DATA_FLAG_BREATHING = 33;
	public const DATA_FLAG_CHESTED = 34;
	public const DATA_FLAG_STACKABLE = 35;
	public const DATA_FLAG_SHOWBASE = 36;
	public const DATA_FLAG_REARING = 37;
	public const DATA_FLAG_VIBRATING = 38;
	public const DATA_FLAG_IDLING = 39;
	public const DATA_FLAG_EVOKER_SPELL = 40;
    public const DATA_FLAG_CHARGE_ATTACK = 41;

    public const DATA_FLAG_WASD_CONTROLLED = 43; //OR 42 ????
    public const DATA_FLAG_CAN_POWER_JUMP = 44;

	public const DATA_FLAG_LINGER = 45;
    public const DATA_FLAG_HAS_COLLISION = 46;
    public const DATA_FLAG_AFFECTED_BY_GRAVITY = 47;

	public static int $entityCount = 1;
	/** @var Entity[] */
	private static array $knownEntities = [];
	private static array $saveNames = [];

	public static function init():void{
        Entity::registerEntity(Arrow::class, false, ['Arrow', 'minecraft:arrow']);
        Entity::registerEntity(Egg::class, false, ['Egg', 'minecraft:egg']);
        Entity::registerEntity(EnderPearl::class, false, ['ThrownEnderpearl', 'minecraft:ender_pearl']);
        Entity::registerEntity(XPOrb::class, false, ['XPOrb', 'minecraft:xp_orb']);
        Entity::registerEntity(FallingBlock::class, false, ['FallingSand', 'minecraft:falling_block']);
        Entity::registerEntity(Item::class, false, ['Item', 'minecraft:item']);
        Entity::registerEntity(PrimedTNT::class, false, ['PrimedTnt', 'PrimedTNT', 'minecraft:tnt']);
        Entity::registerEntity(Snowball::class, false, ['Snowball', 'minecraft:snowball']);
        Entity::registerEntity(SplashPotion::class, false, ['ThrownPotion', 'minecraft:potion', 'thrownpotion']);
        Entity::registerEntity(Squid::class, false, ['Squid', 'minecraft:squid']);
        Entity::registerEntity(Villager::class, false, ['Villager', 'minecraft:villager']);
        Entity::registerEntity(Zombie::class, false, ['Zombie', 'minecraft:zombie']);
        Entity::registerEntity(FishingHook::class, false, ['FishingHook', 'minecraft:fishing_hook']);
        Entity::registerEntity(MinecartEmpty::class, false, ['Minecart', 'minecraft:minecart']);
        Entity::registerEntity(EnderCrystal::class, false, ['EnderCrystal', 'minecraft:ender_crystal']);
        Entity::registerEntity(Lightning::class, false, ['Lightning', 'minecraft:lighting']);
        Entity::registerEntity(ExpBottle::class, false, ['ThrownExpBottle', 'minecraft:xp_bottle']);

		Entity::registerEntity(Human::class, true);
	}

    /**
     * Creates an entity with the specified type, level and NBT, with optional additional arguments to pass to the
     * entity's constructor
     *
     * @param int|string  $type
     * @param Level       $level
     * @param CompoundTag $nbt
     * @param mixed       ...$args
     *
     * @return Entity|null
     */
    public static function createEntity($type, Level $level, CompoundTag $nbt, ...$args) : ?Entity{
        if(isset(self::$knownEntities[$type])){
            $class = self::$knownEntities[$type];
            /** @see Entity::__construct() */
            return new $class($level, $nbt, ...$args);
        }

        return null;
    }

    /**
     * Registers an entity type into the index.
     *
     * @param string                       $className Class that extends Entity
     * @param bool                         $force Force registration even if the entity does not have a valid network ID
     * @param string[]                     $saveNames An array of save names which this entity might be saved under. Defaults to the short name of the class itself if empty.
     *
     * @phpstan-param class-string<Entity> $className
     *
     * NOTE: The first save name in the $saveNames array will be used when saving the entity to disk. The reflection
     * name of the class will be appended to the end and only used if no other save names are specified.
     */
    public static function registerEntity(string $className, bool $force = false, array $saveNames = []) : bool{
        /** @var Entity $className */
        $class = new ReflectionClass($className);
        if(is_a($className, Entity::class, true) and !$class->isAbstract()){
            if($className::NETWORK_ID !== -1){
                self::$knownEntities[$className::NETWORK_ID] = $className;
            }elseif(!$force){
                return false;
            }

            $shortName = $class->getShortName();
            if(!in_array($shortName, $saveNames, true)){
                $saveNames[] = $shortName;
            }

            foreach($saveNames as $name){
                self::$knownEntities[$name] = $className;
            }

            self::$saveNames[$className] = $saveNames;

            return true;
        }

        return false;
    }

	/**
	 * @var Player[]
	 */
	protected array $hasSpawned = [];

	protected int $id = -1;

    protected DataPropertyManager $propertyManager;

	public ?Chunk $chunk = null;

	protected ?EntityDamageEvent $lastDamageCause = null;

	/** @var Block[] */
	protected ?array $blocksAround = [];

	public int|float|null $lastX = null;
	public int|float|null $lastY = null;
	public int|float|null $lastZ = null;

	public int|float $motionX = 0;
	public int|float $motionY = 0;
	public int|float $motionZ = 0;

	/** @var Vector3 */
	public Vector3 $temporalVector;

	public int|float $lastMotionX;
	public int|float $lastMotionY;
	public int|float $lastMotionZ;

	protected bool $forceMovementUpdate = false;

	public float $lastYaw;
	public float $lastPitch;

	public ?AxisAlignedBB $boundingBox = null;
	public bool $onGround = false;

    public int $deadTicks = 0;
    protected int $maxDeadTicks = 25;

	protected $age = 0;

	public float $height;
	public float $width;

	public ?float $eyeHeight = null;

	protected float $baseOffset = 0.0;

	private float $health = 20.0;
	private int $maxHealth = 20;

	protected float $ySize = 0;
	protected float $stepHeight = 0;
	public bool $keepMovement = false;

	public float $fallDistance = 0.0;
	public int $ticksLived = 0;
	public int $lastUpdate = 0;
	public int $fireTicks = 0;
	public ?CompoundTag $namedtag = null;
	public bool $canCollide = true;

	public bool $isCollided = false;
	public bool $isCollidedHorizontally = false;
	public bool $isCollidedVertically = false;

	public int $noDamageTicks = 0;
	protected bool $justCreated = true;
	private bool $invulnerable = false;

	/** @var AttributeMap */
	protected AttributeMap $attributeMap;

	public float $gravity = 0.0;
	public float $drag = 0.0;

	protected Server $server;

	public bool $closed = false;
	private bool $needsDespawn = false;

    private bool $closeInFlight = false;

	protected TimingsHandler $timings;

    public ?Entity $linkedEntity = null;
    /** 0 no linked 1 linked other 2 be linked */
    protected ?int $linkedType = null;

    protected bool $constructed = false;
    public Random $random;

    private bool $savedWithChunk = true;

	public function getServer() : Server{
		return $this->server;
	}

	/**
	 * @deprecated
	 *
	 * @return Effect[]
	 */
	public function getEffects() : array{
		return [];
	}

	/**
	 * @deprecated
	 */
	public function removeAllEffects(){

	}

	/**
	 * @deprecated
	 *
	 * @param int $effectId
	 */
	public function removeEffect(int $effectId){

	}

	/**
	 * @deprecated
	 *
	 * @param int $effectId
	 *
	 * @return EffectInstance|null
	 */
	public function getEffect(int $effectId) : ?EffectInstance{
		return null;
	}

	/**
	 * @deprecated
	 *
	 * @param int $effectId
	 *
	 * @return bool
	 */
	public function hasEffect(int $effectId) : bool{
		return false;
	}

	/**
	 * @deprecated
	 *
	 * @param EffectInstance $effect
	 */
	public function addEffect(EffectInstance $effect){
		throw new \BadMethodCallException("Cannot add effects to non-living entities");
	}

	/**
	 * Helper function which creates minimal NBT needed to spawn an entity.
	 *
	 * @param Vector3      $pos
	 * @param Vector3|null $motion
	 * @param float        $yaw
	 * @param float        $pitch
	 *
	 * @return CompoundTag
	 */
	public static function createBaseNBT(Vector3 $pos, ?Vector3 $motion = null, float $yaw = 0.0, float $pitch = 0.0) : CompoundTag{
		return EntityDataHelper::createBaseNBT($pos, $motion, $yaw, $pitch);
	}

	/**
	 * @deprecated
	 *
	 * @param Player $player
	 */
	public function sendPotionEffects(Player $player){

	}

    public function setDataFlag(int $propertyId, int $flagId, bool $value = true, int $propertyType = self::DATA_TYPE_LONG) : void{
        if($this->getDataFlag($propertyId, $flagId) !== $value){
            $flags = (int) $this->propertyManager->getPropertyValue($propertyId, $propertyType);
            $flags ^= 1 << $flagId;
            $this->propertyManager->setPropertyValue($propertyId, $propertyType, $flags);
        }
    }

    public function getDataFlag(int $propertyId, int $flagId) : bool{
        return (((int) $this->propertyManager->getPropertyValue($propertyId, -1)) & (1 << $flagId)) > 0;
    }

    /**
     * Wrapper around {@link Entity#getDataFlag} for generic data flag reading.
     */
    public function getGenericFlag(int $flagId) : bool{
        return $this->getDataFlag($flagId >= 64 ? self::DATA_FLAGS2 : self::DATA_FLAGS, $flagId % 64);
    }

    /**
     * Wrapper around {@link Entity#setDataFlag} for generic data flag setting.
     */
    public function setGenericFlag(int $flagId, bool $value = true) : void{
        $this->setDataFlag($flagId >= 64 ? self::DATA_FLAGS2 : self::DATA_FLAGS, $flagId % 64, $value, self::DATA_TYPE_LONG);
    }

	/**
	 * @param Player[]|Player $player
	 * @param array           $data Properly formatted entity data, defaults to everything
	 */
	public function sendData($player, array $data = null){
		if(!is_array($player)){
			$player = [$player];
		}

		$pk = new SetEntityDataPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->metadata = $data ?? $this->propertyManager->getAll();

		$bk = new BedrockSetActorDataPacket();
		$bk->actorRuntimeId = $this->getId();
		$bk->metadata = PacketTranslator::translateMetadata($pk->metadata);

		foreach($player as $p){
			if($p === $this){
				continue;
			}
			$p->sendDataPacket($p instanceof BedrockPlayer ? clone $bk : clone $pk);
		}

		if($this instanceof Player){
			$this->sendDataPacket($this instanceof BedrockPlayer ? $bk : $pk);
		}
	}

    public function broadcastEntityEvent(int $eventId, int $eventData = null, array $players = null) : void{
        $pk = new EntityEventPacket();
        $pk->entityRuntimeId = $this->id;
        $pk->event = $eventId;
        $pk->data = $eventData ?? 0;
        $this->server->broadcastPacket($players ?? $this->getViewers(), $pk);
    }

	public function onInteract(Player $player, ItemItem $item, Vector3 $clickPos) : bool{
		$ev = new PlayerEntityInteractEvent($player, $this, $item, $clickPos);
		$ev->call();

		if($ev->isCancelled()){
			return false;
		}
		return true;
	}

    public function __construct(Level $level, CompoundTag $nbt){
        $this->random = new Random(intval(microtime(true) * 1000));
        $this->constructed = true;
        $this->timings = Timings::getEntityTimings($this);

        $this->temporalVector = new Vector3();

        if($this->eyeHeight === null){
            $this->eyeHeight = $this->height * 0.85;
        }

        $this->id = Entity::$entityCount++;
        $this->namedtag = $nbt;
        $this->server = $level->getServer();

        $loc = EntityDataHelper::parseLocation($this->namedtag, $level);

        parent::__construct($loc->x, $loc->y, $loc->z, $loc->yaw, $loc->pitch, $level);
        assert(!is_nan($this->x) and !is_infinite($this->x) and !is_nan($this->y) and !is_infinite($this->y) and !is_nan($this->z) and !is_infinite($this->z));

        $this->boundingBox = new AxisAlignedBB(0, 0, 0, 0, 0, 0);
        $this->recalculateBoundingBox();

        $this->chunk = $level->getChunk(((int)$loc->x) >> 4, ((int)$loc->z) >> 4, true);
        assert($this->chunk !== null);

        $this->setMotion(EntityDataHelper::parseVec3($nbt, "Motion", true));

        $this->resetLastMovements();

        $this->fallDistance = $this->namedtag->getFloat("FallDistance", 0.0);

        $this->propertyManager = new DataPropertyManager();

        $this->propertyManager->setLong(self::DATA_FLAGS, 0);
        $this->propertyManager->setShort(self::DATA_MAX_AIR, 400);
        $this->propertyManager->setString(self::DATA_NAMETAG, "");
        $this->propertyManager->setLong(self::DATA_LEAD_HOLDER_EID, -1);
        $this->propertyManager->setFloat(self::DATA_SCALE, 1);
        $this->propertyManager->setFloat(self::DATA_BOUNDING_BOX_WIDTH, $this->width);
        $this->propertyManager->setFloat(self::DATA_BOUNDING_BOX_HEIGHT, $this->height);
        $this->propertyManager->setFloat(self::DATA_COLOR, 0);

        if(!$this->namedtag->hasTag("Fire", IntTag::class)){
            $this->namedtag->setInt("Fire", 0);
        }
        $this->fireTicks = $this->namedtag->getInt("Fire");
        if($this->isOnFire()){
            $this->setGenericFlag(self::DATA_FLAG_ONFIRE);
        }

        $this->propertyManager->setShort(self::DATA_AIR, $this->namedtag->getShort("Air", 300));
        $this->onGround = $this->namedtag->getByte("OnGround", 0) !== 0;
        $this->invulnerable = $this->namedtag->getByte("Invulnerable", 0) !== 0;

        $this->attributeMap = new AttributeMap();
        $this->addAttributes();

        $this->setGenericFlag(self::DATA_FLAG_AFFECTED_BY_GRAVITY, true);
        $this->setGenericFlag(self::DATA_FLAG_HAS_COLLISION, true);

        $this->initEntity();
        $this->propertyManager->clearDirtyProperties(); //Prevents resending properties that were set during construction

        $this->chunk->addEntity($this);
        $this->level->addEntity($this);

        $this->lastUpdate = $this->server->getTick();
        (new EntitySpawnEvent($this))->call();

        $this->scheduleUpdate();
    }

    /**
     * @return string
     */
    public function getNameTag() : string{
        return $this->propertyManager->getString(self::DATA_NAMETAG);
    }

    /**
     * @return bool
     */
    public function isNameTagVisible() : bool{
        return $this->getGenericFlag(self::DATA_FLAG_CAN_SHOW_NAMETAG);
    }

    /**
     * @return bool
     */
    public function isNameTagAlwaysVisible() : bool{
        return $this->getGenericFlag(self::DATA_FLAG_ALWAYS_SHOW_NAMETAG);
    }


    /**
     * @param string $name
     */
    public function setNameTag(string $name) : void{
        $this->propertyManager->setString(self::DATA_NAMETAG, $name);
    }

    /**
     * @param bool $value
     */
    public function setNameTagVisible(bool $value = true) : void{
        $this->setGenericFlag(self::DATA_FLAG_CAN_SHOW_NAMETAG, $value);
    }

    /**
     * @param bool $value
     */
    public function setNameTagAlwaysVisible(bool $value = true) : void{
        $this->setGenericFlag(self::DATA_FLAG_ALWAYS_SHOW_NAMETAG, $value);
    }

    /**
     * @return float
     */
    public function getScale() : float{
        return $this->propertyManager->getFloat(self::DATA_SCALE);
    }

    /**
     * @param float $value
     */
    public function setScale(float $value) : void{
        if($value <= 0){
            throw new InvalidArgumentException("Scale must be greater than 0");
        }
        $multiplier = $value / $this->getScale();

        $this->width *= $multiplier;
        $this->height *= $multiplier;
        $this->eyeHeight *= $multiplier;

        $this->recalculateBoundingBox();

        $this->propertyManager->setFloat(self::DATA_SCALE, $value);
    }

    public function isInLove() : bool{
        return $this->getDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INLOVE);
    }

    public function setInLove(bool $value) : void{
        $this->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INLOVE, $value);
    }

    public function isRiding() : bool{
        return $this->getDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_RIDING);
    }

    public function setRiding(bool $value) : void{
        $this->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_RIDING, $value);
    }

    public function isBaby() : bool{
        return $this->getGenericFlag(self::DATA_FLAG_BABY);
    }

    public function setBaby(bool $value = true) : void{
        $this->setGenericFlag(self::DATA_FLAG_BABY, $value);
        $this->setScale($value ? 0.5 : 1.0);
    }

    public function getBoundingBox() : AxisAlignedBB{
        return $this->boundingBox;
    }

    protected function recalculateBoundingBox() : void{
        $halfWidth = $this->width / 2;

        $this->boundingBox->setBounds(
            $this->x - $halfWidth,
            $this->y + $this->ySize,
            $this->z - $halfWidth,
            $this->x + $halfWidth,
            $this->y + $this->height + $this->ySize,
            $this->z + $halfWidth
        );
    }

    /**
     * Update entity's height and width
     *
     * @param float $height
     * @param float $width
     */
    public function updateBoundingBox(float $height, float $width) : void{
        $this->height = $height;
        $this->width = $width;

        $this->recalculateBoundingBox();
        $this->propertyManager->setFloat(self::DATA_BOUNDING_BOX_WIDTH, $width);
        $this->propertyManager->setFloat(self::DATA_BOUNDING_BOX_HEIGHT, $height);
    }

    public function isSneaking() : bool{
        return $this->getGenericFlag(self::DATA_FLAG_SNEAKING);
    }

    public function setSneaking(bool $value = true) : void{
        $this->setGenericFlag(self::DATA_FLAG_SNEAKING, $value);
    }

    public function isSprinting() : bool{
        return $this->getGenericFlag(self::DATA_FLAG_SPRINTING);
    }

    public function setSprinting(bool $value = true) : void{
        if($value !== $this->isSprinting()){
            $this->setGenericFlag(self::DATA_FLAG_SPRINTING, $value);
            $attr = $this->attributeMap->getAttribute(Attribute::MOVEMENT_SPEED);
            $attr->setValue($value ? ($attr->getValue() * 1.3) : ($attr->getValue() / 1.3), false, true);
        }
    }

    public function isSwimmer() : bool{
        return $this->getGenericFlag(self::DATA_FLAG_SWIMMER);
    }

    public function setSwimmer(bool $value = true) : void{
        $this->setGenericFlag(self::DATA_FLAG_SWIMMER, $value);
    }

    public function isImmobile() : bool{
        return $this->getGenericFlag(self::DATA_FLAG_IMMOBILE);
    }

    public function setImmobile(bool $value = true) : void{
        $this->setGenericFlag(self::DATA_FLAG_IMMOBILE, $value);
    }

    public function isInvisible() : bool{
        return $this->getGenericFlag(self::DATA_FLAG_INVISIBLE);
    }

    public function setInvisible(bool $value = true) : void{
        $this->setGenericFlag(self::DATA_FLAG_INVISIBLE, $value);
    }

    public function isGliding() : bool{
        return $this->getGenericFlag(self::DATA_FLAG_GLIDING);
    }

    public function setGliding(bool $value = true) : void{
        $this->setGenericFlag(self::DATA_FLAG_GLIDING, $value);
    }

    /**
     * Returns whether the entity is able to climb blocks such as ladders or vines.
     * @return bool
     */
    public function canClimb() : bool{
        return $this->getGenericFlag(self::DATA_FLAG_CAN_CLIMB);
    }

    /**
     * Sets whether the entity is able to climb climbable blocks.
     *
     * @param bool $value
     */
    public function setCanClimb(bool $value = true) : void{
        $this->setGenericFlag(self::DATA_FLAG_CAN_CLIMB, $value);
    }

    /**
     * Returns whether the entity is able to fly
     * @return bool
     */
    public function canFly() : bool{
        return $this->getGenericFlag(self::DATA_FLAG_CAN_FLY);
    }

    /**
     * Sets whether the entity is able to fly
     *
     * @param bool $value
     */
    public function setCanFly(bool $value = true) : void{
        $this->setGenericFlag(self::DATA_FLAG_CAN_FLY, $value);
    }

    /**
     * Returns whether this entity is climbing a block. By default this is only true if the entity is climbing a ladder or vine or similar block.
     *
     * @return bool
     */
    public function canClimbWalls() : bool{
        return $this->getGenericFlag(self::DATA_FLAG_WALLCLIMBING);
    }

    /**
     * Sets whether the entity is climbing a block. If true, the entity can climb anything.
     *
     * @param bool $value
     */
    public function setCanClimbWalls(bool $value = true) : void{
        $this->setGenericFlag(self::DATA_FLAG_WALLCLIMBING, $value);
    }

    /**
     * Returns the entity ID of the owning entity, or null if the entity doesn't have an owner.
     * @return int|null
     */
    public function getOwningEntityId() : ?int{
        return $this->propertyManager->getLong(self::DATA_OWNER_EID);
    }

    /**
     * Returns the owning entity, or null if the entity was not found.
     * @return Entity|null
     */
    public function getOwningEntity() : ?Entity{
        $eid = $this->getOwningEntityId();
        if($eid !== null){
            return $this->server->findEntity($eid);
        }

        return null;
    }

    /**
     * Sets the owner of the entity. Passing null will remove the current owner.
     *
     * @param Entity|null $owner
     *
     * @throws InvalidArgumentException if the supplied entity is not valid
     */
    public function setOwningEntity(?Entity $owner) : void{
        if($owner === null){
            $this->propertyManager->removeProperty(self::DATA_OWNER_EID);
        }elseif($owner->closed){
            throw new InvalidArgumentException("Supplied owning entity is garbage and cannot be used");
        }else{
            $this->propertyManager->setLong(self::DATA_OWNER_EID, $owner->getId());
        }
    }

    /**
     * Returns the entity ID of the entity's target, or null if it doesn't have a target.
     * @return int|null
     */
    public function getTargetEntityId() : ?int{
        return $this->propertyManager->getLong(self::DATA_TARGET_EID);
    }

    /**
     * Returns the entity's target entity, or null if not found.
     * This is used for things like hostile mobs attacking entities, and for fishing rods reeling hit entities in.
     *
     * @return Entity|null
     */
    public function getTargetEntity() : ?Entity{
        $eid = $this->getTargetEntityId();
        if($eid !== null){
            return $this->server->findEntity($eid);
        }

        return null;
    }

    /**
     * Sets the entity's target entity. Passing null will remove the current target.
     *
     * @param Entity|null $target
     *
     * @throws InvalidArgumentException if the target entity is not valid
     */
    public function setTargetEntity(?Entity $target) : void{
        if($target === null){
            $this->propertyManager->removeProperty(self::DATA_TARGET_EID);
        }elseif($target->closed){
            throw new InvalidArgumentException("Supplied target entity is garbage and cannot be used");
        }else{
            $this->propertyManager->setLong(self::DATA_TARGET_EID, $target->getId());
        }
    }

    /**
     * Returns whether this entity will be saved when its chunk is unloaded.
     * @return bool
     */
    public function canSaveWithChunk() : bool{
        return $this->savedWithChunk;
    }

    /**
     * Sets whether this entity will be saved when its chunk is unloaded. This can be used to prevent the entity being
     * saved to disk.
     *
     * @param bool $value
     */
    public function setCanSaveWithChunk(bool $value) : void{
        $this->savedWithChunk = $value;
    }

    /**
     * Returns the short save name
     *
     * @return string
     */
    public function getSaveId() : string{
        if(!isset(self::$saveNames[static::class])){
            throw new InvalidStateException("Entity is not registered");
        }
        reset(self::$saveNames[static::class]);
        return current(self::$saveNames[static::class]);
    }

    public function saveNBT() : void{
        if(!($this instanceof Player)){
            $this->namedtag->setString("id", $this->getSaveId());
            if($this->getNameTag() !== ""){
                $this->namedtag->setString("CustomName", $this->getNameTag());
                $this->namedtag->setByte("CustomNameVisible", $this->isNameTagVisible() ? 1 : 0);
                $this->namedtag->setByte("CustomNameAlwaysVisible", $this->isNameTagAlwaysVisible() ? 1 : 0);
            }else{
                $this->namedtag->removeTag("CustomName", "CustomNameVisible", "CustomNameAlwaysVisible");
            }
        }

        $this->namedtag->setTag("Pos", new ListTag([
            new DoubleTag($this->x),
            new DoubleTag($this->y),
            new DoubleTag($this->z)
        ]));

        $this->namedtag->setTag("Motion", new ListTag([
            new DoubleTag($this->motionX),
            new DoubleTag($this->motionY),
            new DoubleTag($this->motionZ)
        ]));

        $this->namedtag->setTag("Rotation", new ListTag([
            new FloatTag($this->yaw),
            new FloatTag($this->pitch)
        ]));

        $this->namedtag->setFloat("FallDistance", $this->fallDistance);
        $this->namedtag->setInt("Fire", $this->fireTicks);
        $this->namedtag->setShort("Air", $this->propertyManager->getShort(self::DATA_AIR));
        $this->namedtag->setByte("OnGround", $this->onGround ? 1 : 0);
        $this->namedtag->setByte("Invulnerable", $this->invulnerable ? 1 : 0);
    }

    protected function initEntity() : void{
        assert($this->namedtag instanceof CompoundTag);

        if($this->namedtag->hasTag("CustomName", StringTag::class)){
            $this->setNameTag($this->namedtag->getString("CustomName"));
            if($this->namedtag->hasTag("CustomNameVisible", ByteTag::class)){
                $this->setNameTagVisible($this->namedtag->getByte("CustomNameVisible") > 0);
            }

            if($this->namedtag->hasTag("CustomNameAlwaysVisible", ByteTag::class)){
                $this->setNameTagAlwaysVisible($this->namedtag->getByte("CustomNameAlwaysVisible") > 0);
            }
        }

        $this->scheduleUpdate();
    }

    protected function addAttributes() : void{

    }

    /**
     * @param EntityDamageEvent $source
     */
    public function attack(EntityDamageEvent $source) : void{
        $source->call();
        if($source->isCancelled()){
            return;
        }

        $this->setLastDamageCause($source);

        $this->setHealth($this->getHealth() - $source->getFinalDamage());
    }

    /**
     * @param EntityRegainHealthEvent $source
     */
    public function heal(EntityRegainHealthEvent $source) : void{
        $source->call();
        if($source->isCancelled()){
            return;
        }

        $this->setHealth($this->getHealth() + $source->getAmount());
    }

    public function kill() : void{
        $this->health = 0;
        $this->scheduleUpdate();
    }

    /**
     * Called to tick entities while dead. Returns whether the entity should be flagged for despawn yet.
     *
     * @param int $tickDiff
     *
     * @return bool
     */
    protected function onDeathUpdate(int $tickDiff) : bool{
        return true;
    }

    public function isAlive() : bool{
        return $this->health > 0;
    }

    /**
     * @return float
     */
    public function getHealth() : float{
        return $this->health;
    }

    /**
     * Sets the health of the Entity. This won't send any update to the players
     *
     * @param float $amount
     */
    public function setHealth(float $amount) : void{
        if($amount == $this->health){
            return;
        }

        if($amount <= 0){
            if($this->isAlive()){
                $this->health = 0;
                $this->kill();
            }
        }elseif($amount <= $this->getMaxHealth() or $amount < $this->health){
            $this->health = $amount;
        }else{
            $this->health = $this->getMaxHealth();
        }
    }

    /**
     * @return int
     */
    public function getMaxHealth() : int{
        return $this->maxHealth;
    }

    /**
     * @param int $amount
     */
    public function setMaxHealth(int $amount) : void{
        $this->maxHealth = $amount;
    }

    /**
     * @param EntityDamageEvent $type
     */
    public function setLastDamageCause(EntityDamageEvent $type) : void{
        $this->lastDamageCause = $type;
    }

    /**
     * @return EntityDamageEvent|null
     */
    public function getLastDamageCause() : ?EntityDamageEvent{
        return $this->lastDamageCause;
    }

    public function getAttributeMap() : AttributeMap{
        return $this->attributeMap;
    }

    public function getDataPropertyManager() : DataPropertyManager{
        return $this->propertyManager;
    }

    public function entityBaseTick(int $tickDiff = 1) : bool{
        //TODO: check vehicles

        $this->justCreated = false;

        $changedProperties = $this->propertyManager->getDirty();
        if(count($changedProperties) > 0){
            $this->sendData($this->hasSpawned, $changedProperties);
            $this->propertyManager->clearDirtyProperties();
        }

        $hasUpdate = false;

        $this->checkBlockCollision();

        if($this->y <= -16 and $this->isAlive()){
            $ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_VOID, 10);
            $this->attack($ev);
            $hasUpdate = true;
        }

        if($this->isOnFire() and in_array($this->level->getWeather()->getWeather(), [Weather::RAIN, Weather::RAINY_THUNDER, Weather::THUNDER]) and $this->level->getBiomeId($this->getFloorX(), $this->getFloorZ()) !== Biome::DESERT){
            $this->extinguish();
        }

        if($this->isOnFire() and $this->doOnFireTick($tickDiff)){
            $hasUpdate = true;
        }

        if($this->noDamageTicks > 0){
            $this->noDamageTicks -= $tickDiff;
            if($this->noDamageTicks < 0){
                $this->noDamageTicks = 0;
            }
        }

        if($this->isGliding()){
            $this->resetFallDistance();
        }

        $this->age += $tickDiff;
        $this->ticksLived += $tickDiff;

        return $hasUpdate;
    }

    public function isOnFire() : bool{
        return $this->fireTicks > 0;
    }

    public function setOnFire(int $seconds) : void{
        $ticks = $seconds * 20;
        if($ticks > $this->getFireTicks()){
            $this->setFireTicks($ticks);
        }

        $this->setGenericFlag(self::DATA_FLAG_ONFIRE, $this->isOnFire());
    }

    /**
     * @return int
     */
    public function getFireTicks() : int{
        return $this->fireTicks;
    }

    /**
     * @param int $fireTicks
     * @throws InvalidArgumentException
     */
    public function setFireTicks(int $fireTicks) : void{
        if($fireTicks < 0 or $fireTicks > 0x7fff){
            throw new InvalidArgumentException("Fire ticks must be in range 0 ... " . 0x7fff . ", got $fireTicks");
        }
        $this->fireTicks = $fireTicks;
    }

    public function extinguish() : void{
        $this->fireTicks = 0;
        $this->setGenericFlag(self::DATA_FLAG_ONFIRE, false);
    }

    public function isFireProof() : bool{
        return false;
    }

    protected function doOnFireTick(int $tickDiff = 1) : bool{
        if($this->isFireProof() and $this->fireTicks > 1){
            $this->fireTicks = 1;
        }else{
            $this->fireTicks -= $tickDiff;
        }

        if(($this->fireTicks % 20 === 0) or $tickDiff > 20){
            $this->dealFireDamage();
        }

        if(!$this->isOnFire()){
            $this->extinguish();
        }else{
            return true;
        }

        return false;
    }

    /**
     * Called to deal damage to entities when they are on fire.
     */
    protected function dealFireDamage() : void{
        $ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_FIRE_TICK, 1);
        $this->attack($ev);
    }

    public function canCollideWith(Entity $entity) : bool{
        return !$this->justCreated and $entity !== $this;
    }

    public function canBeCollidedWith() : bool{
        return $this->isAlive();
    }

    protected function updateMovement(bool $teleport = false) : void{
        //TODO: hack for client-side AI interference: prevent client sided movement when motion is 0
        $this->setImmobile($this->motionX == 0 and $this->motionY == 0 and $this->motionZ == 0);

        $diffPosition = ($this->x - $this->lastX) ** 2 + ($this->y - $this->lastY) ** 2 + ($this->z - $this->lastZ) ** 2;
        $diffRotation = ($this->yaw - $this->lastYaw) ** 2 + ($this->pitch - $this->lastPitch) ** 2;

        $lastMotion = new Vector3($this->lastMotionX, $this->lastMotionY, $this->lastMotionZ);

        $diffMotion = $this->getMotion()->subtract($lastMotion)->lengthSquared();

        $still = $this->getMotion()->lengthSquared() == 0.0;
        $wasStill = $lastMotion->lengthSquared() == 0.0;

        if($teleport or $diffPosition > 0.0001 or $diffRotation > 1.0 or (!$wasStill and $still)){
            $this->lastX = $this->x;
            $this->lastY = $this->y;
            $this->lastZ = $this->z;

            $this->lastYaw = $this->yaw;
            $this->lastPitch = $this->pitch;

            $this->broadcastMovement($teleport);
        }

        if($diffMotion > 0.0025 or $wasStill !== $still){ //0.05 ** 2
            $this->lastMotionX = $this->motionX;
            $this->lastMotionY = $this->motionY;
            $this->lastMotionZ = $this->motionZ;

            $this->broadcastMotion();
        }
    }

    public function broadcastMovement(bool $teleport = false) : void{
        $pk = new MoveEntityPacket();
        $pk->entityRuntimeId = $this->id;
        [$pk->x, $pk->y, $pk->z] = [$this->x, $this->y + $this->baseOffset, $this->z];
        $pk->yaw = $this->yaw;
        $pk->pitch = $this->pitch;
        $pk->headYaw = $this->yaw; // TODO
        $pk->onGround = $this->onGround;
        $pk->teleported = $teleport;
        $this->server->broadcastPacket($this->hasSpawned, $pk);

        if($this instanceof Player){
            $this->sendDataPacket($pk);
        }
    }

    public function broadcastMotion() : void{
        $pk = new SetEntityMotionPacket();
        $pk->entityRuntimeId = $this->id;
        [$pk->motionX, $pk->motionY, $pk->motionZ] = [$this->motionX, $this->motionY, $this->motionZ];
        $this->server->broadcastPacket($this->hasSpawned, $pk);

        if($this instanceof Player){
            $this->sendDataPacket($pk);
        }
    }

    /**
     * Pushes the other entity
     *
     * @param Entity $entity
     */
    public function applyEntityCollision(Entity $entity) : void{
            if(!($entity instanceof Player and $entity->isSpectator())){
                $d0 = $entity->x - $this->x;
                $d1 = $entity->z - $this->z;
                $d2 = abs(max($d0, $d1));

                if($d2 > 0){
                    $d2 = sqrt($d2);
                    $d0 /= $d2;
                    $d1 /= $d2;
                    $d3 = min(1, 1 / $d2);

                    $entity->setMotion($entity->getMotion()->add($d0 * $d3 * 0.05, 0, $d1 * $d3 * 0.05));
                }
            }
    }

    protected function applyDragBeforeGravity() : bool{
        return false;
    }

    protected function applyGravity() : void{
        $this->motionY -= $this->gravity;
    }

    protected function tryChangeMovement() : void{
        $friction = 1 - $this->drag;

        if($this->applyDragBeforeGravity()){
            $this->motionY *= $friction;
        }

        $this->applyGravity();

        if(!$this->applyDragBeforeGravity()){
            $this->motionY *= $friction;
        }

        if($this->onGround){
            $friction *= $this->level->getBlockAt((int) floor($this->x), (int) floor($this->y - 1), (int) floor($this->z))->getFrictionFactor();
        }

        $this->motionX *= $friction;
        $this->motionZ *= $friction;
    }

    protected function checkObstruction(float $x, float $y, float $z) : bool{
        if(count($this->level->getCollisionCubes($this, $this->getBoundingBox(), false)) === 0){
            return false;
        }

        $floorX = (int) floor($x);
        $floorY = (int) floor($y);
        $floorZ = (int) floor($z);

        $diffX = $x - $floorX;
        $diffY = $y - $floorY;
        $diffZ = $z - $floorZ;

        if(BlockFactory::$solid[$this->level->getBlockIdAt($floorX, $floorY, $floorZ)]){
            $westNonSolid  = !BlockFactory::$solid[$this->level->getBlockIdAt($floorX - 1, $floorY, $floorZ)];
            $eastNonSolid  = !BlockFactory::$solid[$this->level->getBlockIdAt($floorX + 1, $floorY, $floorZ)];
            $downNonSolid  = !BlockFactory::$solid[$this->level->getBlockIdAt($floorX, $floorY - 1, $floorZ)];
            $upNonSolid    = !BlockFactory::$solid[$this->level->getBlockIdAt($floorX, $floorY + 1, $floorZ)];
            $northNonSolid = !BlockFactory::$solid[$this->level->getBlockIdAt($floorX, $floorY, $floorZ - 1)];
            $southNonSolid = !BlockFactory::$solid[$this->level->getBlockIdAt($floorX, $floorY, $floorZ + 1)];

            $direction = -1;
            $limit = 9999;

            if($westNonSolid){
                $limit = $diffX;
                $direction = Vector3::SIDE_WEST;
            }

            if($eastNonSolid and 1 - $diffX < $limit){
                $limit = 1 - $diffX;
                $direction = Vector3::SIDE_EAST;
            }

            if($downNonSolid and $diffY < $limit){
                $limit = $diffY;
                $direction = Vector3::SIDE_DOWN;
            }

            if($upNonSolid and 1 - $diffY < $limit){
                $limit = 1 - $diffY;
                $direction = Vector3::SIDE_UP;
            }

            if($northNonSolid and $diffZ < $limit){
                $limit = $diffZ;
                $direction = Vector3::SIDE_NORTH;
            }

            if($southNonSolid and 1 - $diffZ < $limit){
                $direction = Vector3::SIDE_SOUTH;
            }

            $force = lcg_value() * 0.2 + 0.1;

            if($direction === Vector3::SIDE_WEST){
                $this->motionX = -$force;

                return true;
            }

            if($direction === Vector3::SIDE_EAST){
                $this->motionX = $force;

                return true;
            }

            if($direction === Vector3::SIDE_DOWN){
                $this->motionY = -$force;

                return true;
            }

            if($direction === Vector3::SIDE_UP){
                $this->motionY = $force;

                return true;
            }

            if($direction === Vector3::SIDE_NORTH){
                $this->motionZ = -$force;

                return true;
            }

            if($direction === Vector3::SIDE_SOUTH){
                $this->motionZ = $force;

                return true;
            }
        }

        return false;
    }

    public function getDirection() : ?int{
        $rotation = fmod($this->yaw - 90, 360);
        if($rotation < 0){
            $rotation += 360.0;
        }
        if((0 <= $rotation and $rotation < 45) or (315 <= $rotation and $rotation < 360)){
            return 2; //North
        }elseif(45 <= $rotation and $rotation < 135){
            return 3; //East
        }elseif(135 <= $rotation and $rotation < 225){
            return 0; //South
        }elseif(225 <= $rotation and $rotation < 315){
            return 1; //West
        }else{
            return null;
        }
    }

    public function onUpdate(int $currentTick) : bool{
        if($this->closed){
            return false;
        }

        $tickDiff = $currentTick - $this->lastUpdate;
        if($tickDiff <= 0){
            if(!$this->justCreated){
                $this->server->getLogger()->debug("Expected tick difference of at least 1, got $tickDiff for " . get_class($this));
            }

            return true;
        }

        if(!$this->isAlive()){
            if($this->onDeathUpdate($tickDiff)){
                $this->flagForDespawn();
            }

            return true;
        }

        $this->lastUpdate = $currentTick;

        $this->timings->startTiming();

        if($this->hasMovementUpdate()){
            $this->onMovementUpdate();

            $this->forceMovementUpdate = false;
            $this->updateMovement();
        }

        Timings::$timerEntityBaseTick->startTiming();
        $hasUpdate = $this->entityBaseTick($tickDiff);
        Timings::$timerEntityBaseTick->stopTiming();

        $this->timings->stopTiming();

        return ($hasUpdate or $this->hasMovementUpdate());
    }

    protected function onMovementUpdate() : void{
        $this->tryChangeMovement();

        $this->checkMotion();

        if($this->motionX != 0 or $this->motionY != 0 or $this->motionZ != 0){
            $this->move($this->motionX, $this->motionY, $this->motionZ);
        }
    }

    protected function checkMotion() : void{
        if(abs($this->motionX) <= self::MOTION_THRESHOLD){
            $this->motionX = 0;
        }
        if(abs($this->motionY) <= self::MOTION_THRESHOLD){
            $this->motionY = 0;
        }
        if(abs($this->motionZ) <= self::MOTION_THRESHOLD){
            $this->motionZ = 0;
        }
    }

    final public function scheduleUpdate() : void{
        if($this->closed){
            throw new InvalidStateException("Cannot schedule update on garbage entity " . get_class($this));
        }
        $this->level->updateEntities[$this->id] = $this;
    }

    public function onNearbyBlockChange() : void{
        $this->setForceMovementUpdate();
        $this->scheduleUpdate();
    }

    /**
     * Flags the entity as needing a movement update on the next tick. Setting this forces a movement update even if the
     * entity's motion is zero. Used to trigger movement updates when blocks change near entities.
     *
     * @param bool $value
     */
    final public function setForceMovementUpdate(bool $value = true) : void{
        $this->forceMovementUpdate = $value;

        $this->blocksAround = null;
    }

    /**
     * Returns whether the entity needs a movement update on the next tick.
     * @return bool
     */
    public function hasMovementUpdate() : bool{
        return (
            $this->forceMovementUpdate or
            $this->motionX != 0 or
            $this->motionY != 0 or
            $this->motionZ != 0 or
            !$this->onGround
        );
    }

    public function canTriggerWalking() : bool{
        return true;
    }

    public function canBePushed() : bool{
        return false;
    }

    public function resetFallDistance() : void{
        $this->fallDistance = 0.0;
    }

    /**
     * @param float $distanceThisTick
     * @param bool  $onGround
     */
    protected function updateFallState(float $distanceThisTick, bool $onGround) : void{
        if($onGround){
            if($this->fallDistance > 0){
                $block = $this->level->getBlockAt($this->getFloorX(), (int) floor($this->y - 0.2), $this->getFloorZ());
                if($block->isSolid()){
                    $block->onEntityFallenUpon($this, $this->fallDistance);
                }

                $this->fall($this->fallDistance);
                $this->resetFallDistance();
            }
        }elseif($distanceThisTick < $this->fallDistance){
            //we've fallen some distance (distanceThisTick is negative)
            //or we ascended back towards where fall distance was measured from initially (distanceThisTick is positive but less than existing fallDistance)
            $this->fallDistance -= $distanceThisTick;
        }else{
            //we ascended past the apex where fall distance was originally being measured from
            //reset it so it will be measured starting from the new, higher position
            $this->fallDistance = 0;
        }
    }

    /**
     * Called when a falling entity hits the ground.
     */
    public function fall(float $fallDistance) : void{

    }

    public function getEyeHeight() : float{
        return $this->eyeHeight;
    }

    public function moveFlying(float $strafe, float $forward, float $friction) : bool{
        $f = $strafe * $strafe + $forward * $forward;
        if($f >= self::MOTION_THRESHOLD){
            $f = sqrt($f);

            if($f < 1) $f = 1;

            $f = $friction / $f;
            $strafe *= $f;
            $forward *= $f;

            $f1 = sin($this->yaw * pi() / 180);
            $f2 = cos($this->yaw * pi() / 180);

            $this->motionX += $strafe * $f2 - $forward * $f1;
            $this->motionZ += $forward * $f2 + $strafe * $f1;

            return true;
        }

        return false;
    }

    public function onCollideWithPlayer(Player $player) : void{

    }

    public function onCollideWithEntity(Entity $entity) : void{
        if($this->canBePushed()){
            $entity->applyEntityCollision($this);
        }
    }

    public function isInsideOfPortal() : bool{
        $blocks = $this->getBlocksAround();

        foreach($blocks as $block){
            if($block instanceof Portal){
                return true;
            }
        }

        return false;
    }

    public function isInsideOfFire() : bool{
        foreach($this->getBlocksAround() as $block){
            if($block instanceof Fire){
                return true;
            }
        }

        return false;
    }

    public function isUnderwater() : bool{
        $block = $this->level->getBlockAt((int) floor($this->x), (int) floor($y = ($this->y + $this->getEyeHeight())), (int) floor($this->z));

        if($block instanceof Water){
            $f = ($block->y + 1) - ($block->getFluidHeightPercent() - 0.1111111);
            return $y < $f;
        }

        return false;
    }

    public function isInsideOfSolid() : bool{
        $block = $this->level->getBlockAt((int) floor($this->x), (int) floor($y = ($this->y + $this->getEyeHeight())), (int) floor($this->z));

        return $block->isSolid() and !$block->isTransparent() and $block->collidesWithBB($this->getBoundingBox());
    }

    public function isInsideOfLava() : bool{
        $block = $this->level->getBlockAt((int) floor($this->x), (int) floor($this->y), (int) floor($this->z));

        return $block instanceof Lava;
    }

    public function fastMove(float $dx, float $dy, float $dz) : bool{
        $this->blocksAround = null;

        if($dx == 0 and $dz == 0 and $dy == 0){
            return true;
        }

        Timings::$entityMoveTimer->startTiming();

        $newBB = $this->boundingBox->getOffsetBoundingBox($dx, $dy, $dz);

        $list = $this->level->getCollisionCubes($this, $newBB, false);

        if(count($list) === 0){
            $this->boundingBox = $newBB;
        }

        $this->x = ($this->boundingBox->minX + $this->boundingBox->maxX) / 2;
        $this->y = $this->boundingBox->minY - $this->ySize;
        $this->z = ($this->boundingBox->minZ + $this->boundingBox->maxZ) / 2;

        $this->checkChunks();

        if(!$this->onGround or $dy != 0){
            $bb = clone $this->boundingBox;
            $bb->minY -= 0.75;
            $this->onGround = false;

            if(count($this->level->getCollisionBlocks($bb)) > 0){
                $this->onGround = true;
            }
        }

        $this->isCollided = $this->onGround;
        $this->updateFallState($dy, $this->onGround);

        Timings::$entityMoveTimer->stopTiming();

        return true;
    }

    public function move(float $dx, float $dy, float $dz) : void{
        $this->blocksAround = null;

        Timings::$entityMoveTimer->startTiming();

        $movX = $dx;
        $movY = $dy;
        $movZ = $dz;

        if ($this->keepMovement) {
            $this->boundingBox->offset($dx, $dy, $dz);
        } else {
            $this->ySize *= self::STEP_CLIP_MULTIPLIER;

            $axisalignedbb = clone $this->boundingBox;

            assert(abs($dx) <= 20 and abs($dy) <= 20 and abs($dz) <= 20, "Movement distance is excessive: dx=$dx, dy=$dy, dz=$dz");

            $list = $this->level->getCollisionCubes($this, $this->level->getTickRate() > 1 ? $axisalignedbb->getOffsetBoundingBox($dx, $dy, $dz) : $axisalignedbb->addCoord($dx, $dy, $dz), false);

            foreach ($list as $bb) {
                $dy = $bb->calculateYOffset($axisalignedbb, $dy);
            }

            $axisalignedbb->offset(0, $dy, 0);

            $fallingFlag = ($this->onGround or ($dy != $movY and $movY < 0));

            foreach ($list as $bb) {
                $dx = $bb->calculateXOffset($axisalignedbb, $dx);
            }

            $axisalignedbb->offset($dx, 0, 0);

            foreach ($list as $bb) {
                $dz = $bb->calculateZOffset($axisalignedbb, $dz);
            }

            $axisalignedbb->offset(0, 0, $dz);

            if ($this->stepHeight > 0 and $fallingFlag and ($movX != $dx or $movZ != $dz)) {
                $cx = $dx;
                $cy = $dy;
                $cz = $dz;
                $dx = $movX;
                $dy = $this->stepHeight;
                $dz = $movZ;

                $axisalignedbb1 = clone $this->boundingBox;

                $list = $this->level->getCollisionCubes($this, $axisalignedbb1->addCoord($dx, $dy, $dz), false);

                foreach ($list as $bb) {
                    $dy = $bb->calculateYOffset($axisalignedbb1, $dy);
                }

                $axisalignedbb1->offset(0, $dy, 0);

                foreach ($list as $bb) {
                    $dx = $bb->calculateXOffset($axisalignedbb1, $dx);
                }

                $axisalignedbb1->offset($dx, 0, 0);

                foreach ($list as $bb) {
                    $dz = $bb->calculateZOffset($axisalignedbb1, $dz);
                }

                $axisalignedbb1->offset(0, 0, $dz);

                $reverseDY = -$dy;
                foreach ($list as $bb) {
                    $reverseDY = $bb->calculateYOffset($axisalignedbb1, $reverseDY);
                }
                $dy += $reverseDY;
                $axisalignedbb1->offset(0, $reverseDY, 0);

                if (($cx ** 2 + $cz ** 2) >= ($dx ** 2 + $dz ** 2)) {
                    $dx = $cx;
                    $dy = $cy;
                    $dz = $cz;
                    $this->boundingBox->setBB($axisalignedbb1);
                } else {
                    $axisalignedbb = $axisalignedbb1;
                    $this->ySize += $dy;
                }
            }
            $this->boundingBox = $axisalignedbb;
        }

        $this->x = ($this->boundingBox->minX + $this->boundingBox->maxX) / 2;
        $this->y = $this->boundingBox->minY - $this->ySize;
        $this->z = ($this->boundingBox->minZ + $this->boundingBox->maxZ) / 2;

        $this->checkChunks();
        $this->checkBlockCollision();
        $this->checkEntityCollision();
        $this->checkGroundState($movX, $movY, $movZ, $dx, $dy, $dz);
        $this->updateFallState($dy, $this->onGround);

        if ($movX != $dx) {
            $this->motionX = 0;
        }

        if ($movY != $dy) {
            $this->motionY = 0;
        }

        if ($movZ != $dz) {
            $this->motionZ = 0;
        }


        //TODO: vehicle collision events (first we need to spawn them!)

        Timings::$entityMoveTimer->stopTiming();
    }

    protected function checkGroundState(float $movX, float $movY, float $movZ, float $dx, float $dy, float $dz) : void{
        $this->isCollidedVertically = $movY != $dy;
        $this->isCollidedHorizontally = ($movX != $dx or $movZ != $dz);
        $this->isCollided = ($this->isCollidedHorizontally or $this->isCollidedVertically);
        $this->onGround = ($movY != $dy and $movY < 0);
    }

    /**
     * @deprecated WARNING: Despite what its name implies, this function DOES NOT return all the blocks around the entity.
     * Instead, it returns blocks which have reactions for an entity intersecting with them.
     *
     * @return Block[]
     */
    public function getBlocksAround(){
        if($this->blocksAround === null){
            $inset = 0.001; //Offset against floating-point errors
            $bb = $this->boundingBox->shrink($inset, $inset, $inset);

            $minX = (int) floor($bb->minX);
            $minY = (int) floor($bb->minY);
            $minZ = (int) floor($bb->minZ);
            $maxX = (int) floor($bb->maxX);
            $maxY = (int) floor($bb->maxY);
            $maxZ = (int) floor($bb->maxZ);

            $this->blocksAround = [];

            for($z = $minZ; $z <= $maxZ; ++$z){
                for($x = $minX; $x <= $maxX; ++$x){
                    for($y = $minY; $y <= $maxY; ++$y){
                        $block = $this->level->getBlockAt($x, $y, $z);
                        if($block->hasEntityCollision()){
                            $this->blocksAround[] = $block;
                        }
                    }
                }
            }
        }

        return $this->blocksAround;
    }

    /**
     * Returns whether this entity can be moved by currents in liquids.
     *
     * @return bool
     */
    public function canBeMovedByCurrents() : bool{
        return true;
    }

    protected function checkBlockCollision() : void{
        $vector = $this->temporalVector->setComponents(0, 0, 0);

        foreach($this->getBlocksAround() as $block){
            $block->onEntityCollide($this);
            $block->addVelocityToEntity($this, $vector);
        }

        if($this instanceof Living){
            $down = $this->level->getBlockAt($this->getFloorX(), $this->getFloorY() - 1, $this->getFloorZ());
            if($down->hasEntityCollision()){
                $down->onEntityCollideUpon($this);
            }
        }

        if($vector->lengthSquared() > 0){
            $vector = $vector->normalize();
            $d = 0.014;
            $this->motion->x += $vector->x * $d;
            $this->motion->y += $vector->y * $d;
            $this->motion->z += $vector->z * $d;
        }
    }

    protected function checkEntityCollision() : void{
        if($this->canBePushed()){
            foreach($this->level->getCollidingEntities($this->getBoundingBox()->expandedCopy(0.2, 0, 0.2), $this) as $e){
                $this->onCollideWithEntity($e);
            }
        }
    }

    public function getPosition() : Position{
        return $this->asPosition();
    }

    public function getLocation() : Location{
        return $this->asLocation();
    }

    public function setPosition(Vector3 $pos) : bool{
        if($this->closed){
            return false;
        }

        if($pos instanceof Position and $pos->level !== null and $pos->level !== $this->level){
            if($this->switchLevel($pos->getLevel()) === false){
                return false;
            }
        }

        $this->x = $pos->x;
        $this->y = $pos->y;
        $this->z = $pos->z;

        $this->recalculateBoundingBox();

        $this->blocksAround = null;

        $this->checkChunks();

        return true;
    }

    public function setRotation(float $yaw, float $pitch) : void{
        $this->yaw = $yaw;
        $this->pitch = $pitch;
        $this->scheduleUpdate();
    }

    public function setPositionAndRotation(Vector3 $pos, float $yaw, float $pitch) : bool{
        if($this->setPosition($pos)){
            $this->setRotation($yaw, $pitch);

            return true;
        }

        return false;
    }

    protected function checkChunks() : void{
        $chunkX = $this->getFloorX() >> 4;
        $chunkZ = $this->getFloorZ() >> 4;
        if($this->chunk === null or ($this->chunk->getX() !== $chunkX or $this->chunk->getZ() !== $chunkZ)){
            if($this->chunk !== null){
                $this->chunk->removeEntity($this);
            }
            $this->chunk = $this->level->getChunk($chunkX, $chunkZ, true);

            if(!$this->justCreated){
                $newChunk = $this->level->getViewersForPosition($this);
                foreach($this->hasSpawned as $player){
                    if(!isset($newChunk[$player->getLoaderId()])){
                        $this->despawnFrom($player);
                    }else{
                        unset($newChunk[$player->getLoaderId()]);
                    }
                }
                foreach($newChunk as $player){
                    $this->spawnTo($player);
                }
            }

            if($this->chunk === null){
                return;
            }

            $this->chunk->addEntity($this);
        }
    }

    protected function resetLastMovements() : void{
        list($this->lastX, $this->lastY, $this->lastZ) = [$this->x, $this->y, $this->z];
        list($this->lastYaw, $this->lastPitch) = [$this->yaw, $this->pitch];
        list($this->lastMotionX, $this->lastMotionY, $this->lastMotionZ) = [$this->motionX, $this->motionY, $this->motionZ];
    }

    public function getMotion() : Vector3{
        return new Vector3($this->motionX, $this->motionY, $this->motionZ);
    }

    public function setMotion(Vector3 $motion) : bool{
        if(!$this->justCreated){
            $ev = new EntityMotionEvent($this, $motion);
            $ev->call();
            if($ev->isCancelled()){
                return false;
            }
        }

        $this->motionX = $motion->x;
        $this->motionY = $motion->y;
        $this->motionZ = $motion->z;

        if(!$this->justCreated){
            $this->updateMovement();
        }

        return true;
    }

    public function resetMotion() : void{
        $this->motionX = 0;
        $this->motionY = 0;
        $this->motionZ = 0;
    }

    /**
     * Adds the given values to the entity's motion vector.
     */
    public function addMotion(float $x, float $y, float $z) : void{
        $this->motionX += $x;
        $this->motionY += $y;
        $this->motionZ += $z;
    }

    public function isOnGround() : bool{
        return $this->onGround;
    }

    /**
     * @param Vector3|Position|Location $pos
     * @param float|null                $yaw
     * @param float|null                $pitch
     *
     * @return bool
     */
	public function teleport(Vector3 $pos, float $yaw = null, float $pitch = null) : bool{
		if($pos instanceof Location){
			$yaw = $yaw ?? $pos->yaw;
			$pitch = $pitch ?? $pos->pitch;
		}
		$from = Position::fromObject($this, $this->level);
		$to = Position::fromObject($pos, $pos instanceof Position ? $pos->getLevel() : $this->level);
		$ev = new EntityTeleportEvent($this, $from, $to);
		$ev->call();
		if($ev->isCancelled()){
			return false;
		}
		$this->ySize = 0;
		$pos = $ev->getTo();

		$this->setMotion($this->temporalVector->setComponents(0, 0, 0));
        if($this->setPositionAndRotation($pos, $yaw ?? $this->yaw, $pitch ?? $this->pitch)){
            $this->resetFallDistance();
            $this->setForceMovementUpdate();

            $this->updateMovement(true);
			return true;
		}

		return false;
	}

    protected function switchLevel(Level $targetLevel) : bool{
        if($this->closed){
            return false;
        }

        if($this->isValid()){
            $ev = new EntityLevelChangeEvent($this, $this->level, $targetLevel);
            $ev->call();
            if($ev->isCancelled()){
                return false;
            }

            $this->level->removeEntity($this);
            if($this->chunk !== null){
                $this->chunk->removeEntity($this);
            }
            $this->despawnFromAll();
        }

        $this->setLevel($targetLevel);
        $this->level->addEntity($this);
        $this->chunk = null;

        return true;
    }

    public function getId() : int{
        return $this->id;
    }

    /**
     * @return Player[]
     */
    public function getViewers() : array{
        return $this->hasSpawned;
    }

    /**
     * Called by spawnTo() to send whatever packets needed to spawn the entity to the client.
     *
     * @param Player $player
     */
    protected function sendSpawnPacket(Player $player) : void
    {
        if (static::NETWORK_ID !== -1) {
            $pk = new AddEntityPacket();
            $pk->entityRuntimeId = $this->getId();
            $pk->type = static::NETWORK_ID;
            $pk->x = $this->x;
            $pk->y = $this->y;
            $pk->z = $this->z;
            $pk->speedX = $this->motionX;
            $pk->speedY = $this->motionY;
            $pk->speedZ = $this->motionZ;
            $pk->yaw = $this->yaw;
            $pk->pitch = $this->pitch;
            $pk->attributes = $this->attributeMap->getAll();
            $pk->metadata = $this->propertyManager->getAll();

            $player->sendDataPacket($pk);
        }
    }

    /**
     * @param Player $player
     */
    public function spawnTo(Player $player) : void{
        if(
            !isset($this->hasSpawned[$player->getLoaderId()]) and
            $this->chunk !== null and
            isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())]) and
            $player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())] === true
        ){
            $this->hasSpawned[$player->getLoaderId()] = $player;

            $this->sendSpawnPacket($player);
        }
    }

    public function spawnToAll() : void{
        if($this->chunk === null or $this->closed){
            return;
        }
        foreach($this->level->getViewersForPosition($this) as $player){
            if($player->isOnline()){
                $this->spawnTo($player);
            }
        }
    }

    public function respawnToAll() : void{
        foreach($this->hasSpawned as $key => $player){
            unset($this->hasSpawned[$key]);
            $this->spawnTo($player);
        }
    }

    /**
     * @deprecated WARNING: This function DOES NOT permanently hide the entity from the player. As soon as the entity or
     * player moves, the player will once again be able to see the entity.
     *
     * @param Player $player
     * @param bool   $send
     */
    public function despawnFrom(Player $player, bool $send = true) : void{
        if(isset($this->hasSpawned[$player->getLoaderId()])){
            if($send){
                $pk = new RemoveEntityPacket();
                $pk->entityUniqueId = $this->id;
                $player->sendDataPacket($pk);
            }
            unset($this->hasSpawned[$player->getLoaderId()]);
        }
    }

    /**
     * @deprecated WARNING: This function DOES NOT permanently hide the entity from viewers. As soon as the entity or
     * player moves, viewers will once again be able to see the entity.
     */
    public function despawnFromAll() : void{
        foreach($this->hasSpawned as $player){
            $this->despawnFrom($player);
        }
    }

    /**
     * Flags the entity to be removed from the world on the next tick.
     */
    public function flagForDespawn() : void{
        $this->needsDespawn = true;
        $this->scheduleUpdate();
    }

    public function isFlaggedForDespawn() : bool{
        return $this->needsDespawn;
    }

    /**
     * Returns whether the entity has been "closed".
     * @return bool
     */
    public function isClosed() : bool{
        return $this->closed;
    }

    /**
     * Closes the entity and frees attached references.
     *
     * WARNING: Entities are unusable after this has been executed!
     */
	public function close() : void{
        if($this->closeInFlight){
            return;
        }

		if(!$this->closed){
            $this->closeInFlight = true;
			(new EntityDespawnEvent($this))->call();
			$this->closed = true;

			$this->despawnFromAll();
			$this->hasSpawned = [];

            if($this->chunk !== null){
                $this->chunk->removeEntity($this);
                $this->chunk = null;
            }

            if($this->getLevel() !== null){
                $this->getLevel()->removeEntity($this);
                $this->setLevel(null);
            }

            $this->namedtag = null;
            $this->lastDamageCause = null;
            $this->closeInFlight = false;
		}
	}

    public function linkEntity(Entity $entity): bool{
        return $this->setLinked(1, $entity);
    }

    public function sendLinkedData(): void{
        if($this->linkedEntity instanceof Entity){
            $this->setLinked($this->linkedType, $this->linkedEntity);
        }
    }

    public function setLinked(int $type, Entity $entity): bool{
        if($entity instanceof Rideable){ //TODO: Boat
            $position = $entity->getSeatPosition();
            $this->propertyManager->setVector3(self::DATA_RIDER_SEAT_POSITION, $position);
        }

        if($entity === $this){
            return false;
        }
        switch($type){
            case 0:
                if($this->linkedType == 0){
                    return true;
                }
                $this->linkedType = 0;
                $pk = new SetEntityLinkPacket();
                $pk->fromEntityUniqueId = $entity->getId();
                $pk->toEntityUniqueId = $this->getId();
                $pk->type = 3;
                $this->server->broadcastPacket($this->level->getPlayers(), $pk);
                if($this instanceof Player){
                    $pk = new SetEntityLinkPacket();
                    $pk->fromEntityUniqueId = $entity->getId();
                    $pk->toEntityUniqueId = 0;
                    $pk->type = 3;
                    $this->sendDataPacket($pk);
                }
                if($this->linkedEntity->getLinkedType()){
                    $this->linkedEntity->setLinked(0, $this);
                }
                $this->linkedEntity = null;

                return true;
            case 1:
                if(!$entity->isAlive()){
                    return false;
                }
                $this->linkedEntity = $entity;
                $this->linkedType = 1;
                $entity->linkedEntity = $this;
                $entity->linkedType = 1;
                $pk = new SetEntityLinkPacket();
                $pk->fromEntityUniqueId = $entity->getId();
                $pk->toEntityUniqueId = $this->getId();
                $pk->type = 2;
                $this->server->broadcastPacket($this->level->getPlayers(), $pk);
                if($this instanceof Player){
                    $pk = new SetEntityLinkPacket();
                    $pk->fromEntityUniqueId = $entity->getId();
                    $pk->toEntityUniqueId = 0;
                    $pk->type = 2;
                    $this->sendDataPacket($pk);
                }
                return true;
            case 2:
                if(!$entity->isAlive()){
                    return false;
                }
                if($entity->getLinkedEntity() !== $this){
                    return $entity->linkEntity($this);
                }
                $this->linkedEntity = $entity;
                $this->linkedType = 2;
                return true;
            default:
                return false;
        }
    }

    public function getLinkedEntity(): Entity{
        return $this->linkedEntity;
    }

    public function getLinkedType(){
        return $this->linkedType;
    }

    public function getInteractButtonText(Player $player): ?string{
        return null;
    }

	public function __destruct(){
		$this->close();
	}

	public function setMetadata(string $metadataKey, MetadataValue $newMetadataValue){
		$this->server->getEntityMetadata()->setMetadata($this, $metadataKey, $newMetadataValue);
	}

	public function getMetadata(string $metadataKey){
		return $this->server->getEntityMetadata()->getMetadata($this, $metadataKey);
	}

	public function hasMetadata(string $metadataKey) : bool{
		return $this->server->getEntityMetadata()->hasMetadata($this, $metadataKey);
	}

	public function removeMetadata(string $metadataKey, Plugin $owningPlugin){
		$this->server->getEntityMetadata()->removeMetadata($this, $metadataKey, $owningPlugin);
	}

    public function __toString(){
        return (new ReflectionClass($this))->getShortName() . "(" . $this->getId() . ")";
    }

    /**
     * TODO: remove this BC hack in 4.0
     *
     * @param string $name
     *
     * @return mixed
     * @throws ErrorException
     */
    public function __get(string $name){
        if($name === "fireTicks"){
            return $this->fireTicks;
        }
        throw new ErrorException("Undefined property: " . get_class($this) . "::\$" . $name);
    }

    /**
     * TODO: remove this BC hack in 4.0
     *
     * @param string $name
     * @param mixed $value
     *
     * @throws ErrorException
     * @throws InvalidArgumentException
     */
    public function __set(string $name, $value){
        if($name === "fireTicks"){
            $this->setFireTicks($value);
        }else{
            throw new ErrorException("Undefined property: " . get_class($this) . "::\$" . $name);
        }
    }

    /**
     * TODO: remove this BC hack in 4.0
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset(string $name){
        return $name === "fireTicks";
    }
}


