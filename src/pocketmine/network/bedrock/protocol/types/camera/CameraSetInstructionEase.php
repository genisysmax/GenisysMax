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

namespace pocketmine\network\bedrock\protocol\types\camera;

use pocketmine\network\bedrock\protocol\DataPacket;

final class CameraSetInstructionEase{

	/**
	 * @see CameraSetInstructionEaseType
	 */
	public function __construct(
		private int $type,
		private float $duration
	){}

	/**
	 * @see CameraSetInstructionEaseType
	 */
	public function getType() : int{ return $this->type; }

	public function getDuration() : float{ return $this->duration; }

	public static function read(DataPacket $in) : self{
		$type = $in->getByte();
		$duration = $in->getLFloat();
		return new self($type, $duration);
	}

	public function write(DataPacket $out) : void{
		$out->putByte($this->type);
		$out->putLFloat($this->duration);
	}
}


