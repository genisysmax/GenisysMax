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


class PermissionAttachmentInfo{
	/** @var Permissible */
	private $permissible;

	/** @var string */
	private $permission;

	/** @var PermissionAttachment|null */
	private $attachment;

	/** @var bool */
	private $value;

	/**
	 * @param Permissible               $permissible
	 * @param string                    $permission
	 * @param PermissionAttachment|null $attachment
	 * @param bool                      $value
	 *
	 * @throws \InvalidStateException
	 */
	public function __construct(Permissible $permissible, string $permission, PermissionAttachment $attachment = null, bool $value){
		$this->permissible = $permissible;
		$this->permission = $permission;
		$this->attachment = $attachment;
		$this->value = $value;
	}

	/**
	 * @return Permissible
	 */
	public function getPermissible() : Permissible{
		return $this->permissible;
	}

	/**
	 * @return string
	 */
	public function getPermission() : string{
		return $this->permission;
	}

	/**
	 * @return PermissionAttachment|null
	 */
	public function getAttachment(){
		return $this->attachment;
	}

	/**
	 * @return bool
	 */
	public function getValue() : bool{
		return $this->value;
	}
}

