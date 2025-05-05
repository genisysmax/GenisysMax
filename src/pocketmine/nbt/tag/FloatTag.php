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

final class FloatTag extends ImmutableTag{
	/** @var float */
	private $value;

	public function __construct(float $value){
		self::restrictArgCount(__METHOD__, func_num_args(), 1);
		$this->value = $value;
	}

	protected function getTypeName() : string{
		return "Float";
	}

	public function getType() : int{
		return NBT::TAG_Float;
	}

	public static function read(NbtStreamReader $reader) : self{
		return new self($reader->readFloat());
	}

	public function write(NbtStreamWriter $writer) : void{
		$writer->writeFloat($this->value);
	}

	public function getValue() : float{
		return $this->value;
	}

	protected function stringifyValue(int $indentation) : string{
		return (string) $this->value;
	}
}


