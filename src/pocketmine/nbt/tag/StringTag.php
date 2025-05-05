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
use function func_num_args;
use function strlen;

final class StringTag extends ImmutableTag{
	/** @var string */
	private $value;

	public function __construct(string $value){
		self::restrictArgCount(__METHOD__, func_num_args(), 1);
		if(strlen($value) > 32767){
			throw new \InvalidArgumentException("StringTag cannot hold more than 32767 bytes, got string of length " . strlen($value));
		}
		$this->value = $value;
	}

	protected function getTypeName() : string{
		return "String";
	}

	public function getType() : int{
		return NBT::TAG_String;
	}

	public static function read(NbtStreamReader $reader) : self{
		return new self($reader->readString());
	}

	public function write(NbtStreamWriter $writer) : void{
		$writer->writeString($this->value);
	}

	public function getValue() : string{
		return $this->value;
	}

	protected function stringifyValue(int $indentation) : string{
		return '"' . $this->value . '"';
	}
}


