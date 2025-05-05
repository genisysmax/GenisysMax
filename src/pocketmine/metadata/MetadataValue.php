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

namespace pocketmine\metadata;

use pocketmine\plugin\Plugin;

abstract class MetadataValue{
	/** @var Plugin */
	private $owningPlugin;

	protected function __construct(Plugin $owningPlugin){
		$this->owningPlugin = $owningPlugin;
	}

	/**
	 * @return Plugin
	 */
	public function getOwningPlugin(){
		return $this->owningPlugin;
	}

	/**
	 * Fetches the value of this metadata item.
	 *
	 * @return mixed
	 */
	abstract public function value();

	/**
	 * Invalidates this metadata item, forcing it to recompute when next
	 * accessed.
	 */
	abstract public function invalidate();
}

