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
use pocketmine\item\Item;
use pocketmine\nbt\JsonNBTParser;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use function array_slice;
use function count;
use function implode;

class GiveCommand extends VanillaCommand{

	public function __construct($name){
		parent::__construct(
			$name,
			"%pocketmine.command.give.description",
			"%pocketmine.command.give.usage"
		);
		$this->setPermission("pocketmine.command.give");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		if(count($args) < 2){
			throw new InvalidCommandSyntaxException();
		}

		$player = $sender->getServer()->getPlayer($args[0]);
		$item = Item::fromString($args[1]);

		if(!isset($args[2])){
			$item->setCount($item->getMaxStackSize());
		}else{
			$item->setCount((int) $args[2]);
		}

		if(isset($args[3])){
			$tags = $exception = null;
			$data = implode(" ", array_slice($args, 3));
			try{
				$tags = JsonNBTParser::parseJSON($data);
			}catch(\Throwable $ex){
				$exception = $ex;
			}

			if(!($tags instanceof CompoundTag) or $exception !== null){
				$sender->sendMessage(new TranslationContainer("commands.give.tagError", [$exception !== null ? $exception->getMessage() : "Invalid tag conversion"]));
				return true;
			}

			$item->setNamedTag($tags);
		}

		if($player instanceof Player){
			if($item->getId() === 0){
				$sender->sendMessage(new TranslationContainer(TextFormat::RED . "%commands.give.item.notFound", [$args[1]]));

				return true;
			}

			//TODO: overflow
			$player->getInventory()->addItem(clone $item);
		}else{
			$sender->sendCommandMessage(new TranslationContainer(TextFormat::RED . "%commands.generic.player.notFound"));

			return true;
		}

		Command::broadcastCommandMessage($sender, new TranslationContainer("%commands.give.success", [
			$item->getName() . " (" . $item->getId() . ":" . $item->getDamage() . ")",
			(string) $item->getCount(),
			$player->getName()
		]));
		return true;
	}
}

