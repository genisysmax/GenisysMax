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

namespace raklib\protocol;

use pocketmine\utils\BinaryDataException;

abstract class OfflineMessage extends Packet{

	/**
	 * Magic bytes used to distinguish offline messages from loose garbage.
	 */
	private const MAGIC = "\x00\xff\xff\x00\xfe\xfe\xfe\xfe\xfd\xfd\xfd\xfd\x12\x34\x56\x78";

	/** @var string */
	protected $magic;

	/**
	 * @throws BinaryDataException
	 */
	protected function readMagic(){
		$this->magic = $this->get(16);
	}

	protected function writeMagic(){
		$this->put(self::MAGIC);
	}

	public function isValid() : bool{
		return $this->magic === self::MAGIC;
	}

}


