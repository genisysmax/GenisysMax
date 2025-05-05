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

namespace pocketmine\event\player;

use pocketmine\entity\Skin;
use pocketmine\event\Cancellable;
use pocketmine\Player;

class PlayerSkinChangeEvent extends PlayerEvent implements Cancellable{
	public static $handlerList;

	/** @var Skin */
	protected $oldSkin;
	/** @var Skin */
	protected $newSkin;

	public function __construct(Player $player, Skin $oldSkin, Skin $newSkin){
		$this->player = $player;
		$this->oldSkin = $oldSkin;
		$this->newSkin = $newSkin;
	}

	/**
	 * @return Skin
	 */
	public function getOldSkin() : Skin{
		return $this->oldSkin;
	}

	/**
	 * @return Skin
	 */
	public function getNewSkin() : Skin{
		return $this->newSkin;
	}

	/**
	 * @param Skin $newSkin
	 */
	public function setNewSkin(Skin $newSkin) : void{
		$this->newSkin = $newSkin;
	}
}

