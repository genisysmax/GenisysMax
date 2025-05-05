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

namespace pocketmine\item\enchantment;


use pocketmine\event\entity\EntityDamageEvent;
use SplFixedArray;
use function constant;
use function defined;
use function strtoupper;

class Enchantment{

    public const int TYPE_INVALID = -1;

	public const int PROTECTION = 0;
	public const int FIRE_PROTECTION = 1;
	public const int FEATHER_FALLING = 2;
	public const int BLAST_PROTECTION = 3;
	public const int PROJECTILE_PROTECTION = 4;
	public const int THORNS = 5;
	public const int RESPIRATION = 6;
	public const int DEPTH_STRIDER = 7;
	public const int AQUA_AFFINITY = 8;
	public const int SHARPNESS = 9;
	public const int SMITE = 10;
	public const int BANE_OF_ARTHROPODS = 11;
	public const int KNOCKBACK = 12;
	public const int FIRE_ASPECT = 13;
	public const int LOOTING = 14;
	public const int EFFICIENCY = 15;
	public const int SILK_TOUCH = 16;
	public const int UNBREAKING = 17;
	public const int FORTUNE = 18;
	public const int POWER = 19;
	public const int PUNCH = 20;
	public const int FLAME = 21;
	public const int INFINITY = 22;
	public const int LUCK_OF_THE_SEA = 23;
	public const int LURE = 24;
	public const int FROST_WALKER = 25;
	public const int MENDING = 26;
    public const int BINDING = 27;
    public const int VANISHING = 28;
    public const int IMPALING = 29;
    public const int RIPTIDE = 30;
    public const int LOYALTY = 31;
    public const int CHANNELING = 32;
    public const int MULTISHOT = 33;
    public const int PIERCING = 34;
    public const int QUICK_CHARGE = 35;
    public const int SOUL_SPEED = 36;

	public const int RARITY_COMMON = 0;
	public const int RARITY_UNCOMMON = 1;
	public const int RARITY_RARE = 2;
	public const int RARITY_MYTHIC = 3;

    public const int SLOT_NONE = 0x0;
    public const int SLOT_ALL = 0xffff;
    public const int SLOT_ARMOR = self::SLOT_HEAD | self::SLOT_TORSO | self::SLOT_LEGS | self::SLOT_FEET;
    public const int SLOT_HEAD = 0x1;
    public const int SLOT_TORSO = 0x2;
    public const int SLOT_LEGS = 0x4;
    public const int SLOT_FEET = 0x8;
    public const int SLOT_SWORD = 0x10;
    public const int SLOT_BOW = 0x20;
    public const int SLOT_TOOL = self::SLOT_HOE | self::SLOT_SHEARS | self::SLOT_FLINT_AND_STEEL;
    public const int SLOT_HOE = 0x40;
    public const int SLOT_SHEARS = 0x80;
    public const int SLOT_FLINT_AND_STEEL = 0x100;
    public const int SLOT_DIG = self::SLOT_AXE | self::SLOT_PICKAXE | self::SLOT_SHOVEL;
    public const int SLOT_AXE = 0x200;
    public const int SLOT_PICKAXE = 0x400;
    public const int SLOT_SHOVEL = 0x800;
    public const int SLOT_FISHING_ROD = 0x1000;
    public const int SLOT_CARROT_STICK = 0x2000;
    public const int SLOT_ELYTRA = 0x4000;
    public const int SLOT_TRIDENT = 0x8000;

    /** @var SplFixedArray|Enchantment[] */
    protected static $enchantments;

