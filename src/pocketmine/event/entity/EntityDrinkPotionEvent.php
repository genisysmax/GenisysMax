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



namespace pocketmine\event\entity;

use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\event\Cancellable;
use pocketmine\item\Potion;

class EntityDrinkPotionEvent extends EntityEvent implements Cancellable{

	public static $handlerList = null;

	/* @var Potion */
	private $potion;

	/* @var EffectInstance[] */
	private $effects;

	/**
	 * EntityDrinkPotionEvent constructor.
	 *
	 * @param Entity $entity
	 * @param Potion $potion
	 */
	public function __construct(Entity $entity, Potion $potion){
		$this->entity = $entity;
		$this->potion = $potion;
		$this->effects = $potion->getEffects();
	}

	/**
	 * @return array|EffectInstance[]
	 */
	public function getEffects(){
		return $this->effects;
	}

	/**
	 * @return Potion
	 */
	public function getPotion(){
		return $this->potion;
	}
}


