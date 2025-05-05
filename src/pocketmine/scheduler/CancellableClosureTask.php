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

namespace pocketmine\scheduler;

use pocketmine\utils\Utils;

/**
 * This class permits scheduling a self-cancelling closure to run. This is useful for repeating tasks.
 * The given closure must return a bool which indicates whether it should continue executing.
 *
 * Example usage:
 *
 * ```
 * Server::getInstance()->scheduleRepeatingTask(new CancellableClosureTask(function(int $currentTick) : bool{
 *     echo "HI on $currentTick\n";
 *     $continue = false;
 *     return $continue; //stop repeating
 * });
 * ```
 *
 * @see ClosureTask
 */
class CancellableClosureTask extends Task{

	/** @var \Closure */
	private $closure;

	/**
	 * CancellableClosureTask constructor.
	 *
	 * The closure should follow the signature callback(int $currentTick) : bool. The return value will be used to
	 * decide whether to continue repeating.
	 *
	 * @param \Closure $closure
	 */
	public function __construct(\Closure $closure){
		Utils::validateCallableSignature(function(int $currentTick) : bool{ return false; }, $closure);
		$this->closure = $closure;
	}

	public function getName() : string{
		return Utils::getNiceClosureName($this->closure);
	}

	public function onRun(int $currentTick){
		if(!($this->closure)($currentTick)){
			$this->getHandler()->cancel();
		}
	}
}

