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
 * Global accessor for logger
 */
final class GlobalLogger{

	private function __construct(){
		//NOOP
	}

	private static ?\Logger $logger = null;

	public static function get() : \Logger{
		if(self::$logger === null){
			self::$logger = new SimpleLogger();
		}
		return self::$logger;
	}

	public static function set(\Logger $logger) : void{
		self::$logger = $logger;
	}
}


