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

namespace pocketmine\plugin;

use pocketmine\Server;
use function class_exists;
use function dirname;
use function file_exists;
use function is_dir;
use const DIRECTORY_SEPARATOR;

/**
 * Handles different types of plugins
 */
class PharPluginLoader implements PluginLoader{

	/**
	 * @param Server $server
	 */
	public function __construct(
		private Server $server
	){}

	/**
	 * Loads the plugin contained in $file
	 *
	 * @param string $file
	 *
	 * @return Plugin|null
	 */
	public function loadPlugin(string $file){
		if(($description = $this->getPluginDescription($file)) instanceof PluginDescription){
			$this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.plugin.load", [$description->getFullName()]));
			$dataFolder = dirname($file) . DIRECTORY_SEPARATOR . $description->getName();
			if(file_exists($dataFolder) and !is_dir($dataFolder)){
				throw new \InvalidStateException("Projected dataFolder '" . $dataFolder . "' for " . $description->getName() . " exists and is not a directory");
			}
			$file = "phar://$file";
			$className = $description->getMain();
			$this->server->getLoader()->addPath($description->getSrcNamespacePrefix(), "$file/src");

			if(class_exists($className, true)){
				$plugin = new $className();
				$this->server->getPluginManager()->initPlugin($this, $plugin, $description, $dataFolder, $file);

				return $plugin;
			}else{
				throw new PluginException("Couldn't load plugin " . $description->getName() . ": main class not found");
			}
		}

		return null;
	}

	/**
	 * Gets the PluginDescription from the file
	 *
	 * @param string $file
	 *
	 * @return null|PluginDescription
	 */
	public function getPluginDescription(string $file){
		$phar = new \Phar($file);
		if(isset($phar["plugin.yml"])){
			$pluginYml = $phar["plugin.yml"];
			if($pluginYml instanceof \PharFileInfo){
				return new PluginDescription($pluginYml->getContent());
			}
		}

		return null;
	}

	/**
	 * Returns the filename patterns that this loader accepts
	 *
	 * @return string
	 */
	public function getPluginFilters() : string{
		return "/\\.phar$/i";
	}
}

