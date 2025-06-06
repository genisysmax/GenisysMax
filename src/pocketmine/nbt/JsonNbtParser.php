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

use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\Tag;
use pocketmine\utils\BinaryDataException;
use pocketmine\utils\BinaryStream;
use function is_numeric;
use function strpos;
use function strtolower;
use function substr;
use function trim;

class JsonNbtParser{

	/**
	 * Parses JSON-formatted NBT into a CompoundTag and returns it. Used for parsing tags supplied with the /give command.
	 *
	 * @param string $data
	 *
	 * @return CompoundTag
	 */
	public static function parseJson(string $data) : CompoundTag{
		$stream = new BinaryStream(trim($data, " \r\n\t"));

		try{
			if(($b = $stream->get(1)) !== "{"){
				throw new NbtDataException("Syntax error: expected compound start but got '$b'");
			}
			$ret = self::parseCompound($stream); //don't return directly, syntax needs to be validated
		}catch(BinaryDataException $e){
			throw new NbtDataException("Syntax error: " . $e->getMessage() . " at offset " . $stream->getOffset());
		}
		if(!$stream->feof()){
			throw new NbtDataException("Syntax error: unexpected trailing characters after end of tag: " . $stream->getRemaining());
		}

		return $ret;
	}

	/**
	 * @param BinaryStream $stream
	 *
	 * @return ListTag
	 */
	private static function parseList(BinaryStream $stream) : ListTag{
		$retval = new ListTag();

		if(self::skipWhitespace($stream, "]")){
			while(!$stream->feof()){
				$retval->push(self::readValue($stream));
				if(self::readBreak($stream, "]")){
					return $retval;
				}
			}

			throw new NbtDataException("Syntax error: unexpected end of stream");
		}

		return $retval;
	}

	/**
	 * @param BinaryStream $stream
	 *
	 * @return CompoundTag
	 */
	private static function parseCompound(BinaryStream $stream) : CompoundTag{
		$retval = new CompoundTag();

		if(self::skipWhitespace($stream, "}")){
			while(!$stream->feof()){
				$k = self::readKey($stream);
				if($retval->hasTag($k)){
					throw new NbtDataException("Syntax error: duplicate compound leaf node '$k'");
				}
				$retval->setTag($k, self::readValue($stream));

				if(self::readBreak($stream, "}")){
					return $retval;
				}
			}

			throw new NbtDataException("Syntax error: unexpected end of stream");
		}

		return $retval;
	}

	/**
	 * @param BinaryStream $stream
	 * @param string $terminator
	 *
	 * @return bool
	 */
	private static function skipWhitespace(BinaryStream $stream, string $terminator) : bool{
		while(!$stream->feof()){
			$b = $stream->get(1);
			if($b === $terminator){
				return false;
			}
			if($b === " " or $b === "\n" or $b === "\t" or $b === "\r"){
				continue;
			}

			$stream->setOffset($stream->getOffset() - 1);
			return true;
		}

		throw new NbtDataException("Syntax error: unexpected end of stream, expected start of key");
	}

	/**
	 * @param BinaryStream $stream
	 * @param string $terminator
	 *
	 * @return bool true if terminator has been found, false if comma was found
	 */
	private static function readBreak(BinaryStream $stream, string $terminator) : bool{
		if($stream->feof()){
			throw new NbtDataException("Syntax error: unexpected end of stream, expected '$terminator'");
		}
		$offset = $stream->getOffset();
		$c = $stream->get(1);
		if($c === ","){
			return false;
		}
		if($c === $terminator){
			return true;
		}

		throw new NbtDataException("Syntax error: unexpected '$c' end at offset $offset");
	}

