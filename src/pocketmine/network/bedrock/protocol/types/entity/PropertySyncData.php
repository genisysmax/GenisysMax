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

namespace pocketmine\network\bedrock\protocol\types\entity;

use pocketmine\network\bedrock\protocol\DataPacket;

class PropertySyncData{

	/** @var int */
	public $intProperties = [];
	/** @var float */
	public $floatProperties = [];

	public function __construct(array $intProperties = [], array $floatProperties = []){
		$this->intProperties = $intProperties;
		$this->floatProperties = $floatProperties;
	}

	public static function read(DataPacket $pk) : self{
		$result = new self;
		for($i = 0, $count = $pk->getUnsignedVarInt(); $i < $count; ++$i){
			$result->intProperties[$pk->getUnsignedVarInt()] = $pk->getVarInt();
		}
		for($i = 0, $count = $pk->getUnsignedVarInt(); $i < $count; ++$i){
			$result->floatProperties[$pk->getUnsignedVarInt()] = $pk->getLFloat();
		}
		return $result;
	}

	public function write(DataPacket $pk) : void{
		$pk->putUnsignedVarInt(count($this->intProperties));
		foreach($this->intProperties as $key => $value){
			$pk->putUnsignedVarInt($key);
			$pk->putVarInt($value);
		}
		$pk->putUnsignedVarInt(count($this->floatProperties));
		foreach($this->floatProperties as $key => $value){
			$pk->putUnsignedVarInt($key);
			$pk->putLFloat($value);
		}
	}
}

