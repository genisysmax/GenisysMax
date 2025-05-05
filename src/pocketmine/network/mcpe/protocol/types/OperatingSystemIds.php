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

namespace pocketmine\network\mcpe\protocol\types;

/**
 * @deprecated
 */
interface OperatingSystemIds{

	public const UNKNOWN = -1;
	public const ANDROID = 1;
	public const IOS = 2;
	public const OSX = 3;
	public const FIREOS = 4;
	public const GEARVR = 5;
	public const HOLOLENS = 6;
	public const WIN10 = 7;
	public const WIN32 = 8;
	public const DEDICATED = 9;
	public const ORBIS = 10;
	public const NX = 11;

}


