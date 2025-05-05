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

namespace pocketmine\level;

use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use function cos;
use function deg2rad;
use function sin;
use const M_PI_2;

class Location extends Position{
	/** @var Vector3|null */
	private static $temporalVector;

	public $yaw;
	public $pitch;

	/**
	 * @param int   $x
	 * @param int   $y
	 * @param int   $z
	 * @param float $yaw
	 * @param float $pitch
	 * @param Level $level
	 */
	public function __construct($x = 0, $y = 0, $z = 0, $yaw = 0.0, $pitch = 0.0, Level $level = null){
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
		$this->yaw = $yaw;
		$this->pitch = $pitch;
		$this->level = $level;
	}

	/**
	 * @param Vector3    $pos
	 * @param Level|null $level default null
	 * @param float      $yaw   default 0.0
	 * @param float      $pitch default 0.0
	 *
	 * @return Location
	 */
	public static function fromObject(Vector3 $pos, Level $level = null, $yaw = 0.0, $pitch = 0.0) : Location{
		return new Location($pos->x, $pos->y, $pos->z, $yaw, $pitch, $level ?? (($pos instanceof Position) ? $pos->level : null));
	}

	/**
	 * Return a Location instance
	 *
	 * @return Location
	 */
	public function asLocation() : Location{
		return new Location($this->x, $this->y, $this->z, $this->yaw, $this->pitch, $this->level);
	}

	public function getYaw(){
		return $this->yaw;
	}

	public function getPitch(){
		return $this->pitch;
	}

	/**
	 * @return Vector3
	 */
	public function getDirectionVector(){
		$y = -sin(deg2rad($this->pitch));
		$xz = cos(deg2rad($this->pitch));
		$x = -$xz * sin(deg2rad($this->yaw));
		$z = $xz * cos(deg2rad($this->yaw));

		self::$temporalVector = self::$temporalVector ?? new Vector3();
		return self::$temporalVector->setComponents($x, $y, $z)->normalize();
	}

	public function getDirectionPlane(){
		return (new Vector2(-cos(deg2rad($this->yaw) - M_PI_2), -sin(deg2rad($this->yaw) - M_PI_2)))->normalize();
	}

	public function __toString(){
		return "Location (level=" . ($this->isValid() ? $this->getLevel()->getName() : "null") . ", x=$this->x, y=$this->y, z=$this->z, yaw=$this->yaw, pitch=$this->pitch)";
	}

	public function equals(Vector3 $v) : bool{
		if($v instanceof Location){
			return parent::equals($v) and $v->yaw == $this->yaw and $v->pitch == $this->pitch;
		}
		return parent::equals($v);
	}
}


