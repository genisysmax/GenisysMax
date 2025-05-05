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

namespace pocketmine\level\generator\biome;

use pocketmine\level\generator\noise\Simplex;
use pocketmine\utils\Random;
use function call_user_func;

class BiomeSelector{

	/** @var Biome */
	private $fallback;

	/** @var Simplex */
	private $temperature;
	/** @var Simplex */
	private $rainfall;

	/** @var Biome[] */
	private $biomes = [];

	/** @var \SplFixedArray */
	private $map = null;

	/** @var callable */
	private $lookup;

	public function __construct(Random $random, callable $lookup, Biome $fallback){
		$this->fallback = $fallback;
		$this->lookup = $lookup;
		$this->temperature = new Simplex($random, 2, 1 / 16, 1 / 512);
		$this->rainfall = new Simplex($random, 2, 1 / 16, 1 / 512);
	}

	public function recalculate(){
		$this->map = new \SplFixedArray(64 * 64);

		for($i = 0; $i < 64; ++$i){
			for($j = 0; $j < 64; ++$j){
				$this->map[$i + ($j << 6)] = call_user_func($this->lookup, $i / 63, $j / 63);
			}
		}
	}

	public function addBiome(Biome $biome){
		$this->biomes[$biome->getId()] = $biome;
	}

	public function getTemperature($x, $z){
		return ($this->temperature->noise2D($x, $z, true) + 1) / 2;
	}

	public function getRainfall($x, $z){
		return ($this->rainfall->noise2D($x, $z, true) + 1) / 2;
	}

	/**
	 * @param $x
	 * @param $z
	 *
	 * @return Biome
	 */
	public function pickBiome($x, $z) : Biome{
		$temperature = (int) ($this->getTemperature($x, $z) * 63);
		$rainfall = (int) ($this->getRainfall($x, $z) * 63);

		$biomeId = $this->map[$temperature + ($rainfall << 6)];
		return $this->biomes[$biomeId] ?? $this->fallback;
	}
}

