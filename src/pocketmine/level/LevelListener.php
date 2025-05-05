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

/**
 * This interface allows you to listen for events related to levels. This is only used for handling level unloading for now.
 *
 * @see Level::registerLevelListener()
 * @see Level::unregisterLevelListener()
 */
interface LevelListener{

	/**
	 * This method will be called when a Level is unloaded.
	 * 
	 * @param Level $level
	 */
	public function onLevelUnloaded(Level $level);
}

