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

use pocketmine\math\Vector3;
use pocketmine\Server;
use function assert;

class WeakPosition extends Position{

	protected $levelId = -1;

	/**
	 * @param int   $x
	 * @param int   $y
	 * @param int   $z
	 * @param Level $level
	 */
	public function __construct($x = 0, $y = 0, $z = 0, Level $level = null){
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
		$this->levelId = ($level !== null ? $level->getId() : -1);
	}

	public static function fromObject(Vector3 $pos, Level $level = null){
		return new WeakPosition($pos->x, $pos->y, $pos->z, $level);
	}

	/**
	 * @return Level|null
	 */
	public function getLevel(){
		return Server::getInstance()->getLevel($this->levelId);
	}

	/**
	 * @param Level|null $level
	 *
	 * @return $this
	 *
	 * @throws \InvalidArgumentException if the specified Level has been closed
	 */
	public function setLevel(Level $level = null){
		if($level !== null and $level->isClosed()){
			throw new \InvalidArgumentException("Specified level has been unloaded and cannot be used");
		}

		$this->levelId = ($level !== null ? $level->getId() : -1);
		return $this;
	}

	/**
	 * Returns a side Vector
	 *
	 * @param int $side
	 * @param int $step
	 *
	 * @return WeakPosition
	 *
	 * @throws LevelException
	 */
	public function getSide(int $side, int $step = 1){
		assert($this->isValid());

		return WeakPosition::fromObject(parent::getSide($side, $step), $this->level);
	}

	public function __toString(){
		return "Weak" . parent::__toString();
	}
}

