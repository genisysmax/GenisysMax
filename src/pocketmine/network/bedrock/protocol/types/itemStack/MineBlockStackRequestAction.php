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

class MineBlockStackRequestAction extends StackRequestAction{

	/** @var int */
	protected $unknown1;
	/** @var int */
	protected $predictedDurability;
	/** @var int */
	protected $stackId;

	/**
	 * @return int
	 */
	public function getUnknown1() : int{
		return $this->unknown1;
	}

	/**
	 * @return int
	 */
	public function getPredictedDurability() : int{
		return $this->predictedDurability;
	}

	/**
	 * @return int
	 */
	public function getStackId() : int{
		return $this->stackId;
	}

	public function getActionId() : int{
		return ItemStackRequestPacket::ACTION_MINE_BLOCK;
	}

	public function decode(DataPacket $stream) : void{
		$this->unknown1 = $stream->getVarInt();
		$this->predictedDurability = $stream->getVarInt();
		$this->stackId = $stream->getVarInt();
	}

	public function encode(DataPacket $stream) : void{
		$stream->putVarInt($this->unknown1);
		$stream->putVarInt($this->predictedDurability);
		$stream->putVarInt($this->stackId);
	}
}

