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
use pocketmine\event\TranslationContainer;
use pocketmine\network\bedrock\adapter\v388\protocol\ProtocolInfo;
use pocketmine\network\bedrock\protocol\ProtocolInfo as MCBEProtocol;
use pocketmine\network\mcpe\protocol\ProtocolInfo as MCPEProtocol;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use function count;
use function implode;
use function stripos;
use function strtolower;
use const pocketmine\DEVELOPERS;

class VersionCommand extends VanillaCommand{

	public function __construct($name){
		parent::__construct(
			$name,
			"%pocketmine.command.version.description",
			"%pocketmine.command.version.usage",
			["ver", "about"]
		);
		$this->setPermission("pocketmine.command.version");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		if(count($args) === 0){
			$server = $sender->getServer();
			$messages = [
				new TranslationContainer('pocketmine.server.info.extended.title', []),
				new TranslationContainer('pocketmine.server.info.extended.main', [TextFormat::GREEN . $server->getName(), TextFormat::GREEN . $server->getPocketMineVersion(), TextFormat::GREEN . implode(", ", DEVELOPERS)]),
				new TranslationContainer('pocketmine.server.info.extended.php', [TextFormat::GREEN . phpversion(), TextFormat::GREEN . phpversion('pmmpthread')]),
				new TranslationContainer('pocketmine.server.info.extended.api', [TextFormat::GREEN . $server->getApiVersion()]),
				new TranslationContainer('pocketmine.server.info.extended.version', [TextFormat::GREEN . $server->getVersion() . ", " . ProtocolInfo::MINECRAFT_VERSION . " - " . $server->getBedrockVersion()]),
				new TranslationContainer('pocketmine.server.info.extended.protocols', [TextFormat::GREEN . MCPEProtocol::CURRENT_PROTOCOL . ", " . ProtocolInfo::CURRENT_PROTOCOL . " - " . MCBEProtocol::CURRENT_PROTOCOL]),
			];

			foreach ($messages as $message) {
				$sender->sendMessage($message);
			}
		}else{
			$pluginName = implode(" ", $args);
			$exactPlugin = $sender->getServer()->getPluginManager()->getPlugin($pluginName);

			if($exactPlugin instanceof Plugin){
				$this->describeToSender($exactPlugin, $sender);

				return true;
			}

			$found = false;
			$pluginName = strtolower($pluginName);
			foreach($sender->getServer()->getPluginManager()->getPlugins() as $plugin){
				if(stripos($plugin->getName(), $pluginName) !== false){
					$this->describeToSender($plugin, $sender);
					$found = true;
				}
			}

			if(!$found){
				$sender->sendMessage(new TranslationContainer("pocketmine.command.version.noSuchPlugin"));
			}
		}

		return true;
	}

	private function describeToSender(Plugin $plugin, CommandSender $sender){
		$desc = $plugin->getDescription();
		$sender->sendMessage(TextFormat::DARK_GREEN . $desc->getName() . TextFormat::WHITE . " version " . TextFormat::DARK_GREEN . $desc->getVersion());

		if($desc->getDescription() != null){
			$sender->sendMessage($desc->getDescription());
		}

		if($desc->getWebsite() != null){
			$sender->sendMessage("Website: " . $desc->getWebsite());
		}

		if(count($authors = $desc->getAuthors()) > 0){
			if(count($authors) === 1){
				$sender->sendMessage("Author: " . implode(", ", $authors));
			}else{
				$sender->sendMessage("Authors: " . implode(", ", $authors));
			}
		}
	}
}

