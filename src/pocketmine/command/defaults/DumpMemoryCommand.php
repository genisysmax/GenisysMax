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

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use function count;
use function sha1;
use function strtoupper;
use function substr;

class DumpMemoryCommand extends VanillaCommand{

	private static $executions = 0;

	public function __construct($name){
		parent::__construct(
			$name,
			"Dumps the memory",
			"/$name <TOKEN (run once to get it)> [path]"
		);
		$this->setPermission("pocketmine.command.dumpmemory");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		$token = strtoupper(substr(sha1(BOOTUP_RANDOM . ":" . $sender->getServer()->getServerUniqueId() . ":" . self::$executions), 6, 6));

		if(count($args) < 1 or strtoupper($args[0]) !== $token){
			$sender->sendMessage("Usage: /" . $this->getName() . " " . $token);
			return true;
		}

		++self::$executions;

		$sender->getServer()->getMemoryManager()->dumpServerMemory($args[1] ?? ($sender->getServer()->getDataPath() . "/memoryDump_$token"), 48, 80);
		return true;
	}
}


