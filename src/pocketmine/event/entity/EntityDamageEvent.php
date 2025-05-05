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

namespace pocketmine\event\entity;

use pocketmine\entity\Entity;
use pocketmine\event\Cancellable;
use function array_sum;

/**
 * Called when an entity takes damage.
 */
class EntityDamageEvent extends EntityEvent implements Cancellable{
	public static $handlerList = null;

	public const MODIFIER_BASE = 0;
	public const MODIFIER_ARMOR = 1;
	public const MODIFIER_STRENGTH = 2;
	public const MODIFIER_WEAKNESS = 3;
	public const MODIFIER_RESISTANCE = 4;
	public const MODIFIER_CRITICAL = 5;
	public const MODIFIER_ENCHANTMENT_PROTECTION = 6;
	public const MODIFIER_ENCHANTMENT_SHARPNESS = 7;
	public const MODIFIER_ENCHANTMENT_POWER = 8;
	public const MODIFIER_ABSORPTION = 9;
	public const MODIFIER_TOTEM = 10;
	public const MODIFIER_ENCHANTMENT_FEATHER_FALLING = 11;
	public const MODIFIER_PREVIOUS_DAMAGE_COOLDOWN = 12;
    public const MODIFIER_ARMOR_ENCHANTMENTS = 13;
    public const MODIFIER_WEAPON_ENCHANTMENTS = 14;

	public const CAUSE_CONTACT = 0;
	public const CAUSE_ENTITY_ATTACK = 1;
	public const CAUSE_PROJECTILE = 2;
	public const CAUSE_SUFFOCATION = 3;
	public const CAUSE_FALL = 4;
	public const CAUSE_FIRE = 5;
	public const CAUSE_FIRE_TICK = 6;
	public const CAUSE_LAVA = 7;
	public const CAUSE_DROWNING = 8;
	public const CAUSE_BLOCK_EXPLOSION = 9;
	public const CAUSE_ENTITY_EXPLOSION = 10;
	public const CAUSE_VOID = 11;
	public const CAUSE_SUICIDE = 12;
	public const CAUSE_MAGIC = 13;
	public const CAUSE_CUSTOM = 14;
	public const CAUSE_STARVATION = 15;
    public const CAUSE_LIGHTNING = 16;
    public const CAUSE_HOT_FLOOR = 17;

    /** @var int */
    private $cause;
    /** @var float */
    private $baseDamage;
    /** @var float */
    private $originalBase;

    /** @var float[] */
    private $modifiers;
    /** @var float[] */
    private $originals;

    /** @var int */
    private $attackCooldown = 10;

	public function __construct(Entity $entity, int $cause, float $damage, array $modifiers = []){
        $this->entity = $entity;
        $this->cause = $cause;
        $this->baseDamage = $this->originalBase = $damage;

        $this->modifiers = $modifiers;
        $this->originals = $this->modifiers;
	}

    public function getCause() : int{
        return $this->cause;
    }

    public function getBaseDamage() : float{
        return $this->baseDamage;
    }

    /**
     * Sets the base amount of damage applied, optionally recalculating modifiers.
     *
     * TODO: add ability to recalculate modifiers when this is set
     *
     * @param float $damage
     */
    public function setBaseDamage(float $damage) : void{
        $this->baseDamage = $damage;
    }

    /**
     * Returns the original base amount of damage applied, before alterations by plugins.
     *
     * @return float
     */
    public function getOriginalBaseDamage() : float{
        return $this->originalBase;
    }

    /**
     * @return float[]
     */
    public function getOriginalModifiers() : array{
        return $this->originals;
    }

    /**
     * @param int $type
     *
     * @return float
     */
    public function getOriginalModifier(int $type) : float{
        return $this->originals[$type] ?? 0.0;
    }

    /**
     * @return float[]
     */
    public function getModifiers() : array{
        return $this->modifiers;
    }

    /**
     * @param int $type
     *
     * @return float
     */
    public function getModifier(int $type) : float{
        return $this->modifiers[$type] ?? 0.0;
    }

    /**
     * @param float $damage
     * @param int   $type
     */
    public function setModifier(float $damage, int $type) : void{
        $this->modifiers[$type] = $damage;
    }

    /**
     * @param int $type
     *
     * @return bool
     */
    public function isApplicable(int $type) : bool{
        return isset($this->modifiers[$type]);
    }

    /**
     * @return float
     */
    public function getFinalDamage() : float{
        return $this->baseDamage + array_sum($this->modifiers);
    }

    /**
     * Returns whether an entity can use armour points to reduce this type of damage.
     * @return bool
     */
    public function canBeReducedByArmor() : bool{
        switch($this->cause){
            case self::CAUSE_FIRE_TICK:
            case self::CAUSE_SUFFOCATION:
            case self::CAUSE_DROWNING:
            case self::CAUSE_STARVATION:
            case self::CAUSE_FALL:
            case self::CAUSE_VOID:
            case self::CAUSE_MAGIC:
            case self::CAUSE_SUICIDE:
                return false;

        }

        return true;
    }

    /**
     * Returns the cooldown in ticks before the target entity can be attacked again.
     *
     * @return int
     */
    public function getAttackCooldown() : int{
        return $this->attackCooldown;
    }

    /**
     * Sets the cooldown in ticks before the target entity can be attacked again.
     *
     * NOTE: This value is not used in non-Living entities
     *
     * @param int $attackCooldown
     */
    public function setAttackCooldown(int $attackCooldown) : void{
        $this->attackCooldown = $attackCooldown;
    }
}

