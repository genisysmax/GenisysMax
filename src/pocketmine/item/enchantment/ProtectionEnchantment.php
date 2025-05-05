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
use function array_flip;
use function floor;

class ProtectionEnchantment extends Enchantment{
	/** @var float */
	protected $typeModifier;
	/** @var int[]|null */
	protected $applicableDamageTypes = null;

	/**
	 * ProtectionEnchantment constructor.
	 *
	 * @param int        $id
	 * @param string     $name
	 * @param int        $rarity
	 * @param int        $primaryItemFlags
	 * @param int        $secondaryItemFlags
	 * @param int        $maxLevel
	 * @param float      $typeModifier
	 * @param int[]|null $applicableDamageTypes EntityDamageEvent::CAUSE_* constants which this enchantment type applies to, or null if it applies to all types of damage.
	 */
	public function __construct(int $id, string $name, int $rarity, int $primaryItemFlags, int $secondaryItemFlags, int $maxLevel, float $typeModifier, ?array $applicableDamageTypes){
		parent::__construct($id, $name, $rarity, $primaryItemFlags, $secondaryItemFlags, $maxLevel);

		$this->typeModifier = $typeModifier;
		if($applicableDamageTypes !== null){
			$this->applicableDamageTypes = array_flip($applicableDamageTypes);
		}
	}

	/**
	 * Returns the multiplier by which this enchantment type's EPF increases with each enchantment level.
	 * @return float
	 */
	public function getTypeModifier() : float{
		return $this->typeModifier;
	}

	/**
	 * Returns the base EPF this enchantment type offers for the given enchantment level.
	 *
	 * @param int $level
	 *
	 * @return int
	 */
	public function getProtectionFactor(int $level) : int{
		return (int) floor((6 + $level ** 2) * $this->typeModifier / 3);
	}

	/**
	 * Returns whether this enchantment type offers protection from the specified damage source's cause.
	 *
	 * @param EntityDamageEvent $event
	 *
	 * @return bool
	 */
	public function isApplicable(EntityDamageEvent $event) : bool{
		return $this->applicableDamageTypes === null or isset($this->applicableDamageTypes[$event->getCause()]);
	}
}


