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

namespace pocketmine\plugin;

use pocketmine\event\Cancellable;
use pocketmine\event\Event;
use pocketmine\event\Listener;
use pocketmine\event\TimingsHandler;

class RegisteredListener{

	/** @var Listener */
	private $listener;

	/** @var int */
	private $priority;

	/** @var Plugin */
	private $plugin;

	/** @var EventExecutor */
	private $executor;

	/** @var bool */
	private $ignoreCancelled;

	/** @var TimingsHandler */
	private $timings;


	/**
	 * @param Listener       $listener
	 * @param EventExecutor  $executor
	 * @param int            $priority
	 * @param Plugin         $plugin
	 * @param bool           $ignoreCancelled
	 * @param TimingsHandler $timings
	 */
	public function __construct(Listener $listener, EventExecutor $executor, int $priority, Plugin $plugin, bool $ignoreCancelled, TimingsHandler $timings){
		$this->listener = $listener;
		$this->priority = $priority;
		$this->plugin = $plugin;
		$this->executor = $executor;
		$this->ignoreCancelled = $ignoreCancelled;
		$this->timings = $timings;
	}

	/**
	 * @return Listener
	 */
	public function getListener() : Listener{
		return $this->listener;
	}

	/**
	 * @return Plugin
	 */
	public function getPlugin() : Plugin{
		return $this->plugin;
	}

	/**
	 * @return int
	 */
	public function getPriority() : int{
		return $this->priority;
	}

	/**
	 * @param Event $event
	 */
	public function callEvent(Event $event){
		if($event instanceof Cancellable and $event->isCancelled() and $this->isIgnoringCancelled()){
			return;
		}
		$this->timings->startTiming();
		$this->executor->execute($this->listener, $event);
		$this->timings->stopTiming();
	}

	public function __destruct(){
		$this->timings->remove();
	}

	/**
	 * @return bool
	 */
	public function isIgnoringCancelled() : bool{
		return $this->ignoreCancelled === true;
	}
}

