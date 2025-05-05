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

namespace raklib;

use function defined;
use function extension_loaded;
use function version_compare;
use const PHP_EOL;
use const PHP_VERSION;

//Dependencies check
$errors = 0;
if(version_compare(RakLib::MIN_PHP_VERSION, PHP_VERSION) > 0){
	echo "[CRITICAL] Use PHP >= " . RakLib::MIN_PHP_VERSION . PHP_EOL;
	++$errors;
}

$exts = [
	"bcmath" => "BC Math",
	"sockets" => "Sockets"
];

foreach($exts as $ext => $name){
	if(!extension_loaded($ext)){
		echo "[CRITICAL] Unable to find the $name ($ext) extension." . PHP_EOL;
		++$errors;
	}
}

if(!defined('AF_INET6')){
	echo "[CRITICAL] This build of PHP does not support IPv6. IPv6 support is required.";
	++$errors;
}

if($errors > 0){
	exit(1); //Exit with error
}
unset($errors, $exts);

abstract class RakLib{
	public const VERSION = "0.12.0";

	public const MIN_PHP_VERSION = "8.2.0";

	/**
	 * Default vanilla Raknet protocol version that this library implements. Things using RakNet can override this
	 * protocol version with something different.
	 */
	public const DEFAULT_PROTOCOL_VERSION = 6;

	public const MCPE_RAKNET_PROTOCOL_VERSION = 8;
	public const BEDROCK_RAKNET_PROTOCOL_VERSION = 11;

	public const PRIORITY_NORMAL = 0;
	public const PRIORITY_IMMEDIATE = 1;

	public const FLAG_NEED_ACK = 0b00001000;

	/**
	 * Regular RakNet uses 10 by default. MCPE uses 20. Configure this value as appropriate.
	 * @var int
	 */
	public static $SYSTEM_ADDRESS_COUNT = 20;
}


