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

use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

interface LevelProvider{

	/**
	 * @param Level  $level
	 * @param string $path
	 */
	public function __construct(Level $level, string $path);

	/**
	 * Returns the full provider name, like "anvil" or "mcregion", will be used to find the correct format.
	 *
	 * @return string
	 */
	public static function getProviderName() : string;

	/**
	 * Gets the build height limit of this world
	 *
	 * @return int
	 */
	public function getWorldHeight() : int;

	/**
	 * @return string
	 */
	public function getPath() : string;

	/**
	 * Tells if the path is a valid level.
	 * This must tell if the current format supports opening the files in the directory
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public static function isValid(string $path) : bool;

	/**
	 * Generate the needed files in the path given
	 *
	 * @param string  $path
	 * @param string  $name
	 * @param int     $seed
	 * @param string  $generator
	 * @param array[] $options
	 */
	public static function generate(string $path, string $name, int $seed, string $generator, array $options = []);

	/**
	 * Returns the generator name
	 *
	 * @return string
	 */
	public function getGenerator() : string;

	/**
	 * @return array
	 */
	public function getGeneratorOptions() : array;

	/**
	 * Gets the Chunk object
	 * This method must be implemented by all the level formats.
	 *
	 * @param int  $chunkX
	 * @param int  $chunkZ
	 * @param bool $create
	 *
	 * @return Chunk|null
	 */
	public function getChunk(int $chunkX, int $chunkZ, bool $create = false);

	/**
	 * @param int   $chunkX
	 * @param int   $chunkZ
	 * @param Chunk $chunk
	 */
	public function setChunk(int $chunkX, int $chunkZ, Chunk $chunk);

	/**
	 * @param int $chunkX
	 * @param int $chunkZ
	 *
	 * @return bool
	 */
	public function saveChunk(int $chunkX, int $chunkZ) : bool;

	public function saveChunks();

	/**
	 * @param int  $chunkX
	 * @param int  $chunkZ
	 * @param bool $create
	 *
	 * @return bool
	 */
	public function loadChunk(int $chunkX, int $chunkZ, bool $create = false) : bool;

	/**
	 * @param int  $chunkX
	 * @param int  $chunkZ
	 * @param bool $safe
	 *
	 * @return bool
	 */
	public function unloadChunk(int $chunkX, int $chunkZ, bool $safe = true) : bool;

	public function unloadChunks();

	/**
	 * @param int $chunkX
	 * @param int $chunkZ
	 *
	 * @return bool
	 */
	public function isChunkLoaded(int $chunkX, int $chunkZ) : bool;

	/**
	 * @param int $chunkX
	 * @param int $chunkZ
	 *
	 * @return bool
	 */
	public function isChunkGenerated(int $chunkX, int $chunkZ) : bool;

	/**
	 * @param int $chunkX
	 * @param int $chunkZ
	 *
	 * @return bool
	 */
	public function isChunkPopulated(int $chunkX, int $chunkZ) : bool;

	/**
	 * @return string
	 */
	public function getName() : string;

	/**
	 * @return int|string int, or the string numeric representation of a long in 32-bit systems
	 */
	public function getTime();

	/**
	 * @param int|string $value int, or the string numeric representation of a long in 32-bit systems
	 */
	public function setTime($value);

	/**
	 * @return int|string int, or the string numeric representation of a long in 32-bit systems
	 */
	public function getSeed();

	/**
	 * @param int|string $value int, or the string numeric representation of a long in 32-bit systems
	 */
	public function setSeed($value);

    /**
     * Returns the world difficulty. This will be one of the Level constants.
     * @return int
     */
    public function getDifficulty() : int;

    /**
     * Sets the world difficulty.
     * @param int $difficulty
     */
    public function setDifficulty(int $difficulty): void;

	/**
	 * @return Vector3
	 */
	public function getSpawn() : Vector3;

	/**
	 * @param Vector3 $pos
	 */
	public function setSpawn(Vector3 $pos);

	/**
	 * @return Chunk[]
	 */
	public function getLoadedChunks() : array;

	public function doGarbageCollection();

	/**
	 * @return Level
	 */
	public function getLevel() : Level;

	public function close();

}

