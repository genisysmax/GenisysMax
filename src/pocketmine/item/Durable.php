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

use pocketmine\item\enchantment\Enchantment;
use pocketmine\nbt\tag\ByteTag;

abstract class Durable extends Item{

	public function isUnbreakable(){
		$tag = $this->getNamedTag();
		return $tag->hasTag("Unbreakable", ByteTag::class) and $tag->getByte("Unbreakable") > 0;
	}

	/**
	 * @param bool $unbreakable
	 *
	 * @return $this
	 */
	public function setUnbreakable(bool $unbreakable){
		$this->getNamedTag()->setInt("Unbreakable", $unbreakable ? 1 : 0);
		return $this;
	}

	/**
	 * Applies damage to the item.
	 * @param int $amount
	 *
	 * @return bool if any damage was applied to the item
	 */
	public function applyDamage(int $amount) : bool{
		if($this->isUnbreakable() or $this->isBroken()){
			return false;
		}

		$amount -= $this->getUnbreakingDamageReduction($amount);

		$this->meta = min($this->meta + $amount, $this->getMaxDurability());
		if($this->isBroken()){
			$this->onBroken();
		}

		return true;
	}

	protected function getUnbreakingDamageReduction(int $amount) : int{
		if(($unbreaking = $this->getEnchantment(Enchantment::UNBREAKING)) !== null){
			$negated = 0;

			$chance = 1 / ($unbreaking->getLevel() + 1);
			for($i = 0; $i < $amount; ++$i){
				if(lcg_value() > $chance){
					$negated++;
				}
			}

			return $negated;
		}

		return 0;
	}

	/**
	 * Called when the item's damage exceeds its maximum durability.
	 */
	protected function onBroken() : void{
		$this->pop();
	}

	/**
	 * Returns whether the item is broken.
	 * @return bool
	 */
	public function isBroken() : bool{
		return $this->meta >= $this->getMaxDurability();
	}
}

