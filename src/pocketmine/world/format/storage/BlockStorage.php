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
use SplFixedArray;
use function assert;
use function count;
use function in_array;
use function intdiv;

class BlockStorage{

	public const PALETTED_1 = 1; // 1 bit per block, 32 blocks per word
	public const PALETTED_2 = 2; // 2 bits per block, 16 blocks per word
	public const PALETTED_3 = 3; // 3 bits per block, 10 blocks per word and 2 bits of padding per word
	public const PALETTED_4 = 4; // 4 bits per block, 8 blocks per word
	public const PALETTED_5 = 5; // 5 bits per block, 6 blocks per word and 2 bits of padding per word
	public const PALETTED_6 = 6; // 6 bits per block, 5 blocks per word and 2 bits of padding per word
	public const PALETTED_8 = 8; // 8 bits per block, 4 blocks per word
	public const PALETTED_16 = 16; // 16 bit per block, 2 blocks per word

	public const PALETTE_TYPES = [
		self::PALETTED_1,
		self::PALETTED_2,
		self::PALETTED_3,
		self::PALETTED_4,
		self::PALETTED_5,
		self::PALETTED_6,
		self::PALETTED_8,
		self::PALETTED_16
	];

	/** @var int */
	protected $bitsPerBlock;
	/** @var int */
	protected $maxEntryValue;
	/** @var Palette */
	protected $palette;
	/** @var SplFixedArray|int[] */
	protected $wordArray;

	public function __construct(int $bitsPerBlock, array $palette, ?SplFixedArray $wordArray = null){
		if(!in_array($bitsPerBlock, self::PALETTE_TYPES, true)){
			throw new InvalidArgumentException("Unknown block storage type: $bitsPerBlock");
		}
		$maxCount = (1 << $bitsPerBlock);
		if(count($palette) > $maxCount){
			throw new InvalidArgumentException("Palette is too long for $bitsPerBlock bits per block");
		}

		$this->bitsPerBlock = $bitsPerBlock;
		$this->maxEntryValue = $maxCount - 1;
		$this->palette = new Palette($this, $palette);

		$arraySize = intdiv(4095, intdiv(32, $bitsPerBlock)) + 1;
		if($wordArray !== null and $wordArray->getSize() !== $arraySize){
			throw new InvalidArgumentException("Given word array has invalid size");
		}
		$this->wordArray = $wordArray ?? new SplFixedArray($arraySize);
	}

	public function getPaletteOffset(int $x, int $y, int $z) : int{
		$index = ($x << 8) | ($z << 4) | $y;
		self::indexToOffset($this->bitsPerBlock, $index, $arrayIndex, $offset);
		return (($this->wordArray->offsetGet($arrayIndex) ?? 0) >> $offset) & $this->maxEntryValue;
	}

	public function get(int $x, int $y, int $z) : int{
		return $this->palette->get($this->getPaletteOffset($x, $y, $z));
	}

	public function set(int $x, int $y, int $z, int $value) : void{
		if(!isset($this->paletteLookup[$value])){
			throw new InvalidArgumentException("The specified value $value is not in the palette");
		}

		$palettedVal = $this->palette->getOffset($value);
		assert($palettedVal <= $this->maxEntryValue);

		$index = ($x << 8) | ($z << 4) | $y;
		self::indexToOffset($this->bitsPerBlock, $index, $arrayIndex, $offset);

		$word = $this->wordArray->offsetGet($arrayIndex) ?? 0;
		$word &= ~($this->maxEntryValue << $offset); // clear old value
		$word |= ($palettedVal << $offset); // insert the new one

		$this->wordArray->offsetSet($arrayIndex, $word);
	}

	/**
	 * @return int
	 */
	public function getBitsPerBlock() : int{
		return $this->bitsPerBlock;
	}

	/**
	 * @return int
	 */
	public function getMaxEntryValue() : int{
		return $this->maxEntryValue;
	}

	/**
	 * @return int[]
	 */
	public function getWordArray() : array{
		return $this->wordArray->toArray();
	}

	/**
	 * @return Palette
	 */
	public function getPalette() : Palette{
		return $this->palette;
	}

	public static function indexToOffset(int $bitsPerBlock, int $index, &$arrayIndex, &$offset) : void{
		$entriesPerWord = intdiv(32, $bitsPerBlock);

		$arrayIndex = intdiv($index, $entriesPerWord);
		$offset = ($index % $entriesPerWord) * $bitsPerBlock;
	}
}

