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



namespace pocketmine\level\sound;

use pocketmine\block\Block;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

class BlockPlaceSound extends GenericSound{

	protected $data;

	/**
	 * BlockPlaceSound constructor.
	 *
	 * @param Block $b
	 */
	public function __construct(Block $b){
		parent::__construct($b, LevelSoundEventPacket::SOUND_PLACE, 1);
		$this->data = $b->getId();
	}

	public function encode(){
		$pk = new LevelSoundEventPacket;
		$pk->sound = $this->id;
		$pk->entityType = 1;
		$pk->extraData = $this->data;
		list($pk->x, $pk->y, $pk->z) = [$this->x, $this->y, $this->z];

		return $pk;
	}
}


