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

namespace pocketmine\network\bedrock\adapter\v428\protocol\types;

use pocketmine\network\bedrock\adapter\v428\protocol\InventoryTransactionPacket as InventoryTransactionPacket428;
use pocketmine\network\bedrock\protocol\InventoryTransactionPacket;
use function count;


trait TransactionDataTrait{
	/** @var NetworkInventoryAction[] */
	protected $actions = [];

	/**
	 * @param InventoryTransactionPacket $packet
	 *
	 * @throws \OutOfBoundsException
	 * @throws \UnexpectedValueException
	 */
	public function decode(InventoryTransactionPacket $packet) : void{
		/** @var InventoryTransactionPacket428 $packet */
		$actionCount = $packet->getUnsignedVarInt();
		if($actionCount > self::MAX_ACTION_COUNT){
			throw new \UnexpectedValueException("Too big action count: $actionCount");
		}
		for($i = 0; $i < $actionCount; ++$i){
			$this->actions[] = (new NetworkInventoryAction())->read($packet);
		}
		$this->decodeData($packet);
	}

	public function encode(InventoryTransactionPacket $packet) : void{
		/** @var InventoryTransactionPacket428 $packet */

		$packet->putUnsignedVarInt(count($this->actions));
		foreach($this->actions as $action){
			$action->write($packet);
		}
		$this->encodeData($packet);
	}

}


