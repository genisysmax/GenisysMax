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

namespace pocketmine\network\bedrock\protocol\types;

/**
 * Trait for enums serialized in packets. Provides a convenient helper method to read, validate and properly bail on
 * invalid values.
 */
trait PacketIntEnumTrait{

	/**
	 * @throws \InvalidArgumentException
	 */
	public static function fromPacket(int $value) : self{
		$enum = self::tryFrom($value);
		if($enum === null){
			throw new \InvalidArgumentException("Invalid raw value $value for " . static::class);
		}

		return $enum;
	}
}

