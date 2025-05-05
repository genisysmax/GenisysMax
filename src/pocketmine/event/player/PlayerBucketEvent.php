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

namespace pocketmine\event\player;

use pocketmine\block\Block;
use pocketmine\event\Cancellable;
use pocketmine\item\Item;
use pocketmine\Player;

abstract class PlayerBucketEvent extends PlayerEvent implements Cancellable{

	/** @var Block */
	private $blockClicked;
	/** @var int */
	private $blockFace;
	/** @var Item */
	private $bucket;
	/** @var Item */
	private $item;

	/**
	 * @param Player $who
	 * @param Block  $blockClicked
	 * @param int    $blockFace
	 * @param Item   $bucket
	 * @param Item   $itemInHand
	 */
	public function __construct(Player $who, Block $blockClicked, int $blockFace, Item $bucket, Item $itemInHand){
		$this->player = $who;
		$this->blockClicked = $blockClicked;
		$this->blockFace = $blockFace;
		$this->item = $itemInHand;
		$this->bucket = $bucket;
	}

	/**
	 * Returns the bucket used in this event
	 *
	 * @return Item
	 */
	public function getBucket() : Item{
		return $this->bucket;
	}

	/**
	 * Returns the item in hand after the event
	 *
	 * @return Item
	 */
	public function getItem() : Item{
		return $this->item;
	}

	/**
	 * @param Item $item
	 */
	public function setItem(Item $item){
		$this->item = $item;
	}

	/**
	 * @return Block
	 */
	public function getBlockClicked() : Block{
		return $this->blockClicked;
	}

	/**
	 * @return int
	 */
	public function getBlockFace() : int{
		return $this->blockFace;
	}
}


