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

use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\ItemStackRequestPacket;

class CreateStackRequestAction extends StackRequestAction{

	/** @var int */
	protected $resultsSlot;

	/**
	 * @return int
	 */
	public function getResultsSlot() : int{
		return $this->resultsSlot;
	}

	public function getActionId() : int{
		return ItemStackRequestPacket::ACTION_CREATE;
	}

	public function decode(DataPacket $stream) : void{
		$this->resultsSlot = $stream->getByte();
	}

	public function encode(DataPacket $stream) : void{
		$stream->putByte($this->resultsSlot);
	}
}

