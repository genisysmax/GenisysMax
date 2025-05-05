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
use function file_exists;
use function file_get_contents;
use function is_dir;

class FolderPluginLoader implements PluginLoader{
	public function __construct(
		private readonly Server $server
	){}

	public function canLoadPlugin(string $path) : bool{
		return is_dir($path) and file_exists($path . "/plugin.yml") and file_exists($path . "/src/");
	}

	/**
	 * Loads the plugin contained in $file
	 */
	public function loadPlugin(string $file) : ?Plugin{
		$description = $this->getPluginDescription($file);
		if($description !== null){
            $className = $description->getMain();
            $dataFolder = dirname($file) . DIRECTORY_SEPARATOR . $description->getName();
            if(file_exists($dataFolder) and !is_dir($dataFolder)){
                trigger_error("Projected dataFolder '" . $dataFolder . "' for " . $description->getName() . " exists and is not a directory", E_USER_WARNING);

                return null;
            }
            $this->server->getLoader()->addPath($description->getSrcNamespacePrefix(), $file . "/src");

            if(class_exists($className, true)){
                $plugin = new $className();
                $this->server->getPluginManager()->initPlugin($this, $plugin, $description, $dataFolder, $file);

                return $plugin;
            }else{
                trigger_error("Couldn't load source plugin " . $description->getName() . ": main class not found", E_USER_WARNING);

                return null;
            }
		}
	}

    /**
	 * Returns the filename patterns that this loader accepts
	 *
	 * @return array|string
	 */
	public function getPluginFilters() : string{
		return "/[^\\.]/";
	}

	/**
	 * Gets the PluginDescription from the file
	 */
	public function getPluginDescription(string $file) : ?PluginDescription{
		if(is_dir($file) and file_exists($file . "/plugin.yml")){
			$yaml = @file_get_contents($file . "/plugin.yml");
			if($yaml != ""){
				return new PluginDescription($yaml);
			}
		}

		return null;
	}

	public function getAccessProtocol() : string{
		return "";
	}
}