	/**
	 * @param BinaryStream $stream
	 *
	 * @return Tag
	 */
	private static function readValue(BinaryStream $stream) : Tag{
		$value = "";
		$inQuotes = false;

		$offset = $stream->getOffset();

		$foundEnd = false;

		/** @var Tag|null $retval */
		$retval = null;

		while(!$stream->feof()){
			$offset = $stream->getOffset();
			$c = $stream->get(1);

			if($inQuotes){ //anything is allowed inside quotes, except unescaped quotes
				if($c === '"'){
					$inQuotes = false;
					$retval = new StringTag($value);
					$foundEnd = true;
				}elseif($c === "\\"){
					$value .= $stream->get(1);
				}else{
					$value .= $c;
				}
			}else{
				if($c === "," or $c === "}" or $c === "]"){ //end of parent tag
					$stream->setOffset($stream->getOffset() - 1); //the caller needs to be able to read this character
					$foundEnd = true;
					break;
				}

				if($value === "" or $foundEnd){
					if($c === "\r" or $c === "\n" or $c === "\t" or $c === " "){ //leading or trailing whitespace, ignore it
						continue;
					}

					if($foundEnd){ //unexpected non-whitespace character after end of value
						throw new NbtDataException("Syntax error: unexpected '$c' after end of value at offset $offset");
					}
				}

				if($c === '"'){ //start of quoted string
					if($value !== ""){
						throw new NbtDataException("Syntax error: unexpected quote at offset $offset");
					}
					$inQuotes = true;

				}elseif($c === "{"){ //start of compound tag
					if($value !== ""){
						throw new NbtDataException("Syntax error: unexpected compound start at offset $offset (enclose in double quotes for literal)");
					}

					$retval = self::parseCompound($stream);
					$foundEnd = true;

				}elseif($c === "["){ //start of list tag - TODO: arrays
					if($value !== ""){
						throw new NbtDataException("Syntax error: unexpected list start at offset $offset (enclose in double quotes for literal)");
					}

					$retval = self::parseList($stream);
					$foundEnd = true;

				}else{ //any other character
					$value .= $c;
				}
			}
		}

		if($retval !== null){
			return $retval;
		}

		if($value === ""){
			throw new NbtDataException("Syntax error: empty value at offset $offset");
		}
		if(!$foundEnd){
			throw new NbtDataException("Syntax error: unexpected end of stream at offset $offset");
		}

		$last = strtolower(substr($value, -1));
		$part = substr($value, 0, -1);

		if($last !== "b" and $last !== "s" and $last !== "l" and $last !== "f" and $last !== "d"){
			$part = $value;
			$last = null;
		}

		if(is_numeric($part)){
			if($last === "f" or $last === "d" or strpos($part, ".") !== false or strpos($part, "e") !== false){ //e = scientific notation
				$value = (float) $part;
				switch($last){
					case "d":
						return new DoubleTag($value);
					case "f":
					default:
						return new FloatTag($value);
				}
			}else{
				$value = (int) $part;
				switch($last){
					case "b":
						return new ByteTag($value);
					case "s":
						return new ShortTag($value);
					case "l":
						return new LongTag($value);
					default:
						return new IntTag($value);
				}
			}
		}else{
			return new StringTag($value);
		}
	}

	/**
	 * @param BinaryStream $stream
	 *
	 * @return string
	 */
	private static function readKey(BinaryStream $stream) : string{
		$key = "";
		$offset = $stream->getOffset();

		$inQuotes = false;
		$foundEnd = false;

		while(!$stream->feof()){
			$c = $stream->get(1);

			if($inQuotes){
				if($c === '"'){
					$inQuotes = false;
					$foundEnd = true;
				}elseif($c === "\\"){
					$key .= $stream->get(1);
				}else{
					$key .= $c;
				}
			}else{
				if($c === ":"){
					$foundEnd = true;
					break;
				}

				if($key === "" or $foundEnd){
					if($c === "\r" or $c === "\n" or $c === "\t" or $c === " "){ //leading or trailing whitespace, ignore it
						continue;
					}

					if($foundEnd){ //unexpected non-whitespace character after end of value
						throw new NbtDataException("Syntax error: unexpected '$c' after end of value at offset $offset");
					}
				}

				if($c === '"'){ //start of quoted string
					if($key !== ""){
						throw new NbtDataException("Syntax error: unexpected quote at offset $offset");
					}
					$inQuotes = true;

				}elseif($c === "{" or $c === "}" or $c === "[" or $c === "]" or $c === ","){
					throw new NbtDataException("Syntax error: unexpected '$c' at offset $offset (enclose in double quotes for literal)");
				}else{ //any other character
					$key .= $c;
				}
			}
		}

		if($key === ""){
			throw new NbtDataException("Syntax error: invalid empty key at offset $offset");
		}
		if(!$foundEnd){
			throw new NbtDataException("Syntax error: unexpected end of stream at offset $offset");
		}

		return $key;
	}
}


