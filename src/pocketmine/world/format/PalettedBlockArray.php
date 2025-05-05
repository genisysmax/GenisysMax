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

namespace pocketmine\world\format;

use InvalidArgumentException;
use pocketmine\world\format\storage\BlockStorage;
use SplFixedArray;
use function count;
use function extension_loaded;
use function intdiv;
use function pack;
use function strlen;
use function unpack;

if(!extension_loaded("chunkutils2")){

	class PalettedBlockArray{
		private const NEXT_PALETTE_TYPE = [
			1 => 2,
			2 => 3,
			3 => 4,
			4 => 5,
			5 => 6,
			6 => 8,
			8 => 16,
		];

		/** @var BlockStorage */
		private $blockStorage;
		/** @var bool */
		private $mayNeedGC = false;

		public function __construct(int $fillEntry){
			$this->blockStorage = new BlockStorage(1, [$fillEntry]);
		}

		public static function fromData(int $bitsPerBlock, string $wordArray, array $palette) : PalettedBlockArray{
			$expectedSize = self::getExpectedWordArraySize($bitsPerBlock);
			if(strlen($wordArray) !== $expectedSize * 4){
				throw new InvalidArgumentException("Word array size is incompatible with $bitsPerBlock bits per block");
			}
			$intWordArray = SplFixedArray::fromArray(unpack("V*", $wordArray), false); // LInt array

			$result = new self(0);
			$result->blockStorage = new BlockStorage($bitsPerBlock, $palette, $intWordArray);
			$result->mayNeedGC = true;
			return $result;
		}

		public function getWordArray() : string{
			return pack("V*", ...$this->blockStorage->getWordArray()); // LInt array
		}

		public function getPalette() : array{
			return $this->blockStorage->getPalette()->toArray();
		}

		public function getMaxPaletteSize() : int{
			return 1 << $this->blockStorage->getBitsPerBlock();
		}

		public function getBitsPerBlock() : int{
			return $this->blockStorage->getBitsPerBlock();
		}

		public function get(int $x, int $y, int $z) : int{
			return $this->blockStorage->get($x, $y, $z);
		}

		public function set(int $x, int $y, int $z, int $val) : void{
			$palette = $this->blockStorage->getPalette();
			if(!$palette->has($val)){
				if($palette->count() >= $this->getMaxPaletteSize()){
					$nextType = self::NEXT_PALETTE_TYPE[$this->blockStorage->getBitsPerBlock()] ?? null;

					if($nextType === null){
						$palette->replace($this->blockStorage->getPaletteOffset($x, $y, $z), $val);
						return;
					}else{
						$oldStorage = $this->blockStorage;

						$this->blockStorage = new BlockStorage($nextType, [$palette->get(0)]);
						for($x = 0; $x < 16; ++$x){
							for($z = 0; $z < 16; ++$z){
								for($y = 0; $y < 16; ++$y){
									$this->blockStorage->set($x, $y, $z, $oldStorage->get($x, $y, $z));
								}
							}
						}
					}
				}

				$palette->add($val);
			}

			$this->blockStorage->set($x, $y, $z, $val);
			$this->mayNeedGC = true;
		}

		public function replace(int $offset, int $val) : void{
			$this->blockStorage->getPalette()->replace($offset, $val);
			$this->mayNeedGC = true;
		}

		public function replaceAll(int $oldVal, int $newVal) : void{
			$palette = $this->blockStorage->getPalette();
			foreach($palette->toArray() as $offset => $value){
				if($value === $oldVal){
					$palette->replace($offset, $newVal);
				}
			}
			$this->mayNeedGC = true;
		}

		public function collectGarbage(bool $force = false) : void{
			if($this->mayNeedGC or $force){
				$unused = $this->blockStorage->getPalette()->toArray();
				for($x = 0; $x < 16; ++$x){
					for($z = 0; $z < 16; ++$z){
						for($y = 0; $y < 16; ++$y){
							unset($unused[$this->blockStorage->getPaletteOffset($x, $y, $z)]);
						}
					}
				}

				if(count($unused) > 0){
					$oldStorage = $this->blockStorage;
					$this->blockStorage = new BlockStorage(1, [$this->getPalette()[0]]);

					for($x = 0; $x < 16; ++$x){
						for($z = 0; $z < 16; ++$z){
							for($y = 0; $y < 16; ++$y){
								$this->set($x, $y, $z, $oldStorage->get($x, $y, $z));
							}
						}
					}
				}
				$this->mayNeedGC = false;
			}
		}

		public static function getExpectedWordArraySize(int $bitsPerBlock) : int{
			return intdiv(4095, intdiv(32, $bitsPerBlock)) + 1;
		}
	}
}

