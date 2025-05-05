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

use pocketmine\entity\Entity;

class EntityCombustByEntityEvent extends EntityCombustEvent{

	protected $combuster;

	/**
	 * @param Entity $combuster
	 * @param Entity $combustee
	 * @param int    $duration
	 */
	public function __construct(Entity $combuster, Entity $combustee, $duration){
		parent::__construct($combustee, $duration);
		$this->combuster = $combuster;
	}

	/**
	 * @return Entity
	 */
	public function getCombuster(){
		return $this->combuster;
	}

}

