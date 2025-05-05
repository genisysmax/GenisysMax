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

/**
 * Container for enchantment data applied to items.
 */
class EnchantmentInstance{
	/** @var Enchantment */
	private $enchantment;
	/** @var int */
	private $level;

	/**
	 * EnchantmentInstance constructor.
	 *
	 * @param Enchantment $enchantment Enchantment type
	 * @param int         $level Level of enchantment
	 */
	public function __construct(Enchantment $enchantment, int $level = 1){
		$this->enchantment = $enchantment;
		$this->level = $level;
	}

	/**
	 * Returns the type of this enchantment.
	 * @return Enchantment
	 */
	public function getType() : Enchantment{
		return $this->enchantment;
	}

	/**
	 * Returns the type identifier of this enchantment instance.
	 * @return int
	 */
	public function getId() : int{
		return $this->enchantment->getId();
	}

	/**
	 * Returns the level of the enchantment.
	 * @return int
	 */
	public function getLevel() : int{
		return $this->level;
	}

	/**
	 * Sets the level of the enchantment.
	 *
	 * @param int $level
	 *
	 * @return $this
	 */
	public function setLevel(int $level) : self{
		$this->level = $level;

		return $this;
	}
}


