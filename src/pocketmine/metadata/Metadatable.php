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

namespace pocketmine\metadata;

use pocketmine\plugin\Plugin;

interface Metadatable{

	/**
	 * Sets a metadata value in the implementing object's metadata store.
	 *
	 * @param string        $metadataKey
	 * @param MetadataValue $newMetadataValue
	 */
	public function setMetadata(string $metadataKey, MetadataValue $newMetadataValue);

	/**
	 * Returns a list of previously set metadata values from the implementing
	 * object's metadata store.
	 *
	 * @param string $metadataKey
	 *
	 * @return MetadataValue[]
	 */
	public function getMetadata(string $metadataKey);

	/**
	 * Tests to see whether the implementing object contains the given
	 * metadata value in its metadata store.
	 *
	 * @param string $metadataKey
	 *
	 * @return bool
	 */
	public function hasMetadata(string $metadataKey) : bool;

	/**
	 * Removes the given metadata value from the implementing object's
	 * metadata store.
	 *
	 * @param string $metadataKey
	 * @param Plugin $owningPlugin
	 */
	public function removeMetadata(string $metadataKey, Plugin $owningPlugin);

}

