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

namespace pocketmine\network\bedrock\protocol\types\command;

use pocketmine\utils\UUID;

class CommandOriginData{
	public const int ORIGIN_PLAYER = 0;
	public const int ORIGIN_BLOCK = 1;
	public const int ORIGIN_MINECART_BLOCK = 2;
	public const int ORIGIN_DEV_CONSOLE = 3;
	public const int ORIGIN_TEST = 4;
	public const int ORIGIN_AUTOMATION_PLAYER = 5;
	public const int ORIGIN_CLIENT_AUTOMATION = 6;
	public const int ORIGIN_DEDICATED_SERVER = 7;
	public const int ORIGIN_ACTOR = 8;
	public const int ORIGIN_VIRTUAL = 9;
	public const int ORIGIN_GAME_ARGUMENT = 10;
	public const int ORIGIN_ACTOR_SERVER = 11; //???

	/** @var int */
	public $type;
	/** @var UUID */
	public $uuid;

	/** @var string */
	public $requestId;

	/** @var int */
	public $varlong1;
}


