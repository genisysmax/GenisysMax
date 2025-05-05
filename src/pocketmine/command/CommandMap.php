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

namespace pocketmine\command;


interface CommandMap{

	/**
	 * @param string $fallbackPrefix
	 * @param Command[] $commands
	 */
	public function registerAll(string $fallbackPrefix, array $commands);

	/**
	 * @param string      $fallbackPrefix
	 * @param Command     $command
	 * @param string|null $label
	 *
	 * @return bool
	 */
	public function register(string $fallbackPrefix, Command $command, string $label = null) : bool;

	/**
	 * @param CommandSender $sender
	 * @param string        $cmdLine
	 *
	 * @return bool
	 */
	public function dispatch(CommandSender $sender, string $cmdLine) : bool;

	/**
	 * @param Command     $command
	 *
	 * @return bool
	 */
	public function unregister(Command $command) : bool;

	/**
	 * @return void
	 */
	public function clearCommands();

	/**
	 * @param string $name
	 *
	 * @return Command|null
	 */
	public function getCommand(string $name);


}

