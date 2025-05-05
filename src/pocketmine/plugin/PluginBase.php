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

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\event\Listener;
use pocketmine\Server;
use pocketmine\utils\Config;
use function dirname;
use function fclose;
use function file_exists;
use function fopen;
use function is_dir;
use function mkdir;
use function rtrim;
use function str_replace;
use function stream_copy_to_stream;
use function stream_get_contents;
use function strpos;
use function strtolower;
use function trim;
use function yaml_parse;

abstract class PluginBase implements Plugin{

	/** @var PluginLoader */
	private $loader;

	/** @var Server */
	private $server;

	/** @var PluginManager */
	private $pluginManager;

	/** @var bool */
	private $isEnabled = false;

	/** @var bool */
	private $initialized = false;

	/** @var PluginDescription */
	private $description;

	/** @var string */
	private $dataFolder;
	/** @var Config|null */
	private $config = null;
	/** @var string */
	private $configFile;
	/** @var string */
	private $file;

	/** @var PluginLogger */
	private $logger;

	/**
	 * Called when the plugin is loaded, before calling onEnable()
	 */
	public function onLoad(){

	}

	public function onEnable(){

	}

	public function onDisable(){

	}

	/**
	 * @return bool
	 */
	final public function isEnabled() : bool{
		return $this->isEnabled === true;
	}

	/**
	 * @param bool $boolean
	 */
	final public function setEnabled(bool $boolean = true){
		if($this->isEnabled !== $boolean){
			$this->isEnabled = $boolean;
			if($this->isEnabled === true){
				$this->onEnable();
			}else{
				$this->onDisable();
			}
		}
	}

	/**
	 * @return bool
	 */
	final public function isDisabled() : bool{
		return $this->isEnabled === false;
	}

	final public function getDataFolder() : string{
		return $this->dataFolder;
	}

	final public function getDescription() : PluginDescription{
		return $this->description;
	}

	final public function init(PluginLoader $loader, Server $server, PluginDescription $description, $dataFolder, $file){
		if($this->initialized === false){
			$this->initialized = true;
			$this->loader = $loader;
			$this->server = $server;
			$this->pluginManager = $server->getPluginManager();
			$this->description = $description;
			$this->dataFolder = rtrim($dataFolder, "\\/") . "/";
			$this->file = rtrim($file, "\\/") . "/";
			$this->configFile = $this->dataFolder . "config.yml";
			$prefix = $this->getDescription()->getPrefix();
			$this->logger = new PluginLogger($server->getLogger(), $prefix !== "" ? $prefix : $this->getName());
		}
	}

	/**
	 * @return PluginLogger
	 */
	public function getLogger() : PluginLogger{
		return $this->logger;
	}

	/**
	 * @return bool
	 */
	final public function isInitialized() : bool{
		return $this->initialized;
	}

	/**
	 * @param string $name
	 *
	 * @return Command|PluginIdentifiableCommand|null
	 */
	public function getCommand(string $name){
		$command = $this->getServer()->getPluginCommand($name);
		if($command === null or $command->getPlugin() !== $this){
			$command = $this->getServer()->getPluginCommand(strtolower($this->description->getName()) . ":" . $name);
		}

		if($command instanceof PluginIdentifiableCommand and $command->getPlugin() === $this){
			return $command;
		}else{
			return null;
		}
	}

	/**
	 * @param CommandSender $sender
	 * @param Command       $command
	 * @param string        $label
	 * @param string[]      $args
	 *
	 * @return bool
	 */
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		return false;
	}

	/**
	 * @return bool
	 */
	protected function isPhar() : bool{
		return strpos($this->file, "phar://") === 0;
	}

	/**
	 * Gets an embedded resource on the plugin file.
	 * WARNING: You must close the resource given using fclose()
	 *
	 * @param string $filename
	 *
	 * @return null|resource Resource data, or null
	 */
	public function getResource(string $filename){
		$filename = rtrim(str_replace("\\", "/", $filename), "/");
		if(file_exists($this->file . "resources/" . $filename)){
			return fopen($this->file . "resources/" . $filename, "rb");
		}

		return null;
	}

	/**
	 * @param string $filename
	 * @param bool $replace
	 *
	 * @return bool
	 */
	public function saveResource(string $filename, bool $replace = false) : bool{
		if(trim($filename) === ""){
			return false;
		}

		if(($resource = $this->getResource($filename)) === null){
			return false;
		}

		$out = $this->dataFolder . $filename;
		if(!file_exists(dirname($out))){
			mkdir(dirname($out), 0755, true);
		}

		if(file_exists($out) and $replace !== true){
			return false;
		}

		$ret = stream_copy_to_stream($resource, $fp = fopen($out, "wb")) > 0;
		fclose($fp);
		fclose($resource);
		return $ret;
	}

	/**
	 * Returns all the resources packaged with the plugin
	 *
	 * @return string[]
	 */
	public function getResources() : array{
		$resources = [];
		if(is_dir($this->file . "resources/")){
			foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->file . "resources/")) as $resource){
				$resources[] = $resource;
			}
		}

		return $resources;
	}

	/**
	 * @return Config
	 */
	public function getConfig() : Config{
		if($this->config === null){
			$this->reloadConfig();
		}

		return $this->config;
	}

	public function saveConfig(){
		if($this->getConfig()->save() === false){
			$this->getLogger()->critical("Could not save config to " . $this->configFile);
		}
	}

	public function saveDefaultConfig() : bool{
		if(!file_exists($this->configFile)){
			return $this->saveResource("config.yml", false);
		}
		return false;
	}

	public function reloadConfig(){
		$this->config = new Config($this->configFile);
		if(($configStream = $this->getResource("config.yml")) !== null){
			$this->config->setDefaults(yaml_parse(Config::fixYAMLIndexes(stream_get_contents($configStream))));
			fclose($configStream);
		}
	}

	/**
	 * @return Server
	 */
	final public function getServer() : Server{
		return $this->server;
	}

	/**
	 * @return string
	 */
	final public function getName() : string{
		return $this->description->getName();
	}

	/**
	 * @return string
	 */
	final public function getFullName() : string{
		return $this->description->getFullName();
	}

	/**
	 * @return string
	 */
	protected function getFile() : string{
		return $this->file;
	}

	/**
	 * @return PluginLoader
	 */
	public function getPluginLoader(){
		return $this->loader;
	}

	public function registerEvents(Listener $listener) : void{
		$this->pluginManager->registerEvents($listener, $this);
	}

}


