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

namespace pocketmine\event;

use pocketmine\level\Level;

class LevelTimings{

	/** @var TimingsHandler */
	public $setBlock;
	/** @var TimingsHandler */
	public $doBlockLightUpdates;
	/** @var TimingsHandler */
	public $doBlockSkyLightUpdates;

	/** @var TimingsHandler */
	public $mobSpawn;
	/** @var TimingsHandler */
	public $doChunkUnload;
	/** @var TimingsHandler */
	public $doPortalForcer;
	/** @var TimingsHandler */
	public $doTickPending;
	/** @var TimingsHandler */
	public $doTickTiles;
	/** @var TimingsHandler */
	public $doVillages;
	/** @var TimingsHandler */
	public $doChunkMap;
	/** @var TimingsHandler */
	public $doChunkGC;
	/** @var TimingsHandler */
	public $doSounds;
	/** @var TimingsHandler */
	public $entityTick;
	/** @var TimingsHandler */
	public $tileEntityTick;
	/** @var TimingsHandler */
	public $tileEntityPending;
	/** @var TimingsHandler */
	public $tracker;
	/** @var TimingsHandler */
	public $doTick;
	/** @var TimingsHandler */
	public $tickEntities;

	/** @var TimingsHandler */
	public $syncChunkSendTimer;
	/** @var TimingsHandler */
	public $syncChunkSendPrepareTimer;

	/** @var TimingsHandler */
	public $syncChunkLoadTimer;
	/** @var TimingsHandler */
	public $syncChunkLoadDataTimer;
	/** @var TimingsHandler */
	public $syncChunkLoadStructuresTimer;
	/** @var TimingsHandler */
	public $syncChunkLoadEntitiesTimer;
	/** @var TimingsHandler */
	public $syncChunkLoadTileEntitiesTimer;
	/** @var TimingsHandler */
	public $syncChunkLoadTileTicksTimer;
	/** @var TimingsHandler */
	public $syncChunkLoadPostTimer;

	public function __construct(Level $level){
		$name = $level->getFolderName() . " - ";

		$this->setBlock = new TimingsHandler("** " . $name . "setBlock");
		$this->doBlockLightUpdates = new TimingsHandler("** " . $name . "doBlockLightUpdates");
		$this->doBlockSkyLightUpdates = new TimingsHandler("** " . $name . "doBlockSkyLightUpdates");

		$this->mobSpawn = new TimingsHandler("** " . $name . "mobSpawn");
		$this->doChunkUnload = new TimingsHandler("** " . $name . "doChunkUnload");
		$this->doTickPending = new TimingsHandler("** " . $name . "doTickPending");
		$this->doTickTiles = new TimingsHandler("** " . $name . "doTickTiles");
		$this->doVillages = new TimingsHandler("** " . $name . "doVillages");
		$this->doChunkMap = new TimingsHandler("** " . $name . "doChunkMap");
		$this->doSounds = new TimingsHandler("** " . $name . "doSounds");
		$this->doChunkGC = new TimingsHandler("** " . $name . "doChunkGC");
		$this->doPortalForcer = new TimingsHandler("** " . $name . "doPortalForcer");
		$this->entityTick = new TimingsHandler("** " . $name . "entityTick");
		$this->tileEntityTick = new TimingsHandler("** " . $name . "tileEntityTick");
		$this->tileEntityPending = new TimingsHandler("** " . $name . "tileEntityPending");

		$this->syncChunkSendTimer = new TimingsHandler("** " . $name . "syncChunkSend");
		$this->syncChunkSendPrepareTimer = new TimingsHandler("** " . $name . "syncChunkSendPrepare");

		$this->syncChunkLoadTimer = new TimingsHandler("** " . $name . "syncChunkLoad");
		$this->syncChunkLoadDataTimer = new TimingsHandler("** " . $name . "syncChunkLoad - Data");
		$this->syncChunkLoadStructuresTimer = new TimingsHandler("** " . $name . "syncChunkLoad - Structures");
		$this->syncChunkLoadEntitiesTimer = new TimingsHandler("** " . $name . "syncChunkLoad - Entities");
		$this->syncChunkLoadTileEntitiesTimer = new TimingsHandler("** " . $name . "syncChunkLoad - TileEntities");
		$this->syncChunkLoadTileTicksTimer = new TimingsHandler("** " . $name . "syncChunkLoad - TileTicks");
		$this->syncChunkLoadPostTimer = new TimingsHandler("** " . $name . "syncChunkLoad - Post");

		$this->tracker = new TimingsHandler($name . "tracker");
		$this->doTick = new TimingsHandler($name . "doTick");
		$this->tickEntities = new TimingsHandler($name . "tickEntities");
	}

}

