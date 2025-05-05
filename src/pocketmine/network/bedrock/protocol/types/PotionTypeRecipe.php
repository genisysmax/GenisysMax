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

namespace pocketmine\network\bedrock\protocol\types;

class PotionTypeRecipe{
	/** @var int */
	private $inputPotionId;
	/** @var int */
	private $inputPotionMeta;
	/** @var int */
	private $ingredientItemId;
	/** @var int */
	private $ingredientItemMeta;
	/** @var int */
	private $outputPotionId;
	/** @var int */
	private $outputPotionMeta;

	public function __construct(int $inputPotionId, int $inputPotionMeta, int $ingredientItemId, int $ingredientItemMeta, int $outputPotionId, int $outputPotionMeta){
		$this->inputPotionId = $inputPotionId;
		$this->inputPotionMeta = $inputPotionMeta;
		$this->ingredientItemId = $ingredientItemId;
		$this->ingredientItemMeta = $ingredientItemMeta;
		$this->outputPotionId = $outputPotionId;
		$this->outputPotionMeta = $outputPotionMeta;
	}

	/**
	 * @return int
	 */
	public function getInputPotionId() : int{
		return $this->inputPotionId;
	}

	/**
	 * @return int
	 */
	public function getInputPotionMeta() : int{
		return $this->inputPotionMeta;
	}

	/**
	 * @return int
	 */
	public function getIngredientItemId() : int{
		return $this->ingredientItemId;
	}

	/**
	 * @return int
	 */
	public function getIngredientItemMeta() : int{
		return $this->ingredientItemMeta;
	}

	/**
	 * @return int
	 */
	public function getOutputPotionId() : int{
		return $this->outputPotionId;
	}

	/**
	 * @return int
	 */
	public function getOutputPotionMeta() : int{
		return $this->outputPotionMeta;
	}
}

