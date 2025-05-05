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

/**
 * Manages crafting operations
 * This class includes future methods for shaped crafting
 *
 * TODO: add small matrix inventory
 */
class CraftingInventory extends BaseInventory{

	/** @var Inventory */
	private $resultInventory;

	/**
	 * @param InventoryHolder $holder
	 * @param Inventory       $resultInventory
	 * @param InventoryType   $inventoryType
	 *
	 * @throws \Exception
	 */
	public function __construct(InventoryHolder $holder, Inventory $resultInventory, InventoryType $inventoryType){
		if($inventoryType->getDefaultTitle() !== "Crafting"){
			throw new \InvalidStateException("Invalid Inventory type, expected CRAFTING or WORKBENCH");
		}
		$this->resultInventory = $resultInventory;
		parent::__construct($holder, $inventoryType);
	}

	/**
	 * @return Inventory
	 */
	public function getResultInventory(){
		return $this->resultInventory;
	}

	public function getSize() : int{
		return $this->getResultInventory()->getSize() + parent::getSize();
	}
}

