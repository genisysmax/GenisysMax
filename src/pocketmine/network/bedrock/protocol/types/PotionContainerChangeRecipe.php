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

class PotionContainerChangeRecipe{
	/** @var int */
	private $inputItemId;
	/** @var int */
	private $ingredientItemId;
	/** @var int */
	private $outputItemId;

	public function __construct(int $inputItemId, int $ingredientItemId, int $outputItemId){
		$this->inputItemId = $inputItemId;
		$this->ingredientItemId = $ingredientItemId;
		$this->outputItemId = $outputItemId;
	}

	public function getInputItemId() : int{
		return $this->inputItemId;
	}

	public function getIngredientItemId() : int{
		return $this->ingredientItemId;
	}

	public function getOutputItemId() : int{
		return $this->outputItemId;
	}
}

