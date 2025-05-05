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



namespace pocketmine\event\block;

use pocketmine\block\Block;
use pocketmine\event\Cancellable;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\tile\ItemFrame;

class ItemFrameDropItemEvent extends BlockEvent implements Cancellable{
	public static $handlerList = null;

	/** @var  Player */
	private $player;
	/** @var  Item */
	private $item;
	/** @var  ItemFrame */
	private $itemFrame;

	/**
	 * ItemFrameDropItemEvent constructor.
	 *
	 * @param Player $player
	 * @param Block $block
	 * @param ItemFrame $itemFrame
	 * @param Item $item
	 */
	public function __construct(Player $player, Block $block, ItemFrame $itemFrame, Item $item){
		$this->player = $player;
		$this->block = $block;
		$this->itemFrame = $itemFrame;
		$this->item = $item;
	}

	/**
	 * @return Player
	 */
	public function getPlayer(){
		return $this->player;
	}

	/**
	 * @return ItemFrame
	 */
	public function getItemFrame(){
		return $this->itemFrame;
	}

	/**
	 * @return Item
	 */
	public function getItem(){
		return $this->item;
	}

    public function setItem(Item $item) :void{
        $this->item = $item;
    }
}


