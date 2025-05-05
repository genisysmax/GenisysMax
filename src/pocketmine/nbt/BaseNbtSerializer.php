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

use pocketmine\utils\Binary;
use pocketmine\utils\BinaryDataException;
use pocketmine\utils\BinaryStream;
use function strlen;

/**
 * Base Named Binary Tag encoder/decoder
 */
abstract class BaseNbtSerializer implements NbtStreamReader, NbtStreamWriter{
	/** @var BinaryStream */
	protected $buffer;

	public function __construct(){
		$this->buffer = new BinaryStream();
	}

	/**
	 * @param int $maxDepth
	 *
	 * @return TreeRoot
	 */
	private function readRoot(int $maxDepth) : TreeRoot{
		$type = $this->readByte();
		if($type === NBT::TAG_End){
			throw new NbtDataException("Found TAG_End at the start of buffer");
		}

		$rootName = $this->readString();
		return new TreeRoot(NBT::createTag($type, $this, new ReaderTracker($maxDepth)), $rootName);
	}

	/**
	 * Decodes NBT from the given binary string and returns it.
	 *
	 * @param string $buffer
	 * @param int $offset reference parameter
	 *
	 * @param int $maxDepth
	 *
	 * @return TreeRoot
	 */
	public function read(string $buffer, int &$offset = 0, int $maxDepth = 0) : TreeRoot{
		$this->buffer = new BinaryStream($buffer, $offset);

		try{
			$data = $this->readRoot($maxDepth);
		}catch(BinaryDataException $e){
			throw new NbtDataException($e->getMessage(), 0, $e);
		}
		$offset = $this->buffer->getOffset();

		return $data;
	}

	/**
	 * Decodes a list of NBT tags into objects and returns them.
	 *
	 * TODO: This is only necessary because we don't have a streams API worth mentioning. Get rid of this in the future.
	 *
	 * @param string $buffer
	 * @param int $maxDepth
	 *
	 * @return TreeRoot[]
	 */
	public function readMultiple(string $buffer, int $maxDepth = 0) : array{
		$this->buffer = new BinaryStream($buffer);

		$retval = [];

		while(!$this->buffer->feof()){
			try{
				$retval[] = $this->readRoot($maxDepth);
			}catch(BinaryDataException $e){
				throw new NbtDataException($e->getMessage(), 0, $e);
			}
		}

		return $retval;
	}

	private function writeRoot(TreeRoot $root) : void{
		$this->writeByte($root->getTag()->getType());
		$this->writeString($root->getName());
		$root->getTag()->write($this);
	}

	public function write(TreeRoot $data) : string{
		$this->buffer = new BinaryStream();

		$this->writeRoot($data);

		return $this->buffer->getBuffer();
	}

	/**
	 * @param TreeRoot[] $data
	 *
	 * @return string
	 */
	public function writeMultiple(array $data) : string{
		$this->buffer = new BinaryStream();
		foreach($data as $root){
			$this->writeRoot($root);
		}
		return $this->buffer->getBuffer();
	}

	public function readByte() : int{
		return $this->buffer->getByte();
	}

	public function readSignedByte() : int{
		return Binary::signByte($this->buffer->getByte());
	}

	public function writeByte(int $v) : void{
		$this->buffer->putByte($v);
	}

	public function readByteArray() : string{
		return $this->buffer->get($this->readInt());
	}

	public function writeByteArray(string $v) : void{
		$this->writeInt(strlen($v)); //TODO: overflow
		$this->buffer->put($v);
	}

	/**
	 * @param int $len
	 *
	 * @return int
	 */
	protected static function checkReadStringLength(int $len) : int{
		if($len > 32767){
			throw new NbtDataException("NBT string length too large ($len > 32767)");
		}
		return $len;
	}

	/**
	 * @param int $len
	 *
	 * @return int
	 */
	protected static function checkWriteStringLength(int $len) : int{
		if($len > 32767){
			throw new \InvalidArgumentException("NBT string length too large ($len > 32767)");
		}
		return $len;
	}

	public function readString() : string{
		return $this->buffer->get(self::checkReadStringLength($this->readShort()));
	}

	/**
	 * @param string $v
	 */
	public function writeString(string $v) : void{
		$this->writeShort(self::checkWriteStringLength(strlen($v)));
		$this->buffer->put($v);
	}
}


