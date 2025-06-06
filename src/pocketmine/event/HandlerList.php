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

use pocketmine\plugin\Plugin;
use pocketmine\plugin\RegisteredListener;
use function spl_object_id;

class HandlerList{

	/**
	 * @var RegisteredListener[]
	 */
	private $handlers = null;

	/**
	 * @var RegisteredListener[][]
	 */
	private $handlerSlots = [];

	/**
	 * @var HandlerList[]
	 */
	private static $allLists = [];

	public static function bakeAll(){
		foreach(self::$allLists as $h){
			$h->bake();
		}
	}

	/**
	 * Unregisters all the listeners
	 * If a Plugin or Listener is passed, all the listeners with that object will be removed
	 *
	 * @param Plugin|Listener|null $object
	 */
	public static function unregisterAll($object = null){
		if($object instanceof Listener or $object instanceof Plugin){
			foreach(self::$allLists as $h){
				$h->unregister($object);
			}
		}else{
			foreach(self::$allLists as $h){
				foreach($h->handlerSlots as $key => $list){
					$h->handlerSlots[$key] = [];
				}
				$h->handlers = null;
			}
		}
	}

	public function __construct(){
		$this->handlerSlots = [
			EventPriority::LOWEST => [],
			EventPriority::LOW => [],
			EventPriority::NORMAL => [],
			EventPriority::HIGH => [],
			EventPriority::HIGHEST => [],
			EventPriority::MONITOR => []
		];
		self::$allLists[] = $this;
	}

	/**
	 * @param RegisteredListener $listener
	 *
	 * @throws \Exception
	 */
	public function register(RegisteredListener $listener){
		if($listener->getPriority() < EventPriority::MONITOR or $listener->getPriority() > EventPriority::LOWEST){
			return;
		}
		if(isset($this->handlerSlots[$listener->getPriority()][spl_object_id($listener)])){
			throw new \InvalidStateException("This listener is already registered to priority " . $listener->getPriority());
		}
		$this->handlers = null;
		$this->handlerSlots[$listener->getPriority()][spl_object_id($listener)] = $listener;
	}

	/**
	 * @param RegisteredListener[] $listeners
	 */
	public function registerAll(array $listeners){
		foreach($listeners as $listener){
			$this->register($listener);
		}
	}

	/**
	 * @param RegisteredListener|Listener|Plugin $object
	 */
	public function unregister($object){
		if($object instanceof Plugin or $object instanceof Listener){
			$changed = false;
			foreach($this->handlerSlots as $priority => $list){
				foreach($list as $hash => $listener){
					if(($object instanceof Plugin and $listener->getPlugin() === $object)
						or ($object instanceof Listener and $listener->getListener() === $object)
					){
						unset($this->handlerSlots[$priority][$hash]);
						$changed = true;
					}
				}
			}
			if($changed === true){
				$this->handlers = null;
			}
		}elseif($object instanceof RegisteredListener){
			if(isset($this->handlerSlots[$object->getPriority()][spl_object_id($object)])){
				unset($this->handlerSlots[$object->getPriority()][spl_object_id($object)]);
				$this->handlers = null;
			}
		}
	}

	public function bake(){
		if($this->handlers !== null){
			return;
		}
		$entries = [];
		foreach($this->handlerSlots as $list){
			foreach($list as $hash => $listener){
				$entries[$hash] = $listener;
			}
		}
		$this->handlers = $entries;
	}

	/**
	 * @param null|Plugin $plugin
	 *
	 * @return RegisteredListener[]
	 */
	public function getRegisteredListeners($plugin = null) : array{
		if($plugin !== null){
			$listeners = [];
			foreach($this->getRegisteredListeners(null) as $hash => $listener){
				if($listener->getPlugin() === $plugin){
					$listeners[$hash] = $plugin;
				}
			}

			return $listeners;
		}else{
			while(($handlers = $this->handlers) === null){
				$this->bake();
			}

			return $handlers;
		}
	}

	/**
	 * @return HandlerList[]
	 */
	public static function getHandlerLists() : array{
		return self::$allLists;
	}

}


