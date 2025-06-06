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
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\event\TimingsHandler;
use pocketmine\event\TranslationContainer;
use pocketmine\Player;
use pocketmine\scheduler\BulkCurlTask;
use pocketmine\Server;
use pocketmine\utils\InternetException;
use function count;
use function fclose;
use function file_exists;
use function fopen;
use function fseek;
use function http_build_query;
use function is_array;
use function json_decode;
use function mkdir;
use function stream_get_contents;
use function strtolower;
use const CURLOPT_AUTOREFERER;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;

class TimingsCommand extends VanillaCommand{

	public static $timingStart = 0;

	public function __construct(string $name){
		parent::__construct(
			$name,
			"%pocketmine.command.timings.description",
			"%pocketmine.command.timings.usage"
		);
		$this->setPermission("pocketmine.command.timings");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		if(count($args) !== 1){
			throw new InvalidCommandSyntaxException();
		}

		$mode = strtolower($args[0]);

		if($mode === "on"){
			$sender->getServer()->getPluginManager()->setUseTimings(true);
			TimingsHandler::reload();
			$sender->sendMessage(new TranslationContainer("pocketmine.command.timings.enable"));

			return true;
		}elseif($mode === "off"){
			$sender->getServer()->getPluginManager()->setUseTimings(false);
			$sender->sendMessage(new TranslationContainer("pocketmine.command.timings.disable"));
			return true;
		}

		if(!$sender->getServer()->getPluginManager()->useTimings()){
			$sender->sendMessage(new TranslationContainer("pocketmine.command.timings.timingsDisabled"));

			return true;
		}

		$paste = $mode === "paste";

		if($mode === "reset"){
			TimingsHandler::reload();
			$sender->sendMessage(new TranslationContainer("pocketmine.command.timings.reset"));
		}elseif($mode === "merged" or $mode === "report" or $paste){
			$timings = "";
			if($paste){
				$fileTimings = fopen("php://temp", "r+b");
			}else{
				$index = 0;
				$timingFolder = $sender->getServer()->getDataPath() . "timings/";

				if(!file_exists($timingFolder)){
					mkdir($timingFolder, 0777);
				}
				$timings = $timingFolder . "timings.txt";
				while(file_exists($timings)){
					$timings = $timingFolder . "timings" . (++$index) . ".txt";
				}

				$fileTimings = fopen($timings, "a+b");
			}
			TimingsHandler::printTimings($fileTimings);

			if($paste){
				fseek($fileTimings, 0);
				$data = [
					"browser" => $agent = $sender->getServer()->getName() . " " . $sender->getServer()->getPocketMineVersion(),
					"data" => $content = stream_get_contents($fileTimings)
				];
				fclose($fileTimings);

				$host = $sender->getServer()->getProperty("timings.host", "timings.pmmp.io");

				$sender->getServer()->getScheduler()->scheduleAsyncTask(new class($sender, $host, $agent, $data) extends BulkCurlTask{
					/** @var string */
					private $host;

					/**
					 * @param CommandSender $sender
					 * @param string $host
					 * @param string $agent
					 * @param string[] $data
					 */
					public function __construct(CommandSender $sender, string $host, string $agent, array $data){
						parent::__construct([
							[
								"page" => "https://$host?upload=true",
								"extraOpts" => [
									CURLOPT_HTTPHEADER => [
										"User-Agent: $agent",
										"Content-Type: application/x-www-form-urlencoded"
									],
									CURLOPT_POST => true,
									CURLOPT_POSTFIELDS => http_build_query($data),
									CURLOPT_AUTOREFERER => false,
									CURLOPT_FOLLOWLOCATION => false
								]
							]
						], $sender);
						$this->host = $host;
					}

					public function onCompletion(Server $server){
						$sender = $this->fetchLocal();
						if($sender instanceof Player and !$sender->isOnline()){ // TODO replace with a more generic API method for checking availability of CommandSender
							return;
						}
						$result = $this->getResult()[0];
						if($result instanceof InternetException){
							$server->getLogger()->logException($result);
							return;
						}
						if(isset($result[0]) && is_array($response = json_decode($result[0], true)) && isset($response["id"])){
							$sender->sendMessage(new TranslationContainer("pocketmine.command.timings.timingsRead",
								["https://" . $this->host . "/?id=" . $response["id"]]));
						}else{
							$sender->sendMessage(new TranslationContainer("pocketmine.command.timings.pasteError"));
						}
					}
				});
			}else{
				fclose($fileTimings);
				$sender->sendMessage(new TranslationContainer("pocketmine.command.timings.timingsWrite", [$timings]));
			}
		}

		return true;
	}
}

