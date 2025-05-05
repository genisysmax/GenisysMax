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

namespace pocketmine\level\sound;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;

class GenericSound extends Sound{

	public function __construct(Vector3 $pos, $id, $pitch = 0){
		parent::__construct($pos->x, $pos->y, $pos->z);
		$this->id = (int) $id;
		$this->pitch = (float) $pitch * 1000;
	}

	protected $pitch = 0;
	protected $id;

	public function getPitch(){
		return $this->pitch / 1000;
	}

	public function setPitch($pitch){
		$this->pitch = (float) $pitch * 1000;
	}


	public function encode(){
		$pk = new LevelEventPacket;
		$pk->evid = $this->id;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->data = (int) $this->pitch;

		return $pk;
	}

}


