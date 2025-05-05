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

namespace pocketmine\nbt;

use Closure;

class ReaderTracker{

	/** @var int */
	private $maxDepth;
	/** @var int */
	private $currentDepth = 0;

	public function __construct(int $maxDepth){
		$this->maxDepth = $maxDepth;
	}

	/**
	 * @param Closure $execute
	 */
	public function protectDepth(Closure $execute) : void{
		if($this->maxDepth > 0 and ++$this->currentDepth > $this->maxDepth){
			throw new NbtDataException("Nesting level too deep: reached max depth of $this->maxDepth tags");
		}
		try{
			$execute();
		}finally{
			--$this->currentDepth;
		}
	}
}


