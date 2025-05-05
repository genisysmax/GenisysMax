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

namespace pocketmine\level\weather;

use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\weather\Lightning;
use pocketmine\event\level\WeatherChangeEvent;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Player;

class Weather{

	public const CLEAR = 0;
	public const SUNNY = 0;
	public const RAIN = 1;
	public const RAINY = 1;
	public const RAINY_THUNDER = 2;
	public const THUNDER = 3;

	private int $weatherNow = 0;
	private int $strength1 = 100000;
	private int $strength2 = 35000;
	private bool $canCalculate = true;

	private ?Vector3 $temporalVector = null;

	private int $lastUpdate = 0;
	private array $randomWeatherData = [0, 1, 0, 1, 0, 1, 0, 2, 0, 3];

	public function __construct(
        private Level $level,
        private int $duration = 1200
    ){
		$this->weatherNow = self::SUNNY;
		$this->lastUpdate = $level->getServer()->getTick();
		$this->temporalVector = new Vector3(0, 0, 0);
	}

	/**
	 * @return bool
	 */
	public function canCalculate() : bool{
		return $this->canCalculate;
	}

	/**
	 * @param bool $canCalc
	 */
	public function setCanCalculate(bool $canCalc): void{
		$this->canCalculate = $canCalc;
	}

	/**
	 * @param $currentTick
	 */
	public function calcWeather(int $currentTick): void{
		if($this->canCalculate()){
			$tickDiff = $currentTick - $this->lastUpdate;
			$this->duration -= $tickDiff;
            $server = $this->level->getServer();

			if($this->duration <= 0){
                $weatherRandomDurationMin = $server->getAdvancedProperty("level.weather-random-duration-min", 6000);
                $weatherRandomDurationMax = $server->getAdvancedProperty("level.weather-random-duration-max", 12000);
				$duration = mt_rand(
					min($weatherRandomDurationMin, $weatherRandomDurationMax),
					max($weatherRandomDurationMin, $weatherRandomDurationMax));

				if($this->weatherNow === self::SUNNY){
					$weather = $this->randomWeatherData[array_rand($this->randomWeatherData)];
                }else{
					$weather = self::SUNNY;
                }
                $this->setWeather($weather, $duration);
            }
            $lightingTime = $server->getAdvancedProperty("level.lightning-time", 200);
			if(($this->weatherNow >= self::RAINY_THUNDER) and ($lightingTime > 0) and is_int($this->duration / $lightingTime)){
				$players = $this->level->getPlayers();
				if(count($players) > 0){
					$p = $players[array_rand($players)];
					$x = $p->x + mt_rand(-64, 64);
					$z = $p->z + mt_rand(-64, 64);
					$y = $this->level->getHighestBlockAt((int)$x, (int)$z);

                    $lightning = new Lightning($this->level, EntityDataHelper::createBaseNBT($this->temporalVector->setComponents($x, $y, $z), null, 0, 0));
                    $lightning->spawnToAll();
				}
			}
		}
		$this->lastUpdate = $currentTick;
	}

	/**
	 * @param int $wea
	 * @param int $duration
	 */
	public function setWeather(int $wea, int $duration = 12000): void{
        $ev = new WeatherChangeEvent($this->level, $wea, $duration);
        $ev->call();
		if(!$ev->isCancelled()){
			$this->weatherNow = $ev->getWeather();
			$this->strength1 = mt_rand(90000, 110000); //If we're clearing the weather, it doesn't matter what strength values we set
			$this->strength2 = mt_rand(30000, 40000);
			$this->duration = $ev->getDuration();
			$this->sendWeatherToAll();
		}
	}

	/**
	 * @return array
	 */
	public function getRandomWeatherData() : array{
		return $this->randomWeatherData;
	}

	/**
	 * @param array $randomWeatherData
	 */
	public function setRandomWeatherData(array $randomWeatherData): void{
		$this->randomWeatherData = $randomWeatherData;
	}

	/**
	 * @return int
	 */
	public function getWeather() : int{
		return $this->weatherNow;
	}

    /**
     * @param string|int $weather
     *
     * @return int
     */
	public static function getWeatherFromString(string|int $weather): int{
		if(is_int($weather)){
			if($weather <= 3){
				return $weather;
			}
			return self::SUNNY;
		}
        return match (strtolower($weather)) {
            "rain", "rainy" => self::RAINY,
            "thunder" => self::THUNDER,
            "rain_thunder", "rainy_thunder", "storm" => self::RAINY_THUNDER,
            default => self::SUNNY,
        };
	}

	/**
	 * @return bool
	 */
	public function isSunny() : bool{
		return $this->getWeather() === self::SUNNY;
	}

	/**
	 * @return bool
	 */
	public function isRainy() : bool{
		return $this->getWeather() === self::RAINY;
	}

	/**
	 * @return bool
	 */
	public function isRainyThunder() : bool{
		return $this->getWeather() === self::RAINY_THUNDER;
	}

	/**
	 * @return bool
	 */
	public function isThunder() : bool{
		return $this->getWeather() === self::THUNDER;
	}

	/**
	 * @return array
	 */
	public function getStrength() : array{
		return [$this->strength1, $this->strength2];
	}

	/**
	 * @param Player $player
	 */
	public function sendWeather(Player $player): void{
		$pks = [
			new LevelEventPacket(),
			new LevelEventPacket()
		];

		//Set defaults. These will be sent if the case statement defaults.
		$pks[0]->evid = LevelEventPacket::EVENT_STOP_RAIN;
		$pks[0]->data = $this->strength1;
		$pks[1]->evid = LevelEventPacket::EVENT_STOP_THUNDER;
		$pks[1]->data = $this->strength2;

		switch($this->weatherNow){
			//If the weather is not clear, overwrite the packet values with these
			case self::RAIN:
				$pks[0]->evid = LevelEventPacket::EVENT_START_RAIN;
				$pks[0]->data = $this->strength1;
				break;
			case self::RAINY_THUNDER:
				$pks[0]->evid = LevelEventPacket::EVENT_START_RAIN;
				$pks[0]->data = $this->strength1;
				$pks[1]->evid = LevelEventPacket::EVENT_START_THUNDER;
				$pks[1]->data = $this->strength2;
				break;
			case self::THUNDER:
				$pks[1]->evid = LevelEventPacket::EVENT_START_THUNDER;
				$pks[1]->data = $this->strength2;
				break;
			default:
				break;
		}

		foreach($pks as $pk){
            $player->sendDataPacket($pk);
		}
        $player->weatherData = [$this->weatherNow, $this->strength1, $this->strength2];
	}

	public function sendWeatherToAll(): void{
		foreach($this->level->getPlayers() as $player){
			$this->sendWeather($player);
		}
	}

}

