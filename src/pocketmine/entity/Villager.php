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

namespace pocketmine\entity;

use pocketmine\nbt\tag\IntTag;

class Villager extends Creature implements NPC, Ageable{
	public const PROFESSION_FARMER = 0;
	public const PROFESSION_LIBRARIAN = 1;
	public const PROFESSION_PRIEST = 2;
	public const PROFESSION_BLACKSMITH = 3;
	public const PROFESSION_BUTCHER = 4;
	public const PROFESSION_GENERIC = 5;

	public const NETWORK_ID = self::VILLAGER;

	public float $width = 0.6;
	public float $height = 1.8;

	public function getName(): string
    {
		return "Villager";
	}

	protected function initEntity() : void{
		parent::initEntity();
		if(!$this->namedtag->hasTag("Profession", IntTag::class)){
			$this->setProfession(self::PROFESSION_GENERIC);
		}
	}

	/**
	 * Sets the villager profession
	 *
	 * @param $profession
	 */
	public function setProfession($profession){
		$this->namedtag->setInt("Profession", $profession);
	}

	public function getProfession(){
		return $this->namedtag->getInt("Profession");
	}

}


