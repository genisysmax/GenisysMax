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

use pocketmine\block\Block;
use pocketmine\level\format\Chunk;
use pocketmine\math\Vector3;
use pocketmine\tile\Tile;

/**
 * This interface allows you to listen for events occurring on or in specific chunks. This will receive events for any
 * chunks which it is registered to listen to.
 *
 * @see Level::registerChunkListener()
 * @see Level::unregisterChunkListener()
 *
 * WARNING: When you're done with the listener, make sure you unregister it from all chunks it's listening to, otherwise
 * the object will not be destroyed.
 * The listener WILL NOT be unregistered when chunks are unloaded. You need to do this yourself when you're done with
 * a chunk.
 */
interface ChunkListener{

	/**
	 * This method will be called when a Chunk is replaced by a new one
	 *
	 * @param Chunk $chunk
	 */
	public function onChunkChanged(Chunk $chunk);

	/**
	 * This method will be called when a registered chunk is loaded
	 *
	 * @param Chunk $chunk
	 */
	public function onChunkLoaded(Chunk $chunk);


	/**
	 * This method will be called when a registered chunk is unloaded
	 *
	 * @param Chunk $chunk
	 */
	public function onChunkUnloaded(Chunk $chunk);

	/**
	 * This method will be called when a registered chunk is populated
	 * Usually it'll be sent with another call to onChunkChanged()
	 *
	 * @param Chunk $chunk
	 */
	public function onChunkPopulated(Chunk $chunk);

	/**
	 * This method will be called when a block changes in a registered chunk
	 *
	 * @param Block|Vector3 $block
	 */
	public function onBlockChanged(Vector3 $block);

	/**
	 * This method will be called when a tile changes in a registered chunk
	 *
	 * @param Tile $tile
	 */
	public function onTileChanged(Tile $tile);
}

