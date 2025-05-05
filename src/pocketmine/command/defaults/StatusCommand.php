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
use pocketmine\thread\ThreadManager;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;
use function count;
use function floor;
use function microtime;
use function number_format;
use function round;

class StatusCommand extends VanillaCommand{

	public function __construct($name){
		parent::__construct(
			$name,
			"%pocketmine.command.status.description",
			"%pocketmine.command.status.usage"
		);
		$this->setPermission("pocketmine.command.status");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		$rUsage = Utils::getRealMemoryUsage();
		$mUsage = Utils::getMemoryUsage(true);

		$server = $sender->getServer();
		$sender->sendMessage(TextFormat::GREEN . "---- " . TextFormat::WHITE . "Server status" . TextFormat::GREEN . " ----");

		$time = microtime(true) - \pocketmine\START_TIME;

		$seconds = floor((int)$time % 60);
		$minutes = null;
		$hours = null;
		$days = null;

		if($time >= 60){
			$minutes = floor(((int)$time % 3600) / 60);
			if($time >= 3600){
				$hours = floor(((int)$time % (3600 * 24)) / 3600);
				if($time >= 3600 * 24){
					$days = floor((int)$time / (3600 * 24));
				}
			}
		}

		$uptime = ($minutes !== null ?
				($hours !== null ?
					($days !== null ?
						"$days days "
					: "") . "$hours hours "
					: "") . "$minutes minutes "
			: "") . "$seconds seconds";
		
		        $onlineCount = 0;
			foreach($sender->getServer()->getOnlinePlayers() as $player){
				if($player->isOnline() and (!($sender instanceof Player) or $sender->canSee($player))){
					++$onlineCount;
				}
			}

		$sender->sendMessage(TextFormat::GOLD . "Uptime: " . TextFormat::RED . $uptime);

		$tpsColor = TextFormat::GREEN;
		if($server->getTicksPerSecond() < 17){
			$tpsColor = TextFormat::GOLD;
		}elseif($server->getTicksPerSecond() < 12){
			$tpsColor = TextFormat::RED;
		}

		$sender->sendMessage(TextFormat::GOLD . "Current TPS: {$tpsColor}{$server->getTicksPerSecond()} ({$server->getTickUsage()}%)");
		$sender->sendMessage(TextFormat::GOLD . "Average TPS: {$tpsColor}{$server->getTicksPerSecondAverage()} ({$server->getTickUsageAverage()}%)");

		$sender->sendMessage(TextFormat::GOLD . "Online:" . TextFormat::GREEN . " " . $onlineCount . "/" . $sender->getServer()->getMaxPlayers());

		$sender->sendMessage(TextFormat::GOLD . "Network upload: " . TextFormat::RED . round($server->getNetwork()->getUpload() / 1024, 2) . " kB/s");
		$sender->sendMessage(TextFormat::GOLD . "Network download: " . TextFormat::RED . round($server->getNetwork()->getDownload() / 1024, 2) . " kB/s");

		$sender->sendMessage(TextFormat::GOLD . "Thread count: " . TextFormat::RED . Utils::getThreadCount());

		$sender->sendMessage(TextFormat::GOLD . "Main thread memory: " . TextFormat::RED . number_format(round(($mUsage[0] / 1024) / 1024, 2)) . " MB.");
		$sender->sendMessage(TextFormat::GOLD . "Total memory: " . TextFormat::RED . number_format(round(($mUsage[1] / 1024) / 1024, 2)) . " MB.");
		$sender->sendMessage(TextFormat::GOLD . "Total virtual memory: " . TextFormat::RED . number_format(round(($mUsage[2] / 1024) / 1024, 2)) . " MB.");
		$sender->sendMessage(TextFormat::GOLD . "Heap memory: " . TextFormat::RED . number_format(round(($rUsage[0] / 1024) / 1024, 2)) . " MB.");
		$sender->sendMessage(TextFormat::GOLD . "Maximum memory (system): " . TextFormat::RED . number_format(round(($mUsage[2] / 1024) / 1024, 2)) . " MB.");

		if($server->getProperty("memory.global-limit") > 0){
			$sender->sendMessage(TextFormat::GOLD . "Maximum memory (manager): " . TextFormat::RED . number_format(round($server->getProperty("memory.global-limit"), 2)) . " MB.");
		}

		foreach($server->getLevels() as $level){
			$levelName = $level->getFolderName() !== $level->getName() ? " (" . $level->getName() . ")" : "";
			$timeColor = ($level->getTickRate() > 1 or $level->getTickRateTime() > 40) ? TextFormat::RED : TextFormat::YELLOW;
			$tickRate = $level->getTickRate() > 1 ? " (tick rate " . $level->getTickRate() . ")" : "";
			$sender->sendMessage(TextFormat::GOLD . "World \"{$level->getFolderName()}\"$levelName: " .
				TextFormat::RED . number_format(count($level->getChunks())) . TextFormat::GREEN . " chunks, " .
				TextFormat::RED . number_format(count($level->getEntities())) . TextFormat::GREEN . " entities, " .
				TextFormat::RED . number_format(count($level->getTiles())) . TextFormat::GREEN . " tiles. " .
				"Time $timeColor" . round($level->getTickRateTime(), 2) . "ms" . $tickRate
			);
		}

        $sender->sendMessage(TextFormat::GOLD . 'Thread lists:');
        $threadList = ThreadManager::getInstance()->getAll();
        foreach($threadList as $thread){
            $sender->sendMessage(TextFormat::GOLD . '- '. $thread->getThreadName());
        }

		return true;
	}
}


