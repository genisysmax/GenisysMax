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

namespace pocketmine\world\format\storage;

use InvalidArgumentException;
use RuntimeException;
use function array_flip;
use function count;

class Palette{

	/** @var BlockStorage */
	protected $blockStorage;
	/** @var int[] */
	protected $palette;
	/** @var int[] */
	protected $paletteLookup;

	public function __construct(BlockStorage $storage, array $palette){
		$this->blockStorage = $storage;
		$this->palette = $palette;
		$this->paletteLookup = array_flip($palette);
	}

	public function get(int $paletteOffset) : int{
		if(!isset($this->palette[$paletteOffset])){
			throw new RuntimeException("Index invalid or out of bounds");
		}

		return $this->palette[$paletteOffset];
	}

	public function getOffset(int $value) : int{
		if(!isset($this->paletteLookup[$value])){
			throw new RuntimeException("Value $value is not in the palette");
		}

		return $this->paletteLookup[$value];
	}

	public function add(int $paletteValue) : int{
		if($this->count() > $this->blockStorage->getMaxEntryValue()){
			throw new InvalidArgumentException("Palette is too long for {$this->blockStorage->getBitsPerBlock()} bits per block");
		}

		$paletteOffset = count($this->palette);
		$this->palette[$paletteOffset] = $paletteValue;
		$this->paletteLookup[$paletteValue] = $paletteOffset;
		return $paletteOffset;
	}

	public function has(int $paletteValue) : bool{
		return isset($this->paletteLookup[$paletteValue]);
	}

	public function replace(int $paletteOffset, int $newValue) : void{
		if($paletteOffset >= count($this->palette)){
			throw new RuntimeException("Index invalid or out of range");
		}

		$oldValue = $this->palette[$paletteOffset];
		unset($this->paletteLookup[$oldValue]);

		$this->palette[$paletteOffset] = $newValue;
		$this->paletteLookup[$newValue] = $paletteOffset;
	}

	/**
	 * @return int[]
	 */
	public function toArray() : array{
		return $this->palette;
	}

	public function count() : int{
		return count($this->palette);
	}

	public function __destruct(){
		$this->blockStorage = null;
	}
}