	public static function init() : void{
        self::$enchantments = new SplFixedArray(256);

        self::registerEnchantment(new ProtectionEnchantment(self::PROTECTION, "%enchantment.protect.all", self::RARITY_COMMON, self::SLOT_ARMOR, self::SLOT_NONE, 4, 0.75, null));
        self::registerEnchantment(new ProtectionEnchantment(self::FIRE_PROTECTION, "%enchantment.protect.fire", self::RARITY_UNCOMMON, self::SLOT_ARMOR, self::SLOT_NONE, 4, 1.25, [
            EntityDamageEvent::CAUSE_FIRE,
            EntityDamageEvent::CAUSE_FIRE_TICK,
            EntityDamageEvent::CAUSE_LAVA
            //TODO: check fireballs
        ]));
        self::registerEnchantment(new ProtectionEnchantment(self::FEATHER_FALLING, "%enchantment.protect.fall", self::RARITY_UNCOMMON, self::SLOT_FEET, self::SLOT_NONE, 4, 2.5, [
            EntityDamageEvent::CAUSE_FALL
        ]));
        self::registerEnchantment(new ProtectionEnchantment(self::BLAST_PROTECTION, "%enchantment.protect.explosion", self::RARITY_RARE, self::SLOT_ARMOR, self::SLOT_NONE, 4, 1.5, [
            EntityDamageEvent::CAUSE_BLOCK_EXPLOSION,
            EntityDamageEvent::CAUSE_ENTITY_EXPLOSION
        ]));
        self::registerEnchantment(new ProtectionEnchantment(self::PROJECTILE_PROTECTION, "%enchantment.protect.projectile", self::RARITY_UNCOMMON, self::SLOT_ARMOR, self::SLOT_NONE, 4, 1.5, [
            EntityDamageEvent::CAUSE_PROJECTILE
        ]));
        self::registerEnchantment(new Enchantment(self::THORNS, "%enchantment.thorns", self::RARITY_MYTHIC, self::SLOT_TORSO, self::SLOT_HEAD | self::SLOT_LEGS | self::SLOT_FEET, 3));
        self::registerEnchantment(new Enchantment(self::RESPIRATION, "%enchantment.oxygen", self::RARITY_RARE, self::SLOT_HEAD, self::SLOT_NONE, 3));

        self::registerEnchantment(new SharpnessEnchantment(self::SHARPNESS, "%enchantment.damage.all", self::RARITY_COMMON, self::SLOT_SWORD, self::SLOT_AXE, 5));
        //TODO: smite, bane of arthropods (these don't make sense now because their applicable mobs don't exist yet)

        self::registerEnchantment(new KnockbackEnchantment(self::KNOCKBACK, "%enchantment.knockback", self::RARITY_UNCOMMON, self::SLOT_SWORD, self::SLOT_NONE, 2));
        self::registerEnchantment(new FireAspectEnchantment(self::FIRE_ASPECT, "%enchantment.fire", self::RARITY_RARE, self::SLOT_SWORD, self::SLOT_NONE, 2));

        self::registerEnchantment(new Enchantment(self::EFFICIENCY, "%enchantment.digging", self::RARITY_COMMON, self::SLOT_DIG, self::SLOT_SHEARS, 5));
        self::registerEnchantment(new Enchantment(self::SILK_TOUCH, "%enchantment.untouching", self::RARITY_MYTHIC, self::SLOT_DIG, self::SLOT_SHEARS, 1));
        self::registerEnchantment(new Enchantment(self::UNBREAKING, "%enchantment.durability", self::RARITY_UNCOMMON, self::SLOT_DIG | self::SLOT_ARMOR | self::SLOT_FISHING_ROD | self::SLOT_BOW, self::SLOT_TOOL | self::SLOT_CARROT_STICK | self::SLOT_ELYTRA, 3));

        self::registerEnchantment(new Enchantment(self::POWER, "%enchantment.arrowDamage", self::RARITY_COMMON, self::SLOT_BOW, self::SLOT_NONE, 5));
        self::registerEnchantment(new Enchantment(self::PUNCH, "%enchantment.arrowKnockback", self::RARITY_RARE, self::SLOT_BOW, self::SLOT_NONE, 2));
        self::registerEnchantment(new Enchantment(self::FLAME, "%enchantment.arrowFire", self::RARITY_RARE, self::SLOT_BOW, self::SLOT_NONE, 1));
        self::registerEnchantment(new Enchantment(self::INFINITY, "%enchantment.arrowInfinite", self::RARITY_MYTHIC, self::SLOT_BOW, self::SLOT_NONE, 1));

        self::registerEnchantment(new Enchantment(self::MENDING, "%enchantment.mending", self::RARITY_RARE, self::SLOT_NONE, self::SLOT_ALL, 1));

        self::registerEnchantment(new Enchantment(self::VANISHING, "%enchantment.curse.vanishing", self::RARITY_MYTHIC, self::SLOT_NONE, self::SLOT_ALL, 1));
	}

