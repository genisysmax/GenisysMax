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

namespace pocketmine\network\bedrock\protocol\types\inventory;

use pocketmine\item\Item;

class ItemInstance{

	/** @var int */
	public $stackNetworkId;
	/** @var Item */
	public $stack;

	public function __construct(?int $stackNetworkId = null, ?Item $stack = null){
		$this->stackNetworkId = $stackNetworkId;
		$this->stack = $stack;
	}

	public static function legacy(Item $itemStack) : self{
		return new self($itemStack->isNull() ? 0 : 1, $itemStack);
	}
}

