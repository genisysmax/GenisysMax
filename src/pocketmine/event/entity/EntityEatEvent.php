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

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\event\Cancellable;
use pocketmine\item\FoodSource;

class EntityEatEvent extends EntityEvent implements Cancellable{
	public static $handlerList = null;

	/** @var FoodSource */
	private $foodSource;
	/** @var int */
	private $foodRestore;
	/** @var float */
	private $saturationRestore;
	private $residue;
	/** @var Effect[] */
	private $additionalEffects;

	public function __construct(Entity $entity, FoodSource $foodSource){
		$this->entity = $entity;
		$this->foodSource = $foodSource;
		$this->foodRestore = $foodSource->getFoodRestore();
		$this->saturationRestore = $foodSource->getSaturationRestore();
		$this->residue = $foodSource->getResidue();
		$this->additionalEffects = $foodSource->getAdditionalEffects();
	}

	public function getFoodSource(){
		return $this->foodSource;
	}

	public function getFoodRestore() : int{
		return $this->foodRestore;
	}

	public function setFoodRestore(int $foodRestore){
		$this->foodRestore = $foodRestore;
	}

	public function getSaturationRestore() : float{
		return $this->saturationRestore;
	}

	public function setSaturationRestore(float $saturationRestore){
		$this->saturationRestore = $saturationRestore;
	}

	public function getResidue(){
		return $this->residue;
	}

	public function setResidue($residue){
		$this->residue = $residue;
	}

	/**
	 * @return EffectInstance[]
	 */
	public function getAdditionalEffects() : array{
		return $this->additionalEffects;
	}

	/**
	 * @param Effect[] $additionalEffects
	 *
	 * @throws \TypeError
	 */
	public function setAdditionalEffects(array $additionalEffects) : void{
		foreach($additionalEffects as $effect){
			if(!($effect instanceof Effect)){
				throw new \TypeError("Argument 1 passed to EntityEatEvent::setAdditionalEffects() must be an Effect array");
			}
		}
		$this->additionalEffects = $additionalEffects;
	}
}


