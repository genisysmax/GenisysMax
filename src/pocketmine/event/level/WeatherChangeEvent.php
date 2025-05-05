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

namespace pocketmine\event\level;

use pocketmine\event\Cancellable;
use pocketmine\level\Level;
use pocketmine\level\weather\Weather;

class WeatherChangeEvent extends LevelEvent implements Cancellable{
	public static $handlerList = null;

	private $weather;
	private $duration;

	/**
	 * WeatherChangeEvent constructor.
	 *
	 * @param Level $level
	 * @param int $weather
	 * @param int $duration
	 */
	public function __construct(Level $level, int $weather, int $duration){
		parent::__construct($level);
		$this->weather = $weather;
		$this->duration = $duration;
	}

	/**
	 * @return int
	 */
	public function getWeather() : int{
		return $this->weather;
	}

	/**
	 * @param int $weather
	 */
	public function setWeather(int $weather = Weather::SUNNY){
		$this->weather = $weather;
	}

	/**
	 * @return int
	 */
	public function getDuration() : int{
		return $this->duration;
	}

	/**
	 * @param int $duration
	 */
	public function setDuration(int $duration){
		$this->duration = $duration;
	}

}

