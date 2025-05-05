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

namespace pocketmine\item;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDrinkPotionEvent;
use pocketmine\Player;
use function assert;

class Potion extends Item{

	public const WATER_BOTTLE = 0;
	public const MUNDANE = 1;
	public const MUNDANE_EXTENDED = 2;
	public const THICK = 3;
	public const AWKWARD = 4;
	public const NIGHT_VISION = 5;
	public const NIGHT_VISION_T = 6;
	public const INVISIBILITY = 7;
	public const INVISIBILITY_T = 8;
	public const LEAPING = 9;
	public const LEAPING_T = 10;
	public const LEAPING_TWO = 11;
	public const FIRE_RESISTANCE = 12;
	public const FIRE_RESISTANCE_T = 13;
	public const SPEED = 14;
	public const SPEED_T = 15;
	public const SPEED_TWO = 16;
	public const SLOWNESS = 17;
	public const SLOWNESS_T = 18;
	public const WATER_BREATHING = 19;
	public const WATER_BREATHING_T = 20;
	public const HEALING = 21;
	public const HEALING_TWO = 22;
	public const HARMING = 23;
	public const HARMING_TWO = 24;
	public const POISON = 25;
	public const POISON_T = 26;
	public const POISON_TWO = 27;
	public const REGENERATION = 28;
	public const REGENERATION_T = 29;
	public const REGENERATION_TWO = 30;
	public const STRENGTH = 31;
	public const STRENGTH_T = 32;
	public const STRENGTH_TWO = 33;
	public const WEAKNESS = 34;
	public const WEAKNESS_T = 35;

    //Structure: Potion ID => [matching effect, duration in ticks, amplifier]
    //Use false if no effects.
    const POTIONS = [
        self::WATER_BOTTLE => false,
        self::MUNDANE => false,
        self::MUNDANE_EXTENDED => false,
        self::THICK => false,
        self::AWKWARD => false,

        self::NIGHT_VISION => [Effect::NIGHT_VISION, (180 * 20), 0],
        self::NIGHT_VISION_T => [Effect::NIGHT_VISION, (480 * 20), 0],

        self::INVISIBILITY => [Effect::INVISIBILITY, (180 * 20), 0],
        self::INVISIBILITY_T => [Effect::INVISIBILITY, (480 * 20), 0],

        self::LEAPING => [Effect::JUMP, (180 * 20), 0],
        self::LEAPING_T => [Effect::JUMP, (480 * 20), 0],
        self::LEAPING_TWO => [Effect::JUMP, (90 * 20), 1],

        self::FIRE_RESISTANCE => [Effect::FIRE_RESISTANCE, (180 * 20), 0],
        self::FIRE_RESISTANCE_T => [Effect::FIRE_RESISTANCE, (480 * 20), 0],

        self::SPEED => [Effect::SPEED, (180 * 20), 0],
        self::SPEED_T => [Effect::SPEED, (480 * 20), 0],
        self::SPEED_TWO => [Effect::SPEED, (90 * 20), 1],

        self::SLOWNESS => [Effect::SLOWNESS, (90 * 20), 0],
        self::SLOWNESS_T => [Effect::SLOWNESS, (240 * 20), 0],

        self::WATER_BREATHING => [Effect::WATER_BREATHING, (180 * 20), 0],
        self::WATER_BREATHING_T => [Effect::WATER_BREATHING, (480 * 20), 0],

        self::HEALING => [Effect::HEALING, (1), 0],
        self::HEALING_TWO => [Effect::HEALING, (1), 1],

        self::HARMING => [],
        self::HARMING_TWO => [],

        self::POISON => [Effect::POISON, (45 * 20), 0],
        self::POISON_T => [Effect::POISON, (120 * 20), 0],
        self::POISON_TWO => [Effect::POISON, (22 * 20), 1],

        self::REGENERATION => [Effect::REGENERATION, (45 * 20), 0],
        self::REGENERATION_T => [Effect::REGENERATION, (120 * 20), 0],
        self::REGENERATION_TWO => [Effect::REGENERATION, (22 * 20), 1],

        self::STRENGTH => [Effect::STRENGTH, (180 * 20), 0],
        self::STRENGTH_T => [Effect::STRENGTH, (480 * 20), 0],
        self::STRENGTH_TWO => [Effect::STRENGTH, (90 * 20), 1],

        self::WEAKNESS => [Effect::WEAKNESS, (90 * 20), 0],
        self::WEAKNESS_T => [Effect::WEAKNESS, (240 * 20), 0]
    ];

