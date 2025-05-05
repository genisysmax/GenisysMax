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

final class ByteTag extends ImmutableTag{
	use IntegerishTagTrait;

	protected function min() : int{ return -0x80; }

	protected function max() : int{ return 0x7f; }

	protected function getTypeName() : string{
		return "Byte";
	}

	public function getType() : int{
		return NBT::TAG_Byte;
	}

	public static function read(NbtStreamReader $reader) : self{
		return new self($reader->readSignedByte());
	}

	public function write(NbtStreamWriter $writer) : void{
		$writer->writeByte($this->value);
	}
}


