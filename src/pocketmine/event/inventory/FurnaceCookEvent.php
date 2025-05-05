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

namespace pocketmine\event\inventory;

use pocketmine\event\block\BlockEvent;
use pocketmine\event\Cancellable;
use pocketmine\tile\Furnace;

class FurnaceCookEvent extends BlockEvent implements Cancellable{
    public static $handlerList = null;

	/** @var Furnace */
	private $furnace;
	/** @var int */
	private $maxCookTime;


	public function __construct(Furnace $furnace, int $maxCookTime){
		parent::__construct($furnace->getBlock());	
		$this->maxCookTime = $maxCookTime;
		$this->furnace = $furnace;
	}

	public function getFurnace() : Furnace{
		return $this->furnace;
	}

	public function getMaxCookTime() : int{
		return $this->maxCookTime;
	}

	public function setMaxCookTime(int $maxCookTime) : void{
		$this->maxCookTime = $maxCookTime;
	}
}


