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
 * UPnP port forwarding support. Only for Windows
 */
namespace pocketmine\network\upnp;

use pocketmine\utils\Internet;
use pocketmine\utils\Utils;
use function class_exists;
use function is_object;

abstract class UPnP{

	public static function PortForward(int $port) : bool{
		if(Internet::$online === false){
			return false;
		}
		if(Utils::getOS() != "win" or !class_exists("COM")){
			return false;
		}

		$myLocalIP = Internet::getInternalIP();
		try{
			/** @noinspection PhpUndefinedClassInspection */
			$com = new \COM("HNetCfg.NATUPnP");
			/** @noinspection PhpUndefinedFieldInspection */
			if($com === false or !is_object($com->StaticPortMappingCollection)){
				return false;
			}
			/** @noinspection PhpUndefinedFieldInspection */
			$com->StaticPortMappingCollection->Add($port, "UDP", $port, $myLocalIP, true, "PocketMine-MP");
		}catch(\Throwable $e){
			return false;
		}

		return true;
	}

	public static function RemovePortForward(int $port) : bool{
		if(Internet::$online === false){
			return false;
		}
		if(Utils::getOS() != "win" or !class_exists("COM")){
			return false;
		}

		try{
			/** @noinspection PhpUndefinedClassInspection */
			$com = new \COM("HNetCfg.NATUPnP") or false;
			/** @noinspection PhpUndefinedFieldInspection */
			if($com === false or !is_object($com->StaticPortMappingCollection)){
				return false;
			}
			/** @noinspection PhpUndefinedFieldInspection */
			$com->StaticPortMappingCollection->Remove($port, "UDP");
		}catch(\Throwable $e){
			return false;
		}

		return true;
	}
}