    /**
     * Registers an enchantment type.
     *
     * @param Enchantment $enchantment
     */
    public static function registerEnchantment(Enchantment $enchantment) : void{
        self::$enchantments[$enchantment->getId()] = clone $enchantment;
    }

    public static function getEnchantment(int $id) : Enchantment{
        if(isset(self::$enchantments[$id])){
            return clone self::$enchantments[$id];
        }
        return new Enchantment(self::TYPE_INVALID, "unknown", 0, 0, 0, 5);
    }

	public static function getEnchantmentByName(string $name) : Enchantment{
		if(defined(Enchantment::class . "::" . strtoupper($name))){
			return self::getEnchantment(constant(Enchantment::class . "::" . strtoupper($name)));
		}
        return new Enchantment(self::TYPE_INVALID, "unknown", 0, 0, 0, 5);
	}

    private int $id;
    private string $name;
    private int $rarity;
    private int $primaryItemFlags;
    private int $secondaryItemFlags;
    private int $maxLevel;

    public function __construct(int $id, string $name, int $rarity, int $primaryItemFlags, int $secondaryItemFlags, int $maxLevel){
        $this->id = $id;
        $this->name = $name;
        $this->rarity = $rarity;
        $this->primaryItemFlags = $primaryItemFlags;
        $this->secondaryItemFlags = $secondaryItemFlags;
        $this->maxLevel = $maxLevel;
    }

    /**
     * Returns the ID of this enchantment as per Minecraft PE
     * @return int
     */
    public function getId() : int{
        return $this->id;
    }

    /**
     * Returns a translation key for this enchantment's name.
     * @return string
     */
    public function getName() : string{
        return $this->name;
    }

    /**
     * Returns an int constant indicating how rare this enchantment type is.
     * @return int
     */
    public function getRarity() : int{
        return $this->rarity;
    }

    /**
     * Returns a bitset indicating what item types can have this item applied from an enchanting table.
     *
     * @return int
     */
    public function getPrimaryItemFlags() : int{
        return $this->primaryItemFlags;
    }

    /**
     * Returns a bitset indicating what item types cannot have this item applied from an enchanting table, but can from
     * an anvil.
     *
     * @return int
     */
    public function getSecondaryItemFlags() : int{
        return $this->secondaryItemFlags;
    }

    /**
     * Returns whether this enchantment can apply to the item type from an enchanting table.
     *
     * @param int $flag
     *
     * @return bool
     */
    public function hasPrimaryItemType(int $flag) : bool{
        return ($this->primaryItemFlags & $flag) !== 0;
    }

    /**
     * Returns whether this enchantment can apply to the item type from an anvil, if it is not a primary item.
     *
     * @param int $flag
     *
     * @return bool
     */
    public function hasSecondaryItemType(int $flag) : bool{
        return ($this->secondaryItemFlags & $flag) !== 0;
    }

    /**
     * Returns the maximum level of this enchantment that can be found on an enchantment table.
     * @return int
     */
    public function getMaxLevel() : int{
        return $this->maxLevel;
    }

    //TODO: methods for min/max XP cost bounds based on enchantment level (not needed yet - enchanting is client-side)
}

