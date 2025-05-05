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

namespace pocketmine\network\bedrock\protocol\types\itemStack;

use pocketmine\item\Item;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\ItemStackRequestPacket;
use UnexpectedValueException;
use function count;

class CraftResultsDeprecatedStackRequestAction extends StackRequestAction{

	/** @var Item[] */
	protected $resultItems = [];
	/** @var int */
	protected $timesCrafted;

	/**
	 * @return Item[]
	 */
	public function getResultItems() : array{
		return $this->resultItems;
	}

	/**
	 * @return int
	 */
	public function getTimesCrafted() : int{
		return $this->timesCrafted;
	}

	public function getActionId() : int{
		return ItemStackRequestPacket::ACTION_CRAFT_RESULTS_DEPRECATED;
	}

	public function decode(DataPacket $stream) : void{
		$count = $stream->getUnsignedVarInt();
		if($count > 128){
			throw new UnexpectedValueException("Too many result items: $count");
		}

		for($i = 0; $i < $count; ++$i){
			$this->resultItems[] = $stream->getItemStackWithoutStackId();
		}

		$this->timesCrafted = $stream->getByte();
	}

	public function encode(DataPacket $stream) : void{
		$stream->putUnsignedVarInt(count($this->resultItems));
		foreach($this->resultItems as $item){
			$stream->putItemStackWithoutStackId($item);
		}

		$stream->putByte($this->timesCrafted);
	}
}


