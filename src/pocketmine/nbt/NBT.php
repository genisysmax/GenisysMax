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

/**
 * Named Binary Tag handling classes
 */
namespace pocketmine\nbt;

use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntArrayTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\Tag;

abstract class NBT{

	public const TAG_End = 0;
	public const TAG_Byte = 1;
	public const TAG_Short = 2;
	public const TAG_Int = 3;
	public const TAG_Long = 4;
	public const TAG_Float = 5;
	public const TAG_Double = 6;
	public const TAG_ByteArray = 7;
	public const TAG_String = 8;
	public const TAG_List = 9;
	public const TAG_Compound = 10;
	public const TAG_IntArray = 11;

	/**
	 * @param int $type
	 * @param NbtStreamReader $reader
	 * @param ReaderTracker $tracker
	 *
	 * @return Tag
	 */
	public static function createTag(int $type, NbtStreamReader $reader, ReaderTracker $tracker) : Tag{
		switch($type){
			case self::TAG_Byte:
				return ByteTag::read($reader);
			case self::TAG_Short:
				return ShortTag::read($reader);
			case self::TAG_Int:
				return IntTag::read($reader);
			case self::TAG_Long:
				return LongTag::read($reader);
			case self::TAG_Float:
				return FloatTag::read($reader);
			case self::TAG_Double:
				return DoubleTag::read($reader);
			case self::TAG_ByteArray:
				return ByteArrayTag::read($reader);
			case self::TAG_String:
				return StringTag::read($reader);
			case self::TAG_List:
				return ListTag::read($reader, $tracker);
			case self::TAG_Compound:
				return CompoundTag::read($reader, $tracker);
			case self::TAG_IntArray:
				return IntArrayTag::read($reader);
			default:
				throw new NbtDataException("Unknown NBT tag type $type");
		}
	}
}


