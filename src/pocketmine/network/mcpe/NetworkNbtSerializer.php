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

namespace pocketmine\network\mcpe;

use pocketmine\nbt\BaseNbtSerializer;
use function count;
use function strlen;

class NetworkNbtSerializer extends BaseNbtSerializer{

	public function readShort() : int{
		return $this->buffer->getLShort();
	}

	public function readSignedShort() : int{
		return $this->buffer->getSignedLShort();
	}

	public function writeShort(int $v) : void{
		$this->buffer->putLShort($v);
	}

	public function readInt() : int{
		return $this->buffer->getVarInt();
	}

	public function writeInt(int $v) : void{
		$this->buffer->putVarInt($v);
	}

	public function readLong() : int{
		return $this->buffer->getVarLong();
	}

	public function writeLong(int $v) : void{
		$this->buffer->putVarLong($v);
	}

	public function readString() : string{
		return $this->buffer->get(self::checkReadStringLength($this->buffer->getUnsignedVarInt()));
	}

	public function writeString(string $v) : void{
		$this->buffer->putUnsignedVarInt(self::checkWriteStringLength(strlen($v)));
		$this->buffer->put($v);
	}

	public function readFloat() : float{
		return $this->buffer->getLFloat();
	}

	public function writeFloat(float $v) : void{
		$this->buffer->putLFloat($v);
	}

	public function readDouble() : float{
		return $this->buffer->getLDouble();
	}

	public function writeDouble(float $v) : void{
		$this->buffer->putLDouble($v);
	}

	public function readIntArray() : array{
		$len = $this->readInt(); //varint
		$ret = [];
		for($i = 0; $i < $len; ++$i){
			$ret[] = $this->readInt(); //varint
		}

		return $ret;
	}

	public function writeIntArray(array $array) : void{
		$this->writeInt(count($array)); //varint
		foreach($array as $v){
			$this->writeInt($v); //varint
		}
	}
}