	public function __construct($meta = 0, $count = 1){
		parent::__construct(self::POTION, $meta, $count, self::getNameByMeta($meta));
	}

    /**
     * @return int
     */
    public function getMaxStackSize() : int{
        return 1;
    }

    public function getMaxDurability(): int
    {
        return 43;
    }

    /**
     * @return bool
     */
    public function canBeConsumed() : bool{
        return $this->meta > 0;
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    public function canBeConsumedBy(Entity $entity) : bool{
        return $entity instanceof Human;
    }

    /**
     * @return array
     */
    public function getEffects() : array{
        return self::getEffectsById($this->meta);
    }

    /**
     * @param int $id
     *
     * @return EffectInstance[]
     */
    public static function getEffectsById(int $id) : array{
        if($id >= 5){ // 0 to 4 without arguments
            if(count(self::POTIONS[$id] ?? []) === 3){
                return [new EffectInstance(Effect::getEffect(self::POTIONS[$id][0]), self::POTIONS[$id][1], self::POTIONS[$id][2])];
            }
        }
        return [];
    }

    public function onConsume(Entity $entity): void{
		assert($entity instanceof Human);
        $ev = new EntityDrinkPotionEvent($entity, $this);
        if(!$ev->isCancelled()){
            foreach($ev->getEffects() as $effect){
                $entity->addEffect($effect);
            }
            //Don't set the held item to glass bottle if we're in creative
            if($entity instanceof Player){
                if($entity->getGamemode() === 1){
                    return;
                }
            }
            $entity->getInventory()->setItemInHand(Item::get(self::GLASS_BOTTLE));
        }
    }

	public static function getNameByMeta(int $meta) : string{
        return match ($meta) {
            self::WATER_BOTTLE => "Water Bottle",
            self::MUNDANE, self::MUNDANE_EXTENDED => "Mundane Potion",
            self::THICK => "Thick Potion",
            self::AWKWARD => "Awkward Potion",
            self::INVISIBILITY, self::INVISIBILITY_T => "Potion of Invisibility",
            self::LEAPING, self::LEAPING_T => "Potion of Leaping",
            self::LEAPING_TWO => "Potion of Leaping II",
            self::FIRE_RESISTANCE, self::FIRE_RESISTANCE_T => "Potion of Fire Residence",
            self::SPEED, self::SPEED_T => "Potion of Speed",
            self::SPEED_TWO => "Potion of Speed II",
            self::SLOWNESS, self::SLOWNESS_T => "Potion of Slowness",
            self::WATER_BREATHING, self::WATER_BREATHING_T => "Potion of Water Breathing",
            self::HARMING => "Potion of Harming",
            self::HARMING_TWO => "Potion of Harming II",
            self::POISON, self::POISON_T => "Potion of Poison",
            self::POISON_TWO => "Potion of Poison II",
            self::HEALING => "Potion of Healing",
            self::HEALING_TWO => "Potion of Healing II",
            default => "Potion",
        };
	}

	public static function getEffectByMeta(int $meta) : ?EffectInstance{
		$effect = null;
		switch($meta){
			case Potion::NIGHT_VISION:
				$effect = new EffectInstance(Effect::getEffect(Effect::NIGHT_VISION), 3 * 60 * 20, 0);
				break;
			case Potion::NIGHT_VISION_T:
                $effect = new EffectInstance(Effect::getEffect(Effect::NIGHT_VISION), 8 * 60 * 20, 0);
				break;
			case Potion::INVISIBILITY:
                $effect = new EffectInstance(Effect::getEffect(Effect::INVISIBILITY), 3 * 60 * 20, 0);
				break;
			case Potion::INVISIBILITY_T:
                $effect = new EffectInstance(Effect::getEffect(Effect::INVISIBILITY), 8 * 60 * 20, 0);
				break;
			case Potion::LEAPING:
                $effect = new EffectInstance(Effect::getEffect(Effect::JUMP), 3 * 60 * 20, 0);
				break;
			case Potion::LEAPING_T:
                $effect = new EffectInstance(Effect::getEffect(Effect::JUMP), 8 * 60 * 20, 0);
				break;
			case Potion::LEAPING_TWO:
                $effect = new EffectInstance(Effect::getEffect(Effect::JUMP), (int) (1.5 * 60 * 20), 1);
				break;
			case Potion::FIRE_RESISTANCE:
                $effect = new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 3 * 60 * 20, 0);
				break;
			case Potion::FIRE_RESISTANCE_T:
                $effect = new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 8 * 60 * 20, 0);
				break;
			case Potion::SPEED:
                $effect = new EffectInstance(Effect::getEffect(Effect::SPEED), 3 * 60 * 20, 0);
				break;
			case Potion::SPEED_T:
                $effect = new EffectInstance(Effect::getEffect(Effect::SPEED), 8 * 60 * 20, 0);
				break;
			case Potion::SPEED_TWO:
                $effect = new EffectInstance(Effect::getEffect(Effect::SPEED), (int) (1.5 * 60 * 20), 1);
				break;
			case Potion::SLOWNESS:
                $effect = new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 60 * 20, 0);
				break;
			case Potion::SLOWNESS_T:
                $effect = new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 4 * 60 * 20, 0);
				break;
			case Potion::WATER_BREATHING:
                $effect = new EffectInstance(Effect::getEffect(Effect::WATER_BREATHING), 3 * 60 * 20, 0);
				break;
			case Potion::WATER_BREATHING_T:
                $effect = new EffectInstance(Effect::getEffect(Effect::WATER_BREATHING), 8 * 60 * 20, 0);
				break;
			case Potion::POISON:
                $effect = new EffectInstance(Effect::getEffect(Effect::POISON), 45 * 20, 0);
				break;
			case Potion::POISON_T:
                $effect = new EffectInstance(Effect::getEffect(Effect::POISON), 2 * 60 * 20, 0);
				break;
			case Potion::POISON_TWO:
                $effect = new EffectInstance(Effect::getEffect(Effect::POISON), 22 * 20, 0);
				break;
			case Potion::REGENERATION:
                $effect = new EffectInstance(Effect::getEffect(Effect::REGENERATION), 45 * 20, 0);
				break;
			case Potion::REGENERATION_T:
                $effect = new EffectInstance(Effect::getEffect(Effect::REGENERATION), 2 * 60 * 20, 0);
				break;
			case Potion::REGENERATION_TWO:
                $effect = new EffectInstance(Effect::getEffect(Effect::REGENERATION), 22 * 20, 1);
				break;
			case Potion::STRENGTH:
                $effect = new EffectInstance(Effect::getEffect(Effect::STRENGTH), 3 * 60 * 20, 0);
				break;
			case Potion::STRENGTH_T:
                $effect = new EffectInstance(Effect::getEffect(Effect::STRENGTH), 8 * 60 * 20, 0);
				break;
			case Potion::STRENGTH_TWO:
                $effect = new EffectInstance(Effect::getEffect(Effect::STRENGTH), (int) (1.5 * 60 * 20), 1);
				break;
			case Potion::WEAKNESS:
                $effect = new EffectInstance(Effect::getEffect(Effect::WEAKNESS), (int) (1.5 * 60 * 20), 0);
				break;
			case Potion::WEAKNESS_T:
                $effect = new EffectInstance(Effect::getEffect(Effect::WEAKNESS), 4 * 60 * 20, 0);
				break;
			case Potion::HEALING:
                $effect = new EffectInstance(Effect::getEffect(Effect::HEALING), 1, 0);
				break;
			case Potion::HEALING_TWO:
                $effect = new EffectInstance(Effect::getEffect(Effect::HEALING), 1, 1);
				break;
			case Potion::HARMING:
                $effect = new EffectInstance(Effect::getEffect(Effect::HARMING), 1, 0);
				break;
			case Potion::HARMING_TWO:
                $effect = new EffectInstance(Effect::getEffect(Effect::HARMING), 1, 1);
				break;
		}
		return $effect;
	}

}

