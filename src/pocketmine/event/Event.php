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

/**
 * Event related classes
 */
namespace pocketmine\event;

use pocketmine\Server;
use function get_class;

abstract class Event{
	private const MAX_EVENT_CALL_DEPTH = 50;
	/** @var int */
	private static $eventCallDepth = 1;

	/**
	 * Any callable event must declare the static variable
	 *
	 * public static $handlerList = null;
	 *
	 * Not doing so will deny the proper event initialization
	 */

	/** @var string|null */
	protected $eventName = null;
	/** @var bool */
	private $isCancelled = false;

	/**
	 * @return string
	 */
	final public function getEventName() : string{
		return $this->eventName ?? get_class($this);
	}

	/**
	 * @return bool
	 *
	 * @throws \BadMethodCallException
	 */
	public function isCancelled() : bool{
		if(!($this instanceof Cancellable)){
			throw new \BadMethodCallException("Event is not Cancellable");
		}

		/** @var Event $this */
		return $this->isCancelled === true;
	}

	/**
	 * @param bool $value
	 *
	 * @throws \BadMethodCallException
	 */
	public function setCancelled(bool $value = true){
		if(!($this instanceof Cancellable)){
			throw new \BadMethodCallException("Event is not Cancellable");
		}

		/** @var Event $this */
		$this->isCancelled = $value;
	}

	/**
	 * @return HandlerList
	 */
	public function getHandlers() : HandlerList{
		if(static::$handlerList === null){
			static::$handlerList = new HandlerList();
		}

		return static::$handlerList;
	}

	/**
	 * Calls event handlers registered for this event.
	 * 
	 * @throws \RuntimeException
	 */
	public function call() : void{
		if(self::$eventCallDepth >= self::MAX_EVENT_CALL_DEPTH){
			//this exception will be caught by the parent event call if all else fails
			throw new \RuntimeException("Recursive event call detected (reached max depth of " . self::MAX_EVENT_CALL_DEPTH . " calls)");
		}

		++self::$eventCallDepth;
		foreach($this->getHandlers()->getRegisteredListeners() as $registration){
			try{
				$registration->callEvent($this);
			}catch(\Throwable $e){
				$server = Server::getInstance();

				$server->getLogger()->critical(
					$server->getLanguage()->translateString("pocketmine.plugin.eventError", [
						$this->getEventName(),
						$registration->getPlugin()->getDescription()->getFullName(),
						$e->getMessage(),
						get_class($registration->getListener())
					]));
				$server->getLogger()->logException($e);
			}
		}
		--self::$eventCallDepth;
	}
}


