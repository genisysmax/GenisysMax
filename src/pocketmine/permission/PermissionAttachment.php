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

namespace pocketmine\permission;

use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginException;

class PermissionAttachment{
	/** @var PermissionRemovedExecutor */
	private $removed = null;

	/**
	 * @var bool[]
	 */
	private $permissions = [];

	/** @var Permissible */
	private $permissible;

	/** @var Plugin */
	private $plugin;

	/**
	 * @param Plugin      $plugin
	 * @param Permissible $permissible
	 *
	 * @throws PluginException
	 */
	public function __construct(Plugin $plugin, Permissible $permissible){
		if(!$plugin->isEnabled()){
			throw new PluginException("Plugin " . $plugin->getDescription()->getName() . " is disabled");
		}

		$this->permissible = $permissible;
		$this->plugin = $plugin;
	}

	/**
	 * @return Plugin
	 */
	public function getPlugin() : Plugin{
		return $this->plugin;
	}

	/**
	 * @param PermissionRemovedExecutor $ex
	 */
	public function setRemovalCallback(PermissionRemovedExecutor $ex){
		$this->removed = $ex;
	}

	/**
	 * @return PermissionRemovedExecutor|null
	 */
	public function getRemovalCallback(){
		return $this->removed;
	}

	/**
	 * @return Permissible
	 */
	public function getPermissible() : Permissible{
		return $this->permissible;
	}

	/**
	 * @return bool[]
	 */
	public function getPermissions() : array{
		return $this->permissions;
	}

	public function clearPermissions(){
		$this->permissions = [];
		$this->permissible->recalculatePermissions();
	}

	/**
	 * @param bool[] $permissions
	 */
	public function setPermissions(array $permissions){
		foreach($permissions as $key => $value){
			$this->permissions[$key] = (bool) $value;
		}
		$this->permissible->recalculatePermissions();
	}

	/**
	 * @param string[] $permissions
	 */
	public function unsetPermissions(array $permissions){
		foreach($permissions as $node){
			unset($this->permissions[$node]);
		}
		$this->permissible->recalculatePermissions();
	}

	/**
	 * @param string|Permission $name
	 * @param bool              $value
	 */
	public function setPermission($name, bool $value){
		$name = $name instanceof Permission ? $name->getName() : $name;
		if(isset($this->permissions[$name])){
			if($this->permissions[$name] === $value){
				return;
			}
			unset($this->permissions[$name]); //Fixes children getting overwritten
		}
		$this->permissions[$name] = $value;
		$this->permissible->recalculatePermissions();
	}

	/**
	 * @param string|Permission $name
	 */
	public function unsetPermission($name){
		$name = $name instanceof Permission ? $name->getName() : $name;
		if(isset($this->permissions[$name])){
			unset($this->permissions[$name]);
			$this->permissible->recalculatePermissions();
		}
	}

	/**
	 * @return void
	 */
	public function remove(){
		$this->permissible->removeAttachment($this);
	}
}

