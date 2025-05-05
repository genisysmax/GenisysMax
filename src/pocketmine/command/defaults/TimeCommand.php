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

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\event\TranslationContainer;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use function count;

class TimeCommand extends VanillaCommand{

	public function __construct($name){
		parent::__construct(
			$name,
			"%pocketmine.command.time.description",
			"%pocketmine.command.time.usage"
		);
		$this->setPermission("pocketmine.command.time.add;pocketmine.command.time.set;pocketmine.command.time.start;pocketmine.command.time.stop");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(count($args) < 1){
			throw new InvalidCommandSyntaxException();
		}

		if($args[0] === "start"){
			if(!$sender->hasPermission("pocketmine.command.time.start")){
				$sender->sendCommandMessage(new TranslationContainer(TextFormat::RED . "%commands.generic.permission"));

				return true;
			}
			foreach($sender->getServer()->getLevels() as $level){
				$level->checkTime();
				$level->startTime();
				$level->checkTime();
			}
			Command::broadcastCommandMessage($sender, "Restarted the time");
			return true;
		}elseif($args[0] === "stop"){
			if(!$sender->hasPermission("pocketmine.command.time.stop")){
				$sender->sendCommandMessage(new TranslationContainer(TextFormat::RED . "%commands.generic.permission"));

				return true;
			}
			foreach($sender->getServer()->getLevels() as $level){
				$level->checkTime();
				$level->stopTime();
				$level->checkTime();
			}
			Command::broadcastCommandMessage($sender, "Stopped the time");
			return true;
		}elseif($args[0] === "query"){
			if(!$sender->hasPermission("pocketmine.command.time.query")){
				$sender->sendCommandMessage(new TranslationContainer(TextFormat::RED . "%commands.generic.permission"));

				return true;
			}
			if($sender instanceof Player){
				$level = $sender->getLevel();
			}else{
				$level = $sender->getServer()->getDefaultLevel();
			}
			$sender->sendMessage(new TranslationContainer("commands.time.query", [$level->getTime()]));
			return true;
		}


		if(count($args) < 2){
			throw new InvalidCommandSyntaxException();
		}

		if($args[0] === "set"){
			if(!$sender->hasPermission("pocketmine.command.time.set")){
				$sender->sendCommandMessage(new TranslationContainer(TextFormat::RED . "%commands.generic.permission"));

				return true;
			}

			if($args[1] === "day"){
				$value = 0;
			}elseif($args[1] === "night"){
				$value = Level::TIME_NIGHT;
			}else{
				$value = $this->getInteger($sender, $args[1], 0);
			}

			foreach($sender->getServer()->getLevels() as $level){
				$level->checkTime();
				$level->setTime($value);
				$level->checkTime();
			}
			Command::broadcastCommandMessage($sender, new TranslationContainer("commands.time.set", [$value]));
		}elseif($args[0] === "add"){
			if(!$sender->hasPermission("pocketmine.command.time.add")){
				$sender->sendCommandMessage(new TranslationContainer(TextFormat::RED . "%commands.generic.permission"));

				return true;
			}

			$value = $this->getInteger($sender, $args[1], 0);
			foreach($sender->getServer()->getLevels() as $level){
				$level->checkTime();
				$level->setTime($level->getTime() + $value);
				$level->checkTime();
			}
			Command::broadcastCommandMessage($sender, new TranslationContainer("commands.time.added", [$value]));
		}else{
			throw new InvalidCommandSyntaxException();
		}

		return true;
	}
}

