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

use pocketmine\event\Timings;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginException;
use pocketmine\Server;
use function array_keys;
use function spl_object_id;

class PermissibleBase implements Permissible{
	/** @var ServerOperator */
	private $opable = null;

	/** @var Permissible */
	private $parent = null;

	/**
	 * @var PermissionAttachment[]
	 */
	private $attachments = [];

	/**
	 * @var PermissionAttachmentInfo[]
	 */
	private $permissions = [];

	/**
	 * @param ServerOperator $opable
	 */
	public function __construct(ServerOperator $opable){
		$this->opable = $opable;
		if($opable instanceof Permissible){
			$this->parent = $opable;
		}
	}

	public function __destruct(){
		$this->parent = null;
		$this->opable = null;
	}

	/**
	 * @return bool
	 */
	public function isOp() : bool{
		if($this->opable === null){
			return false;
		}else{
			return $this->opable->isOp();
		}
	}

	/**
	 * @param bool $value
	 *
	 * @throws \Exception
	 */
	public function setOp(bool $value){
		if($this->opable === null){
			throw new \LogicException("Cannot change op value as no ServerOperator is set");
		}else{
			$this->opable->setOp($value);
		}
	}

	/**
	 * @param Permission|string $name
	 *
	 * @return bool
	 */
	public function isPermissionSet($name) : bool{
		return isset($this->permissions[$name instanceof Permission ? $name->getName() : $name]);
	}

	/**
	 * @param Permission|string $name
	 *
	 * @return bool
	 */
	public function hasPermission(Permission|string $name) : bool{
		if($name instanceof Permission){
			$name = $name->getName();
		}

		if($this->isPermissionSet($name)){
			return $this->permissions[$name]->getValue();
		}

		if(($perm = Server::getInstance()->getPluginManager()->getPermission($name)) !== null){
			$perm = $perm->getDefault();

			return $perm === Permission::DEFAULT_TRUE or ($this->isOp() and $perm === Permission::DEFAULT_OP) or (!$this->isOp() and $perm === Permission::DEFAULT_NOT_OP);
		}else{
			return Permission::$DEFAULT_PERMISSION === Permission::DEFAULT_TRUE or ($this->isOp() and Permission::$DEFAULT_PERMISSION === Permission::DEFAULT_OP) or (!$this->isOp() and Permission::$DEFAULT_PERMISSION === Permission::DEFAULT_NOT_OP);
		}

	}

	/**
	 * //TODO: tick scheduled attachments
	 *
	 * @param Plugin $plugin
	 * @param string $name
	 * @param bool $value
	 *
	 * @return PermissionAttachment
	 */
	public function addAttachment(Plugin $plugin, string $name = null, bool $value = null) : PermissionAttachment{
		if(!$plugin->isEnabled()){
			throw new PluginException("Plugin " . $plugin->getDescription()->getName() . " is disabled");
		}

		$result = new PermissionAttachment($plugin, $this->parent ?? $this);
		$this->attachments[spl_object_id($result)] = $result;
		if($name !== null and $value !== null){
			$result->setPermission($name, $value);
		}

		$this->recalculatePermissions();

		return $result;
	}

	/**
	 * @param PermissionAttachment $attachment
	 */
	public function removeAttachment(PermissionAttachment $attachment){
		if(isset($this->attachments[spl_object_id($attachment)])){
			unset($this->attachments[spl_object_id($attachment)]);
			if(($ex = $attachment->getRemovalCallback()) !== null){
				$ex->attachmentRemoved($attachment);
			}

			$this->recalculatePermissions();

		}

	}

	public function recalculatePermissions(){
		Timings::$permissibleCalculationTimer->startTiming();

		$this->clearPermissions();
		$defaults = Server::getInstance()->getPluginManager()->getDefaultPermissions($this->isOp());
		Server::getInstance()->getPluginManager()->subscribeToDefaultPerms($this->isOp(), $this->parent ?? $this);

		foreach($defaults as $perm){
			$name = $perm->getName();
			$this->permissions[$name] = new PermissionAttachmentInfo($this->parent ?? $this, $name, null, true);
			Server::getInstance()->getPluginManager()->subscribeToPermission($name, $this->parent ?? $this);
			$this->calculateChildPermissions($perm->getChildren(), false, null);
		}

		foreach($this->attachments as $attachment){
			$this->calculateChildPermissions($attachment->getPermissions(), false, $attachment);
		}

		Timings::$permissibleCalculationTimer->stopTiming();
	}

	public function clearPermissions(){
		$pluginManager = Server::getInstance()->getPluginManager();
		foreach(array_keys($this->permissions) as $name){
			$pluginManager->unsubscribeFromPermission($name, $this->parent ?? $this);
		}

		$pluginManager->unsubscribeFromDefaultPerms(false, $this->parent ?? $this);
		$pluginManager->unsubscribeFromDefaultPerms(true, $this->parent ?? $this);

		$this->permissions = [];
	}

	/**
	 * @param bool[]               $children
	 * @param bool                 $invert
	 * @param PermissionAttachment $attachment
	 */
	private function calculateChildPermissions(array $children, $invert, $attachment){
		foreach($children as $name => $v){
			$perm = Server::getInstance()->getPluginManager()->getPermission($name);
			$value = ($v xor $invert);
			$this->permissions[$name] = new PermissionAttachmentInfo($this->parent ?? $this, $name, $attachment, $value);
			Server::getInstance()->getPluginManager()->subscribeToPermission($name, $this->parent ?? $this);

			if($perm instanceof Permission){
				$this->calculateChildPermissions($perm->getChildren(), !$value, $attachment);
			}
		}
	}

	/**
	 * @return PermissionAttachmentInfo[]
	 */
	public function getEffectivePermissions() : array{
		return $this->permissions;
	}
}


