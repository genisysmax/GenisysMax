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

namespace pocketmine\math;

final class Axis{
	private function __construct(){
		//NOOP
	}

	public const Y = 0;
	public const Z = 1;
	public const X = 2;

	/**
	 * Returns a human-readable string representation of the given axis.
	 */
	public static function toString(int $axis) : string{
		return match($axis){
			Axis::Y => "y",
			Axis::Z => "z",
			Axis::X => "x",
			default => throw new \InvalidArgumentException("Invalid axis $axis")
		};
	}
}


