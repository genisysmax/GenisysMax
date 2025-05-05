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

namespace pocketmine\network;

use Closure;
use InvalidArgumentException;
use InvalidStateException;
use pocketmine\utils\Utils;

class CompressBatchPromise{
	/** @var Closure[] */
	private $callbacks = [];

	/** @var string|null */
	private $result = null;

	/** @var bool */
	private $cancelled = false;

	/**
	 * @param Closure ...$callbacks
	 */
	public function onResolve(Closure ...$callbacks) : void{
		$this->checkCancelled();
		foreach($callbacks as $callback){
			Utils::validateCallableSignature(function(CompressBatchPromise $promise) : void{}, $callback);
		}
		if($this->result !== null){
			foreach($callbacks as $callback){
				$callback($this);
			}
		}else{
			array_push($this->callbacks, ...$callbacks);
		}
	}

	public function resolve(string $result) : void{
		if(!$this->cancelled){
			if($this->result !== null){
				throw new InvalidStateException("Cannot resolve promise more than once");
			}
			$this->result = $result;
			foreach($this->callbacks as $callback){
				$callback($this);
			}
			$this->callbacks = [];
		}
	}

	/**
	 * @return Closure[]
	 */
	public function getResolveCallbacks() : array{
		return $this->callbacks;
	}

	public function getResult() : string{
		$this->checkCancelled();
		if($this->result === null){
			throw new InvalidStateException("Promise has not yet been resolved");
		}
		return $this->result;
	}

	public function hasResult() : bool{
		return $this->result !== null;
	}

	public function isCancelled() : bool{
		return $this->cancelled;
	}

	public function cancel() : void{
		if($this->hasResult()){
			throw new InvalidStateException("Cannot cancel a resolved promise");
		}
		$this->cancelled = true;
	}

	private function checkCancelled() : void{
		if($this->cancelled){
			throw new InvalidArgumentException("Promise has been cancelled");
		}
	}
}

