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
 * All the Tile classes and related classes
 */
namespace pocketmine\tile;

use BadMethodCallException;
use InvalidStateException;
use pocketmine\block\Block;
use pocketmine\event\Timings;
use pocketmine\event\TimingsHandler;
use pocketmine\item\Item;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\Server;
use ReflectionClass;
use ReflectionException;
use function assert;
use function is_a;

abstract class Tile extends Position
{

    public const TAG_ID = "id";
    public const TAG_X = "x";
    public const TAG_Y = "y";
    public const TAG_Z = "z";

    public const BREWING_STAND = "BrewingStand";
    public const CHEST = "Chest";
    public const DL_DETECTOR = "DayLightDetector";
    public const ENCHANT_TABLE = "EnchantTable";
    public const FLOWER_POT = "FlowerPot";
    public const FURNACE = "Furnace";
    public const MOB_SPAWNER = "MobSpawner";
    public const SIGN = "Sign";
    public const SKULL = "Skull";
    public const ITEM_FRAME = "ItemFrame";
    public const DISPENSER = "Dispenser";
    public const DROPPER = "Dropper";
    public const CAULDRON = "Cauldron";
    public const HOPPER = "Hopper";
    public const BEACON = "Beacon";
    public const ENDER_CHEST = "EnderChest";
    public const BED = "Bed";
    public const DAY_LIGHT_DETECTOR = "DLDetector";
    public const SHULKER_BOX = "ShulkerBox";
    public const PISTON_ARM = "PistonArm";
    public const NOTE_BLOCK = "NoteBlock";

    public static int $tileCount = 1;

    /**
     * @var string[] classes that extend Tile
     * @phpstan-var array<string, class-string<Tile>>
     */
    private static array $knownTiles = [];
    /**
     * @var string[]
     * @phpstan-var array<class-string<Tile>, string>
     */
    private static array $saveNames = [];

    /** @var null|Chunk */
    public ?Chunk $chunk = null;
    public string $name;
    public int $id = -1;
    public bool $closed = false;
    protected Server $server;
    protected TimingsHandler $timings;

    public static function init(): void
    {
        self::registerTile(Beacon::class, [self::BEACON, "minecraft:beacon"]);
        self::registerTile(Bed::class, [self::BED, "minecraft:bed"]);
        self::registerTile(BrewingStand::class, [self::BREWING_STAND, "minecraft:brewing_stand"]);
        self::registerTile(Chest::class, [self::CHEST, "minecraft:chest"]);
        self::registerTile(EnchantTable::class, [self::ENCHANT_TABLE, "minecraft:enchanting_table"]);
        self::registerTile(EnderChest::class, [self::ENDER_CHEST, "minecraft:ender_chest"]);
        self::registerTile(FlowerPot::class, [self::FLOWER_POT, "minecraft:flower_pot"]);
        self::registerTile(Furnace::class, [self::FURNACE, "minecraft:furnace"]);
        self::registerTile(Hopper::class, [self::HOPPER, "minecraft:hopper"]);
        self::registerTile(ItemFrame::class, [self::ITEM_FRAME]); //this is an entity in PC
        self::registerTile(Sign::class, [self::SIGN, "minecraft:sign"]);
        self::registerTile(ShulkerBox::class, [self::SHULKER_BOX, "minecraft:shulker_box"]);
        self::registerTile(Skull::class, [self::SKULL, "minecraft:skull"]);
        self::registerTile(NoteBlock::class, [self::NOTE_BLOCK, "minecraft:noteblock"]);
    }

    /**
     * @param string $type
     * @param mixed ...$args
     */
    public static function createTile($type, Level $level, CompoundTag $nbt, ...$args): ?Tile
    {
        if (isset(self::$knownTiles[$type])) {
            $class = self::$knownTiles[$type];
            /** @see Tile::__construct() */
            return new $class($level, $nbt, ...$args);
        }

        return null;
    }

