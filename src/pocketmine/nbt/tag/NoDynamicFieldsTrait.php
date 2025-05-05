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

namespace pocketmine\nbt\tag;

use RuntimeException;
use function get_class;

trait NoDynamicFieldsTrait{

	private function throw(string $field) : RuntimeException{
		return new RuntimeException("Cannot access dynamic field \"$field\": Dynamic field access on " . get_class($this) . " is no longer supported");
	}

	/**
	 * @param string $name
	 *
	 * @phpstan-return never
	 */
	public function __get(string $name){
		throw $this->throw($name);
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 *
	 * @phpstan-return never
	 */
	public function __set(string $name, $value){
		throw $this->throw($name);
	}

	/**
	 * @param string $name
	 *
	 * @phpstan-return never
	 */
	public function __isset(string $name){
		throw $this->throw($name);
	}

	/**
	 * @param string $name
	 *
	 * @phpstan-return never
	 */
	public function __unset(string $name){
		throw $this->throw($name);
	}
}


