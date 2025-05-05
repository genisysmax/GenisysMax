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

namespace pocketmine\inventory;

use pocketmine\item\Item;
use pocketmine\Server;
use pocketmine\utils\UUID;

class FurnaceRecipe implements Recipe{

	/** @var UUID|null */
	private $id = null;

	/** @var Item */
	private $output;

	/** @var Item */
	private $ingredient;

	/**
	 * @param Item $result
	 * @param Item $ingredient
	 */
	public function __construct(Item $result, Item $ingredient){
		$this->output = clone $result;
		$this->ingredient = clone $ingredient;
	}

	/**
	 * @return UUID|null
	 */
	public function getId(){
		return $this->id;
	}

	/**
	 * @param UUID $id
	 */
	public function setId(UUID $id){
		if($this->id !== null){
			throw new \InvalidStateException("Id is already set");
		}

		$this->id = $id;
	}

	/**
	 * @param Item $item
	 */
	public function setInput(Item $item){
		$this->ingredient = clone $item;
	}

	/**
	 * @return Item
	 */
	public function getInput() : Item{
		return clone $this->ingredient;
	}

	/**
	 * @return Item
	 */
	public function getResult() : Item{
		return clone $this->output;
	}

	public function registerToCraftingManager(){
		Server::getInstance()->getCraftingManager()->registerFurnaceRecipe($this);
	}
}