    /**
     * @param string[] $saveNames
     *
     * @phpstan-param class-string<Tile> $className
     *
     * @throws ReflectionException
     */
    public static function registerTile(string $className, array $saveNames = []): bool
    {
        $class = new ReflectionClass($className);
        if (is_a($className, Tile::class, true) and !$class->isAbstract()) {
            $shortName = $class->getShortName();
            if (!in_array($shortName, $saveNames, true)) {
                $saveNames[] = $shortName;
            }

            foreach ($saveNames as $name) {
                self::$knownTiles[$name] = $className;
            }

            self::$saveNames[$className] = reset($saveNames);

            return true;
        }

        return false;
    }

    /**
     * Returns the short save name
     */
    public static function getSaveId(): string
    {
        if (!isset(self::$saveNames[static::class])) {
            throw new InvalidStateException("Tile is not registered");
        }

        return self::$saveNames[static::class];
    }

    public function __construct(Level $level, CompoundTag $nbt)
    {
        $this->timings = Timings::getTileEntityTimings($this);

        $this->server = $level->getServer();
        $this->name = "";
        $this->id = Tile::$tileCount++;

        parent::__construct($nbt->getInt(self::TAG_X), $nbt->getInt(self::TAG_Y), $nbt->getInt(self::TAG_Z), $level);

        $this->chunk = $level->getChunk($this->x >> 4, $this->z >> 4, false);
        assert($this->chunk !== null);

        $this->readSaveData($nbt);

        $this->getLevel()->addTile($this);
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Reads additional data from the CompoundTag on tile creation.
     */
    abstract protected function readSaveData(CompoundTag $nbt): void;

    /**
     * Writes additional save data to a CompoundTag, not including generic things like ID and coordinates.
     */
    abstract protected function writeSaveData(CompoundTag $nbt): void;

    public function saveNBT(): CompoundTag
    {
        $nbt = new CompoundTag();
        $nbt->setString(self::TAG_ID, static::getSaveId());
        $nbt->setInt(self::TAG_X, $this->x);
        $nbt->setInt(self::TAG_Y, $this->y);
        $nbt->setInt(self::TAG_Z, $this->z);
        $this->writeSaveData($nbt);

        return $nbt;
    }

    public function getCleanedNBT(): ?CompoundTag
    {
        $this->writeSaveData($tag = new CompoundTag());
        return $tag->getCount() > 0 ? $tag : null;
    }

    /**
     * Creates and returns a CompoundTag containing the necessary information to spawn a tile of this type.
     */
    public static function createNBT(Vector3 $pos, ?int $face = null, ?Item $item = null, ?Player $player = null): CompoundTag
    {
        if (static::class === self::class) {
            throw new BadMethodCallException(__METHOD__ . " must be called from the scope of a child class");
        }
        $nbt = CompoundTag::create()
            ->setString(self::TAG_ID, static::getSaveId())
            ->setInt(self::TAG_X, (int)$pos->x)
            ->setInt(self::TAG_Y, (int)$pos->y)
            ->setInt(self::TAG_Z, (int)$pos->z);

        static::createAdditionalNBT($nbt, $pos, $face, $item, $player);

        if ($item !== null) {
            if ($item->hasCustomBlockData()) {
                foreach ($item->getCustomBlockData() as $key => $v) {
                    $nbt->setTag($key, $v);
                }
            }
        }

        return $nbt;
    }

    /**
     * Called by createNBT() to allow descendent classes to add their own base NBT using the parameters provided.
     */
    protected static function createAdditionalNBT(CompoundTag $nbt, Vector3 $pos, ?int $face = null, ?Item $item = null, ?Player $player = null): void
    {

    }

    /**
     * @return Block
     */
    public function getBlock(): Block
    {
        return $this->level->getBlockAt($this->x, $this->y, $this->z);
    }

    /**
     * @return bool
     */
    public function onUpdate(): bool
    {
        return false;
    }

    final public function scheduleUpdate(): void
    {
        if ($this->closed) {
            throw new InvalidStateException("Cannot schedule update on garbage tile " . get_class($this));
        }
        $this->level->updateTiles[$this->id] = $this;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function close(): void
    {
        if (!$this->closed) {
            $this->closed = true;
            unset($this->level->updateTiles[$this->id]);
            if ($this->isValid()) {
                $this->level->removeTile($this);
                $this->setLevel();
            }

            $this->chunk = null;
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

}


