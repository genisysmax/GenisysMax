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

use pocketmine\math\Vector3;
use pocketmine\network\bedrock\protocol\DataPacket;

final class CameraSetInstruction{

	public function __construct(
		private int $preset,
		private ?CameraSetInstructionEase $ease,
		private ?Vector3 $cameraPosition,
		private ?CameraSetInstructionRotation $rotation,
		private ?Vector3 $facingPosition,
		private ?bool $default
	){}

	public function getPreset() : int{ return $this->preset; }

	public function getEase() : ?CameraSetInstructionEase{ return $this->ease; }

	public function getCameraPosition() : ?Vector3{ return $this->cameraPosition; }

	public function getRotation() : ?CameraSetInstructionRotation{ return $this->rotation; }

	public function getFacingPosition() : ?Vector3{ return $this->facingPosition; }

	public function getDefault() : ?bool{ return $this->default; }

	public static function read(DataPacket $in) : self{
		$preset = $in->getLInt();
		$ease = $in->readOptional(fn() => CameraSetInstructionEase::read($in));
		$cameraPosition = $in->readOptional($in->getVector3(...));
		$rotation = $in->readOptional(fn() => CameraSetInstructionRotation::read($in));
		$facingPosition = $in->readOptional($in->getVector3(...));
		$default = $in->readOptional($in->getBool(...));

		return new self(
			$preset,
			$ease,
			$cameraPosition,
			$rotation,
			$facingPosition,
			$default
		);
	}

	public function write(DataPacket $out) : void{
		$out->putLInt($this->preset);
		$out->writeOptional($this->ease, fn(CameraSetInstructionEase $v) => $v->write($out));
		$out->writeOptional($this->cameraPosition, $out->putVector3(...));
		$out->writeOptional($this->rotation, fn(CameraSetInstructionRotation $v) => $v->write($out));
		$out->writeOptional($this->facingPosition, $out->putVector3(...));
		$out->writeOptional($this->default, $out->putBool(...));
	}
}


