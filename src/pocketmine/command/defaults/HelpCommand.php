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
use pocketmine\event\TranslationContainer;
use pocketmine\utils\TextFormat;
use function array_chunk;
use function array_pop;
use function count;
use function explode;
use function implode;
use function is_numeric;
use function ksort;
use function min;
use function strtolower;
use const SORT_FLAG_CASE;
use const SORT_NATURAL;

class HelpCommand extends VanillaCommand{

	public function __construct($name){
		parent::__construct(
			$name,
			"%pocketmine.command.help.description",
			"%commands.help.usage",
			["?"]
		);
		$this->setPermission("pocketmine.command.help");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		if(count($args) === 0){
			$command = "";
			$pageNumber = 1;
		}elseif(is_numeric($args[count($args) - 1])){
			$pageNumber = (int) array_pop($args);
			if($pageNumber <= 0){
				$pageNumber = 1;
			}
			$command = implode(" ", $args);
		}else{
			$command = implode(" ", $args);
			$pageNumber = 1;
		}

		$pageHeight = $sender->getScreenLineHeight();

		if($command === ""){
			/** @var Command[][] $commands */
			$commands = [];
			foreach($sender->getServer()->getCommandMap()->getCommands() as $command){
				if($command->testPermissionSilent($sender)){
					$commands[$command->getName()] = $command;
				}
			}
			ksort($commands, SORT_NATURAL | SORT_FLAG_CASE);
			$commands = array_chunk($commands, $pageHeight);
			$pageNumber = (int) min(count($commands), $pageNumber);
			if($pageNumber < 1){
				$pageNumber = 1;
			}
			$sender->sendMessage(new TranslationContainer("commands.help.header", [$pageNumber, count($commands)]));
			if(isset($commands[$pageNumber - 1])){
				foreach($commands[$pageNumber - 1] as $command){
					$sender->sendMessage(TextFormat::DARK_GREEN . "/" . $command->getName() . ": " . TextFormat::WHITE . $command->getDescription());
				}
			}

			return true;
		}else{
			if(($cmd = $sender->getServer()->getCommandMap()->getCommand(strtolower($command))) instanceof Command){
				if($cmd->testPermissionSilent($sender)){
					$message = TextFormat::YELLOW . "--------- " . TextFormat::WHITE . " Help: /" . $cmd->getName() . TextFormat::YELLOW . " ---------\n";
					$message .= TextFormat::GOLD . "Description: " . TextFormat::WHITE . $cmd->getDescription() . "\n";
					$message .= TextFormat::GOLD . "Usage: " . TextFormat::WHITE . implode("\n" . TextFormat::WHITE, explode("\n", $cmd->getUsage())) . "\n";
					$sender->sendMessage($message);

					return true;
				}
			}
			$sender->sendMessage(TextFormat::RED . "No help for " . strtolower($command));

			return true;
		}
	}

}


