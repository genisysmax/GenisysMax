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

namespace pocketmine\network\bedrock\adapter\v419\protocol\types\itemStack;

use InvalidArgumentException;
use pocketmine\network\bedrock\protocol\DataPacket;
use function count;

class ItemStackRequest extends \pocketmine\network\bedrock\adapter\v422\protocol\types\itemStack\ItemStackRequest{

	public function read(DataPacket $in) : void{
		$this->requestId = $in->getVarInt();

		$this->actions = [];
		for($i = 0, $actionCount = $in->getUnsignedVarInt(); $i < $actionCount; ++$i){
			$actionType = $in->getByte();
			$action = $this->getActionById($actionType);
			if($action === null){
				continue;
			}

			$action->decode($in);
			$this->actions[] = $action;
		}
	}

	public function write(DataPacket $out) : void{
		$out->putVarInt($this->requestId);

		$out->putUnsignedVarInt(count($this->actions));
		foreach($this->actions as $action){
			$actionId = $action->getActionId();
			if($actionId === parent::ACTION_MINE_BLOCK){
				throw new InvalidArgumentException("Unsupported action ID for protocol 419: {$actionId}");
			}elseif($actionId > parent::ACTION_MINE_BLOCK){
				--$actionId;
			}

			$out->putByte($actionId);
			$action->encode($out);
		}
	}
}

