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

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\ProtectionEnchantment;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use pocketmine\utils\Color;

abstract class Armor extends Durable{

    public const string TAG_CUSTOM_COLOR = "customColor"; //TAG_Int

	public function getMaxStackSize() : int{
		return 1;
	}

    abstract public function getArmorSlot() : int;

	public function setCustomColor(Color $color) : self{
		$this->getNamedTag()->setInt(self::TAG_CUSTOM_COLOR, $color->toRGB());
		return $this;
	}

	public function getCustomColor() : ?Color{
		$tag = $this->getNamedTag();
		if($tag->hasTag(self::TAG_CUSTOM_COLOR, IntTag::class)){
			return Color::fromRGB($tag->getInt(self::TAG_CUSTOM_COLOR));
		}
		return null;
	}

	public function clearCustomColor() : void{
		$this->getNamedTag()->removeTag(self::TAG_CUSTOM_COLOR);
	}

    /**
     * Returns the total enchantment protection factor this armour piece offers from all applicable protection
     * enchantments on the item.
     */
    public function getEnchantmentProtectionFactor(EntityDamageEvent $event) : int{
        $epf = 0;

        foreach($this->getEnchantments() as $enchantment){
            $type = $enchantment->getType();
            if($type instanceof ProtectionEnchantment and $type->isApplicable($event)){
                $epf += $type->getProtectionFactor($enchantment->getLevel());
            }
        }

        return $epf;
    }

    protected function getUnbreakingDamageReduction(int $amount) : int{
        if(($unbreakingLevel = $this->getEnchantmentLevel(Enchantment::UNBREAKING)) > 0){
            $negated = 0;

            $chance = 1 / ($unbreakingLevel + 1);
            for($i = 0; $i < $amount; ++$i){
                if(mt_rand(1, 100) > 60 and lcg_value() > $chance){ //unbreaking only applies to armor 40% of the time at best
                    $negated++;
                }
            }

            return $negated;
        }

        return 0;
    }

    public function onClickAir(Player $player, Vector3 $directionVector) : bool{
        $current = $player->getArmorInventory()->getItem($this->getArmorSlot());
        if($current->isNull()){
            $player->getArmorInventory()->setItem($this->getArmorSlot(), $this->pop());

            return true;
        }elseif(!$current->equals($this) and $player->getInventory()->canAddItem($current)){
            $player->getArmorInventory()->setItem($this->getArmorSlot(), $this->pop());
            $player->getInventory()->addItem($current);

            return true;
        }

        return false;
    }
}

