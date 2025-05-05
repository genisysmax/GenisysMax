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

namespace pocketmine\event\inventory;

use pocketmine\event\block\BlockEvent;
use pocketmine\event\Cancellable;
use pocketmine\item\Item;
use pocketmine\tile\Furnace;

class FurnaceSmeltEvent extends BlockEvent implements Cancellable{
	public static $handlerList = null;

	/** @var Furnace */
	private $furnace;
	/** @var Item */
	private $source;
	/** @var Item */
	private $result;

	/**
	 * @param Furnace $furnace
	 * @param Item $source
	 * @param Item $result
	 */
	public function __construct(Furnace $furnace, Item $source, Item $result){
		parent::__construct($furnace->getBlock());
		$this->source = clone $source;
		$this->source->setCount(1);
		$this->result = $result;
		$this->furnace = $furnace;
	}

	/**
	 * @return Furnace
	 */
	public function getFurnace() : Furnace{
		return $this->furnace;
	}

	/**
	 * @return Item
	 */
	public function getSource() : Item{
		return $this->source;
	}

	/**
	 * @return Item
	 */
	public function getResult() : Item{
		return $this->result;
	}

	/**
	 * @param Item $result
	 */
	public function setResult(Item $result){
		$this->result = $result;
	}
}

