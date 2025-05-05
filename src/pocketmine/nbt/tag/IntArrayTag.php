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

namespace pocketmine\nbt\tag;

use pocketmine\nbt\NBT;
use pocketmine\nbt\NbtStreamReader;
use pocketmine\nbt\NbtStreamWriter;
use function assert;
use function func_num_args;
use function implode;
use function is_int;

final class IntArrayTag extends ImmutableTag{
	/** @var int[] */
	private $value;

	/**
	 * @param int[] $value
	 */
	public function __construct(array $value){
		self::restrictArgCount(__METHOD__, func_num_args(), 1);
		assert((function() use(&$value) : bool{
			foreach($value as $v){
				if(!is_int($v)){
					return false;
				}
			}

			return true;
		})());

		$this->value = $value;
	}

	protected function getTypeName() : string{
		return "IntArray";
	}

	public function getType() : int{
		return NBT::TAG_IntArray;
	}

	public static function read(NbtStreamReader $reader) : self{
		return new self($reader->readIntArray());
	}

	public function write(NbtStreamWriter $writer) : void{
		$writer->writeIntArray($this->value);
	}

	protected function stringifyValue(int $indentation) : string{
		return "[" . implode(",", $this->value) . "]";
	}

	/**
	 * @return int[]
	 */
	public function getValue() : array{
		return $this->value;
	}
}


