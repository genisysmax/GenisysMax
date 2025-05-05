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

namespace pocketmine\block\utils;

class ColorBlockMetaHelper{

	public static function getColorFromMeta(int $meta) : string{
		static $names = [
			0 => "White",
			1 => "Orange",
			2 => "Magenta",
			3 => "Light Blue",
			4 => "Yellow",
			5 => "Lime",
			6 => "Pink",
			7 => "Gray",
			8 => "Light Gray",
			9 => "Cyan",
			10 => "Purple",
			11 => "Blue",
			12 => "Brown",
			13 => "Green",
			14 => "Red",
			15 => "Black"
		];

		return $names[$meta] ?? "Unknown";
	}
}


