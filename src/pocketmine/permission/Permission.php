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

/**
 * Permission related classes
 */
namespace pocketmine\permission;

use pocketmine\Server;
use function is_array;
use function is_bool;
use function strtolower;

/**
 * Represents a permission
 */
class Permission{
	public const DEFAULT_OP = "op";
	public const DEFAULT_NOT_OP = "notop";
	public const DEFAULT_TRUE = "true";
	public const DEFAULT_FALSE = "false";

	public static $DEFAULT_PERMISSION = self::DEFAULT_OP;

	/**
	 * @param bool|string $value
	 *
	 * @return string
	 */
	public static function getByName($value) : string{
		if(is_bool($value)){
			if($value === true){
				return "true";
			}else{
				return "false";
			}
		}
		switch(strtolower($value)){
			case "op":
			case "isop":
			case "operator":
			case "isoperator":
			case "admin":
			case "isadmin":
				return self::DEFAULT_OP;

			case "!op":
			case "notop":
			case "!operator":
			case "notoperator":
			case "!admin":
			case "notadmin":
				return self::DEFAULT_NOT_OP;

			case "true":
				return self::DEFAULT_TRUE;

			default:
				return self::DEFAULT_FALSE;
		}
	}

	/** @var string */
	private $name;

	/** @var string */
	private $description;

	/**
	 * @var string[]
	 */
	private $children;

	/** @var string */
	private $defaultValue;

	/**
	 * Creates a new Permission object to be attached to Permissible objects
	 *
	 * @param string       $name
	 * @param string       $description
	 * @param string       $defaultValue
	 * @param Permission[] $children
	 */
	public function __construct(string $name, string $description = null, string $defaultValue = null, array $children = []){
		$this->name = $name;
		$this->description = $description ?? "";
		$this->defaultValue = $defaultValue ?? self::$DEFAULT_PERMISSION;
		$this->children = $children;

		$this->recalculatePermissibles();
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return $this->name;
	}

	/**
	 * @return string[]
	 */
	public function &getChildren() : array{
		return $this->children;
	}

	/**
	 * @return string
	 */
	public function getDefault() : string{
		return $this->defaultValue;
	}

	/**
	 * @param string $value
	 */
	public function setDefault(string $value){
		if($value !== $this->defaultValue){
			$this->defaultValue = $value;
			$this->recalculatePermissibles();
		}
	}

	/**
	 * @return string
	 */
	public function getDescription() : string{
		return $this->description;
	}

	/**
	 * @param string $value
	 */
	public function setDescription(string $value){
		$this->description = $value;
	}

	/**
	 * @return Permissible[]
	 */
	public function getPermissibles() : array{
		return Server::getInstance()->getPluginManager()->getPermissionSubscriptions($this->name);
	}

	public function recalculatePermissibles(){
		$perms = $this->getPermissibles();

		Server::getInstance()->getPluginManager()->recalculatePermissionDefaults($this);

		foreach($perms as $p){
			$p->recalculatePermissions();
		}
	}


	/**
	 * @param string|Permission $name
	 * @param                   $value
	 *
	 * @return Permission|null Permission if $name is a string, null if it's a Permission
	 */
	public function addParent($name, $value){
		if($name instanceof Permission){
			$name->getChildren()[$this->getName()] = $value;
			$name->recalculatePermissibles();
			return null;
		}else{
			$perm = Server::getInstance()->getPluginManager()->getPermission($name);
			if($perm === null){
				$perm = new Permission($name);
				Server::getInstance()->getPluginManager()->addPermission($perm);
			}

			$this->addParent($perm, $value);

			return $perm;
		}
	}

	/**
	 * @param array  $data
	 * @param string $default
	 *
	 * @return Permission[]
	 */
	public static function loadPermissions(array $data, string $default = self::DEFAULT_OP) : array{
		$result = [];
		foreach($data as $key => $entry){
			$result[] = self::loadPermission($key, $entry, $default, $result);
		}

		return $result;
	}

	/**
	 * @param string $name
	 * @param array  $data
	 * @param string $default
	 * @param array  $output
	 *
	 * @return Permission
	 *
	 * @throws \Exception
	 */
	public static function loadPermission(string $name, array $data, string $default = self::DEFAULT_OP, array &$output = []) : Permission{
		$desc = null;
		$children = [];
		if(isset($data["default"])){
			$value = Permission::getByName($data["default"]);
			if($value !== null){
				$default = $value;
			}else{
				throw new \InvalidStateException("'default' key contained unknown value");
			}
		}

		if(isset($data["children"])){
			if(is_array($data["children"])){
				foreach($data["children"] as $k => $v){
					if(is_array($v)){
						if(($perm = self::loadPermission($k, $v, $default, $output)) !== null){
							$output[] = $perm;
						}
					}
					$children[$k] = true;
				}
			}else{
				throw new \InvalidStateException("'children' key is of wrong type");
			}
		}

		if(isset($data["description"])){
			$desc = $data["description"];
		}

		return new Permission($name, $desc, $default, $children);

	}


}

