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

namespace pocketmine\network\bedrock\palette;

use pocketmine\nbt\tag\CompoundTag;

final class R12ToCurrentBlockMapEntry{

	/** @var string */
	private $id;
	/** @var int */
	private $meta;
	/** @var CompoundTag */
	private $blockState;

	public function __construct(string $id, int $meta, CompoundTag $blockState){
		$this->id = $id;
		$this->meta = $meta;
		$this->blockState = $blockState;
	}

	public function getId() : string{
		return $this->id;
	}

	public function getMeta() : int{
		return $this->meta;
	}

	public function getBlockState() : CompoundTag{
		return $this->blockState;
	}

	public function __toString(){
		return "id=$this->id, meta=$this->meta, nbt=$this->blockState";
	}
}

