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

namespace pocketmine\nbt;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;

/**
 * This class wraps around the root Tag for NBT files to avoid losing the name information.
 */
class TreeRoot{

	/** @var Tag */
	private $root;
	/** @var string */
	private $name;

	public function __construct(Tag $root, string $name = ""){
		$this->root = $root;
		$this->name = $name;
	}

	public function getTag() : Tag{
		return $this->root;
	}

	/**
	 * Helper to reduce boilerplate code for most common NBT usages that use Compound roots.
	 * TODO: this ought to be replaced by schema validation in the future
	 *
	 * @throws NbtDataException if the root is not a Compound
	 */
	public function mustGetCompoundTag() : CompoundTag{
		if($this->root instanceof CompoundTag){
			return $this->root;
		}
		throw new NbtDataException("Root is not a TAG_Compound");
	}

	public function getName() : string{
		return $this->name;
	}

	public function equals(TreeRoot $that) : bool{
		return $this->name === $that->name and $this->root->equals($that->root);
	}

	public function __toString(){
		return "ROOT {\n  " . ($this->name !== "" ? "\"$this->name\" => " : "") . $this->root->toString(1) . "\n}";
	}
}


