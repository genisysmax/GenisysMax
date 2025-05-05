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

namespace pocketmine\network\bedrock\adapter\v545\protocol\types\itemStack;

use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\types\itemStack\StackRequestAction;
use function count;

class ItemStackRequest extends \pocketmine\network\bedrock\protocol\types\itemStack\ItemStackRequest{

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

		$this->filterStrings = [];
		for($i = 0, $stringsCount = $in->getUnsignedVarInt(); $i < $stringsCount; ++$i){
			$this->filterStrings[] = $in->getString();
		}
	}

	public function write(DataPacket $out) : void{
		$out->putUnsignedVarInt($this->requestId);

		$out->putUnsignedVarInt(count($this->actions));
		foreach($this->actions as $action){
			$out->putByte($action->getActionId());
			$action->encode($out);
		}

		$out->putUnsignedVarInt(count($this->filterStrings));
		foreach($this->filterStrings as $filterString){
			$out->putString($filterString);
		}
	}

	protected function getActionById(int $actionType) : ?StackRequestAction{
		if($actionType === self::ACTION_CRAFT_RECIPE_AUTO){
			return new AutoCraftRecipeStackRequestAction();
		}
		return parent::getActionById($actionType);
	}
}

