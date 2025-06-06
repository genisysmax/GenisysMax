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

use pocketmine\block\Block;
use pocketmine\level\ChunkManager;
use pocketmine\level\generator\normal\biome\DesertBiome;
use pocketmine\level\generator\normal\biome\ForestBiome;
use pocketmine\level\generator\normal\biome\IcePlainsBiome;
use pocketmine\level\generator\normal\biome\MountainsBiome;
use pocketmine\level\generator\normal\biome\OceanBiome;
use pocketmine\level\generator\normal\biome\PlainBiome;
use pocketmine\level\generator\normal\biome\RiverBiome;
use pocketmine\level\generator\normal\biome\SmallMountainsBiome;
use pocketmine\level\generator\normal\biome\SwampBiome;
use pocketmine\level\generator\normal\biome\TaigaBiome;
use pocketmine\level\generator\populator\Populator;
use pocketmine\utils\Random;

abstract class Biome{

	public const OCEAN = 0;
	public const PLAINS = 1;
	public const DESERT = 2;
	public const MOUNTAINS = 3;
	public const FOREST = 4;
	public const TAIGA = 5;
	public const SWAMP = 6;
	public const RIVER = 7;

	public const HELL = 8;

	public const ICE_PLAINS = 12;


	public const SMALL_MOUNTAINS = 20;


	public const BIRCH_FOREST = 27;


	public const MAX_BIOMES = 256;

	/** @var Biome[] */
	private static $biomes = [];

	/** @var int */
	private $id;
	/** @var bool */
	private $registered = false;

	/** @var Populator[] */
	private $populators = [];

	/** @var int */
	private $minElevation;
	/** @var int */
	private $maxElevation;

	/** @var Block[] */
	private $groundCover = [];

	/** @var float */
	protected $rainfall = 0.5;
	/** @var float */
	protected $temperature = 0.5;

	protected static function register(int $id, Biome $biome){
		self::$biomes[$id] = $biome;
		$biome->setId($id);
	}

	public static function init(){
		self::register(self::OCEAN, new OceanBiome());
		self::register(self::PLAINS, new PlainBiome());
		self::register(self::DESERT, new DesertBiome());
		self::register(self::MOUNTAINS, new MountainsBiome());
		self::register(self::FOREST, new ForestBiome());
		self::register(self::TAIGA, new TaigaBiome());
		self::register(self::SWAMP, new SwampBiome());
		self::register(self::RIVER, new RiverBiome());

		self::register(self::ICE_PLAINS, new IcePlainsBiome());


		self::register(self::SMALL_MOUNTAINS, new SmallMountainsBiome());

		self::register(self::BIRCH_FOREST, new ForestBiome(ForestBiome::TYPE_BIRCH));
	}

	/**
	 * @param int $id
	 *
	 * @return Biome
	 */
	public static function getBiome(int $id) : Biome{
		return self::$biomes[$id] ?? self::$biomes[self::OCEAN];
	}

	public function clearPopulators(){
		$this->populators = [];
	}

	public function addPopulator(Populator $populator){
		$this->populators[] = $populator;
	}

	/**
	 * @param ChunkManager $level
	 * @param int          $chunkX
	 * @param int          $chunkZ
	 * @param Random       $random
	 */
	public function populateChunk(ChunkManager $level, int $chunkX, int $chunkZ, Random $random){
		foreach($this->populators as $populator){
			$populator->populate($level, $chunkX, $chunkZ, $random);
		}
	}

	/**
	 * @return Populator[]
	 */
	public function getPopulators() : array{
		return $this->populators;
	}

	public function setId(int $id){
		if(!$this->registered){
			$this->registered = true;
			$this->id = $id;
		}
	}

	public function getId() : int{
		return $this->id;
	}

	abstract public function getName() : string;

	public function getMinElevation() : int{
		return $this->minElevation;
	}

	public function getMaxElevation() : int{
		return $this->maxElevation;
	}

	public function setElevation(int $min, int $max){
		$this->minElevation = $min;
		$this->maxElevation = $max;
	}

	/**
	 * @return Block[]
	 */
	public function getGroundCover() : array{
		return $this->groundCover;
	}

	/**
	 * @param Block[] $covers
	 */
	public function setGroundCover(array $covers){
		$this->groundCover = $covers;
	}

	public function getTemperature() : float{
		return $this->temperature;
	}

	public function getRainfall() : float{
		return $this->rainfall;
	}
}

