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


namespace pocketmine\resourcepacks;


use InvalidArgumentException;
use pocketmine\Server;
use pocketmine\utils\Config;
use function array_keys;
use function copy;
use function file_exists;
use function is_dir;
use function mkdir;
use const DIRECTORY_SEPARATOR;

class ResourcePackManager{

	/** @var Server */
	private $server;
	/** @var string */
	private $path;
	/** @var Config */
	private $resourcePacksConfig;
	/** @var bool */
	private $serverForceResources = false;
	/** @var ResourcePack[] */
	private $resourcePacks = [];
	/** @var ResourcePack[] */
	private $uuidList = [];

	/**
	 * @param Server $server
	 * @param string $path Path to resource-packs directory.
	 */
	public function __construct(Server $server, string $path){
		$this->server = $server;
		$this->path = $path;

		if(!file_exists($this->path)){
			$this->server->getLogger()->debug("Resource packs path $path does not exist, creating directory");
			mkdir($this->path);
		}elseif(!is_dir($this->path)){
			throw new \InvalidArgumentException("Resource packs path $path exists and is not a directory");
		}

		if(!file_exists($this->path . "resource_packs.yml")){
			copy(\pocketmine\RESOURCE_PATH . "resource_packs.yml", $this->path . "resource_packs.yml");
		}

		$this->resourcePacksConfig = new Config($this->path . "resource_packs.yml", Config::YAML, []);

		$this->serverForceResources = (bool) $this->resourcePacksConfig->get("force_resources", false);

		foreach($this->resourcePacksConfig->get("resource_stack", []) as $pack){
			try{
				$this->loadPack($this->path . DIRECTORY_SEPARATOR . $pack);
			}catch(\Throwable $e){
				$this->server->getLogger()->logException($e);
			}
		}
	}

	public function loadPack(string $packPath) : void{
		if(file_exists($packPath)){
			//Detect the type of resource pack.
			if(is_dir($packPath)){
				throw new InvalidArgumentException("Can't load pack $packPath: directory resource packs currently unsupported");
			}else{
				$info = new \SplFileInfo($packPath);
				switch($info->getExtension()){
					case "zip":
					case "mcpack":
						$newPack = new ZippedResourcePack($packPath);
						break;
					default:
						throw new InvalidArgumentException("Can't load pack $packPath: format not recognized");
				}
			}

			$this->resourcePacks[] = $newPack;
			$this->uuidList[$newPack->getPackId()] = $newPack;
		}else{
			throw new InvalidArgumentException("Can't load pack $packPath: file or directory not found");
		}
	}

	public function addPack(ResourcePack $pack) : void{
		$this->resourcePacks[] = $pack;
		$this->uuidList[$pack->getPackId()] = $pack;
	}

	/**
	 * Returns whether players must accept resource packs in order to join.
	 * @return bool
	 */
	public function resourcePacksRequired() : bool{
		return $this->serverForceResources;
	}

	/**
	 * @param bool $serverForceResources
	 */
	public function setResourcePacksRequired(bool $serverForceResources) : void{
		$this->serverForceResources = $serverForceResources;
	}

	/**
	 * Returns an array of resource packs in use, sorted in order of priority.
	 * @return ResourcePack[]
	 */
	public function getResourceStack() : array{
		return $this->resourcePacks;
	}

	/**
	 * Returns the resource pack matching the specified UUID string, or null if the ID was not recognized.
	 *
	 * @param string $id
	 * @return ResourcePack|null
	 */
	public function getPackById(string $id){
		return $this->uuidList[$id] ?? null;
	}

	/**
	 * Returns an array of pack IDs for packs currently in use.
	 * @return string[]
	 */
	public function getPackIdList() : array{
		return array_keys($this->uuidList);
	}
}

