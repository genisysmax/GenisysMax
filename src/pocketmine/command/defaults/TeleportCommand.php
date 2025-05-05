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
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use function array_filter;
use function count;
use function round;
use function strlen;

class TeleportCommand extends VanillaCommand{

	public function __construct($name){
		parent::__construct(
			$name,
			"%pocketmine.command.tp.description",
			"%commands.tp.usage"
		);
		$this->setPermission("pocketmine.command.teleport");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		$args = array_filter($args, function($arg){
			return strlen($arg) > 0;
		});
		if(count($args) < 1 or count($args) > 6){
			throw new InvalidCommandSyntaxException();
		}

		$target = null;
		$origin = $sender;

		if(count($args) === 1 or count($args) === 3){
			if($sender instanceof Player){
				$target = $sender;
			}else{
				$sender->sendMessage(TextFormat::RED . "Please provide a player!");

				return true;
			}
			if(count($args) === 1){
				$target = $sender->getServer()->getPlayer($args[0]);
				if($target === null){
					$sender->sendMessage(TextFormat::RED . "Can't find player " . $args[0]);

					return true;
				}
			}
		}else{
			$target = $sender->getServer()->getPlayer($args[0]);
			if($target === null){
				$sender->sendMessage(TextFormat::RED . "Can't find player " . $args[0]);

				return true;
			}
			if(count($args) === 2){
				$origin = $target;
				$target = $sender->getServer()->getPlayer($args[1]);
				if($target === null){
					$sender->sendMessage(TextFormat::RED . "Can't find player " . $args[1]);

					return true;
				}
			}
		}

		if(count($args) < 3){
			$origin->teleport($target);
			Command::broadcastCommandMessage($sender, new TranslationContainer("commands.tp.success", [$origin->getName(), $target->getName()]));

			return true;
		}elseif($target->getLevel() !== null){
			if(count($args) === 4 or count($args) === 6){
				$pos = 1;
			}else{
				$pos = 0;
			}

			$x = $this->getRelativeDouble($target->x, $sender, $args[$pos++]);
			$y = $this->getRelativeDouble($target->y, $sender, $args[$pos++], 0, 256);
			$z = $this->getRelativeDouble($target->z, $sender, $args[$pos++]);
			$yaw = $target->getYaw();
			$pitch = $target->getPitch();

			if(count($args) === 6 or (count($args) === 5 and $pos === 3)){
				$yaw = (float) $args[$pos++];
				$pitch = (float) $args[$pos++];
			}

			$target->teleport(new Vector3($x, $y, $z), $yaw, $pitch);
			Command::broadcastCommandMessage($sender, new TranslationContainer("commands.tp.success.coordinates", [$target->getName(), round($x, 2), round($y, 2), round($z, 2)]));

			return true;
		}

		throw new InvalidCommandSyntaxException();
	}
}


