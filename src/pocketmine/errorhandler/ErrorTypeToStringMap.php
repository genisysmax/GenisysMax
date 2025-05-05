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

namespace pocketmine\errorhandler;

final class ErrorTypeToStringMap{
	private const ERROR_STRINGS = [
		0 => "EXCEPTION",
		E_ERROR => "E_ERROR",
		E_WARNING => "E_WARNING",
		E_PARSE => "E_PARSE",
		E_NOTICE => "E_NOTICE",
		E_CORE_ERROR => "E_CORE_ERROR",
		E_CORE_WARNING => "E_CORE_WARNING",
		E_COMPILE_ERROR => "E_COMPILE_ERROR",
		E_COMPILE_WARNING => "E_COMPILE_WARNING",
		E_USER_ERROR => "E_USER_ERROR",
		E_USER_WARNING => "E_USER_WARNING",
		E_USER_NOTICE => "E_USER_NOTICE",
		E_STRICT => "E_STRICT",
		E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
		E_DEPRECATED => "E_DEPRECATED",
		E_USER_DEPRECATED => "E_USER_DEPRECATED"
	];

	private function __construct(){

	}

	/**
	 * Converts an E_* error constant to its string representation.
	 *
	 * @throws \InvalidArgumentException
	 */
	public static function get(int $errorType) : string{
		if(!isset(self::ERROR_STRINGS[$errorType])){
			throw new \InvalidArgumentException("Invalid error type $errorType");
		}

		return self::ERROR_STRINGS[$errorType];
	}
}



