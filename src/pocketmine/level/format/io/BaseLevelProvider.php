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

namespace pocketmine\level\format\io;

use pocketmine\level\generator\Generator;
use pocketmine\level\Level;
use pocketmine\level\LevelException;
use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\utils\Zlib;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function mkdir;
use const ZLIB_ENCODING_GZIP;

abstract class BaseLevelProvider implements LevelProvider{
	/** @var Level */
	protected $level;
	/** @var string */
	protected $path;
	/** @var CompoundTag */
	protected $levelData;

	public function __construct(Level $level, string $path){
		$this->level = $level;
		$this->path = $path;
		if(!file_exists($this->path)){
			mkdir($this->path, 0777, true);
		}

		$rawLevelData = file_get_contents($this->getPath() . "level.dat");
		if($rawLevelData === false){
			throw new LevelException("Failed to read level.dat (permission denied or doesn't exist)");
		}

		$decompressed = @Zlib::decompress($rawLevelData);
		if($decompressed === false){
			throw new LevelException("Failed to decompress level.dat contents");
		}

		try{
			$levelData = (new BigEndianNbtSerializer())->read($decompressed)->mustGetCompoundTag();
		}catch(NbtDataException $e){
			throw new LevelException("Invalid level.dat: " . $e->getMessage(), 0, $e);
		}

		if($levelData->hasTag("Data", CompoundTag::class)){
			$this->levelData = $levelData->getCompoundTag("Data");
		}else{
			throw new LevelException("Invalid level.dat");
		}

		if(!$this->levelData->hasTag("generatorName", StringTag::class)){
			$this->levelData->setString("generatorName", (string) Generator::getGenerator("DEFAULT"));
		}

		if(!$this->levelData->hasTag("generatorOptions", StringTag::class)){
			$this->levelData->setString("generatorOptions", "");
		}
	}

	public function getPath() : string{
		return $this->path;
	}

	public function getServer(){
		return $this->level->getServer();
	}

	public function getLevel() : Level{
		return $this->level;
	}

	public function getName() : string{
		return $this->levelData->getString("LevelName");
	}

	public function getTime(){
		return $this->levelData->getLong("Time");
	}

	public function setTime($value){
		$this->levelData->setLong("Time", $value);
	}

	public function getSeed(){
		return $this->levelData->getLong("RandomSeed");
	}

	public function setSeed($value){
		$this->levelData->setLong("RandomSeed", $value);
	}

	public function getSpawn() : Vector3{
		return new Vector3((float) $this->levelData->getInt("SpawnX"), (float) $this->levelData->getInt("SpawnY"), (float) $this->levelData->getInt("SpawnZ"));
	}

	public function setSpawn(Vector3 $pos){
		$this->levelData->setInt("SpawnX", (int) $pos->x);
		$this->levelData->setInt("SpawnY", (int) $pos->y);
		$this->levelData->setInt("SpawnZ", (int) $pos->z);
	}

	public function doGarbageCollection(){

	}

	/**
	 * @return CompoundTag
	 */
	public function getLevelData() : CompoundTag{
		return $this->levelData;
	}

	public function saveLevelData(){
		$tag = CompoundTag::create()->setTag("Data", $this->levelData);
		$decompressed = (new BigEndianNbtSerializer())->write(new TreeRoot($tag));
		$buffer = Zlib::compress($decompressed, ZLIB_ENCODING_GZIP);
		file_put_contents($this->getPath() . "level.dat", $buffer);
	}
}


