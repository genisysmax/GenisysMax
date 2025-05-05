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

final class CameraSetInstructionRotation{

	public function __construct(
		private float $pitch,
		private float $yaw,
	){}

	public function getPitch() : float{ return $this->pitch; }

	public function getYaw() : float{ return $this->yaw; }

	public static function read(DataPacket $in) : self{
		$pitch = $in->getLFloat();
		$yaw = $in->getLFloat();
		return new self($pitch, $yaw);
	}

	public function write(DataPacket $out) : void{
		$out->putLFloat($this->pitch);
		$out->putLFloat($this->yaw);
	}
}


