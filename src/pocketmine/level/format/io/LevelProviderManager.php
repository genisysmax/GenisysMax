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

namespace pocketmine\level\format\io;

use pocketmine\level\LevelException;
use function is_subclass_of;
use function strtolower;
use function trim;

abstract class LevelProviderManager{
	protected static $providers = [];

	/**
	 * @param string $class
	 *
	 * @throws LevelException
	 */
	public static function addProvider(string $class){
		if(!is_subclass_of($class, LevelProvider::class)){
			throw new LevelException("Class is not a subclass of LevelProvider");
		}
		/** @var LevelProvider $class */
		self::$providers[strtolower($class::getProviderName())] = $class;
	}

	/**
	 * Returns a LevelProvider class for this path, or null
	 *
	 * @param string $path
	 *
	 * @return string|null
	 */
	public static function getProvider(string $path){
		foreach(self::$providers as $provider){
			/** @var $provider LevelProvider */
			if($provider::isValid($path)){
				return $provider;
			}
		}

		return null;
	}

	/**
	 * Returns a LevelProvider by name, or null if not found
	 *
	 * @param string $name
	 *
	 * @return string|null
	 */
	public static function getProviderByName(string $name){
		return self::$providers[trim(strtolower($name))] ?? null;
	}
}

