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

namespace pocketmine\event\block;

use pocketmine\block\Block;
use pocketmine\event\Cancellable;

/**
 * Called when a block is burned away by fire.
 */
class BlockBurnEvent extends BlockEvent implements Cancellable{
    public static $handlerList = null;

	/** @var Block */
	private $causingBlock;

	public function __construct(Block $block, Block $causingBlock){
		parent::__construct($block);
		$this->causingBlock = $causingBlock;
	}

	/**
	 * Returns the block (usually Fire) which caused the target block to be burned away.
	 */
	public function getCausingBlock() : Block{
		return $this->causingBlock;
	}
}


