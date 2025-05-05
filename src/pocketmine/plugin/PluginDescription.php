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

use pocketmine\permission\Permission;
use function array_map;
use function array_values;
use function constant;
use function defined;
use function extension_loaded;
use function is_array;
use function phpversion;
use function preg_replace;
use function str_replace;
use function stripos;
use function strlen;
use function strtoupper;
use function substr;
use function version_compare;

class PluginDescription{
	private $name;
	private $srcNamespacePrefix;
	private $main;
	private $api;
	private $extensions = [];
	private $depend = [];
	private $softDepend = [];
	private $loadBefore = [];
	/** @var string */
	private $version;
	private $commands = [];
	/** @var string */
	private $description = "";
	/** @var string[] */
	private $authors = [];
	/** @var string */
	private $website = "";
	/** @var string */
	private $prefix = "";
	private $order = PluginLoadOrder::POSTWORLD;

	/**
	 * @var Permission[]
	 */
	private $permissions = [];

	/**
	 * @param string|array $yamlString
	 */
	public function __construct($yamlString){
		$this->loadMap(!is_array($yamlString) ? \yaml_parse($yamlString) : $yamlString);
	}

	/**
	 * @param array $plugin
	 *
	 * @throws PluginException
	 */
	private function loadMap(array $plugin){
		$this->name = preg_replace("[^A-Za-z0-9 _.-]", "", $plugin["name"]);
		if($this->name === ""){
			throw new PluginException("Invalid PluginDescription name");
		}
		$this->name = str_replace(" ", "_", $this->name);
		$this->version = (string) $plugin["version"];
		$this->main = $plugin["main"];
		$this->srcNamespacePrefix = $plugin["src-namespace-prefix"] ?? "";
		$this->api = array_map(function($v){ return (string) $v; }, !is_array($plugin["api"]) ? [$plugin["api"]] : $plugin["api"]);
		if(stripos($this->main, "pocketmine\\") === 0){
			throw new PluginException("Invalid PluginDescription main, cannot start within the PocketMine namespace");
		}

		if(isset($plugin["commands"]) and is_array($plugin["commands"])){
			$this->commands = $plugin["commands"];
		}

		if(isset($plugin["depend"])){
			$this->depend = (array) $plugin["depend"];
		}
		if(isset($plugin["extensions"])){
			$extensions = (array) $plugin["extensions"];
			$isLinear = $extensions === array_values($extensions);
			foreach($extensions as $k => $v){
				if($isLinear){
					$k = $v;
					$v = "*";
				}
				$this->extensions[$k] = is_array($v) ? $v : [$v];
			}
		}
		if(isset($plugin["softdepend"])){
			$this->softDepend = (array) $plugin["softdepend"];
		}
		if(isset($plugin["loadbefore"])){
			$this->loadBefore = (array) $plugin["loadbefore"];
		}

		if(isset($plugin["website"])){
			$this->website = $plugin["website"];
		}
		if(isset($plugin["description"])){
			$this->description = $plugin["description"];
		}
		if(isset($plugin["prefix"])){
			$this->prefix = $plugin["prefix"];
		}
		if(isset($plugin["load"])){
			$order = strtoupper($plugin["load"]);
			if(!defined(PluginLoadOrder::class . "::" . $order)){
				throw new PluginException("Invalid PluginDescription load");
			}else{
				$this->order = constant(PluginLoadOrder::class . "::" . $order);
			}
		}
		$this->authors = [];
		if(isset($plugin["author"])){
			$this->authors[] = $plugin["author"];
		}
		if(isset($plugin["authors"])){
			foreach($plugin["authors"] as $author){
				$this->authors[] = $author;
			}
		}

		if(isset($plugin["permissions"])){
			$this->permissions = Permission::loadPermissions($plugin["permissions"]);
		}
	}

	/**
	 * @return string
	 */
	public function getFullName() : string{
		return $this->name . " v" . $this->version;
	}

	/**
	 * @return string
	 */
	public function getSrcNamespacePrefix() : string{
		return $this->srcNamespacePrefix;
	}

	/**
	 * @return array
	 */
	public function getCompatibleApis() : array{
		return $this->api;
	}

	/**
	 * @return string[]
	 */
	public function getAuthors() : array{
		return $this->authors;
	}

	/**
	 * @return string
	 */
	public function getPrefix() : string{
		return $this->prefix;
	}

	/**
	 * @return array
	 */
	public function getCommands() : array{
		return $this->commands;
	}

	/**
	 * @return array
	 */
	public function getRequiredExtensions() : array{
		return $this->extensions;
	}

	/**
	 * Checks if the current PHP runtime has the extensions required by the plugin.
	 *
	 * @throws PluginException if there are required extensions missing or have incompatible version, or if the version constraint cannot be parsed
	 */
	public function checkRequiredExtensions(){
		foreach($this->extensions as $name => $versionConstrs){
			if(!extension_loaded($name)){
				throw new PluginException("Required extension $name not loaded");
			}

			if(!is_array($versionConstrs)){
				$versionConstrs = [$versionConstrs];
			}
			$gotVersion = phpversion($name);
			foreach($versionConstrs as $constr){ // versionConstrs_loop
				if($constr === "*"){
					continue;
				}
				if($constr === ""){
					throw new PluginException("One of the extension version constraints of $name is empty. Consider quoting the version string in plugin.yml");
				}
				foreach(["<=", "le", "<>", "!=", "ne", "<", "lt", "==", "=", "eq", ">=", "ge", ">", "gt"] as $comparator){
					// warning: the > character should be quoted in YAML
					if(substr($constr, 0, strlen($comparator)) === $comparator){
						$version = substr($constr, strlen($comparator));
						if(!version_compare($gotVersion, $version, $comparator)){
							throw new PluginException("Required extension $name has an incompatible version ($gotVersion not $constr)");
						}
						continue 2; // versionConstrs_loop
					}
				}
				throw new PluginException("Error parsing version constraint: $constr");
			}
		}
	}

	/**
	 * @return array
	 */
	public function getDepend() : array{
		return $this->depend;
	}

	/**
	 * @return string
	 */
	public function getDescription() : string{
		return $this->description;
	}

	/**
	 * @return array
	 */
	public function getLoadBefore() : array{
		return $this->loadBefore;
	}

	/**
	 * @return string
	 */
	public function getMain() : string{
		return $this->main;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return $this->name;
	}

	/**
	 * @return int
	 */
	public function getOrder() : int{
		return $this->order;
	}

	/**
	 * @return Permission[]
	 */
	public function getPermissions() : array{
		return $this->permissions;
	}

	/**
	 * @return array
	 */
	public function getSoftDepend() : array{
		return $this->softDepend;
	}

	/**
	 * @return string
	 */
	public function getVersion() : string{
		return $this->version;
	}

	/**
	 * @return string
	 */
	public function getWebsite() : string{
		return $this->website;
	}
}


