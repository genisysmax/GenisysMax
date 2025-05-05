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

namespace pocketmine\network\bedrock\adapter\v422\protocol\types\itemStack;

use InvalidArgumentException;
use pocketmine\network\bedrock\adapter\v428\protocol\types\itemStack\AutoCraftRecipeStackRequestAction;
use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\types\itemStack\BeaconPaymentStackRequestAction;
use pocketmine\network\bedrock\protocol\types\itemStack\ConsumeStackRequestAction;
use pocketmine\network\bedrock\protocol\types\itemStack\CraftCreativeStackRequestAction;
use pocketmine\network\bedrock\protocol\types\itemStack\CraftNonImplementedStackRequestAction;
use pocketmine\network\bedrock\protocol\types\itemStack\CraftRecipeStackRequestAction;
use pocketmine\network\bedrock\protocol\types\itemStack\CraftResultsDeprecatedStackRequestAction;
use pocketmine\network\bedrock\protocol\types\itemStack\CreateStackRequestAction;
use pocketmine\network\bedrock\protocol\types\itemStack\DestroyStackRequestAction;
use pocketmine\network\bedrock\protocol\types\itemStack\DropStackRequestAction;
use pocketmine\network\bedrock\protocol\types\itemStack\LabTableCombineStackRequestAction;
use pocketmine\network\bedrock\protocol\types\itemStack\PlaceStackRequestAction;
use pocketmine\network\bedrock\protocol\types\itemStack\StackRequestAction;
use pocketmine\network\bedrock\protocol\types\itemStack\SwapStackRequestAction;
use pocketmine\network\bedrock\protocol\types\itemStack\TakeStackRequestAction;
use UnexpectedValueException;
use function count;

class ItemStackRequest extends \pocketmine\network\bedrock\adapter\v428\protocol\types\itemStack\ItemStackRequest{

	public const ACTION_TAKE = 0;
	public const ACTION_PLACE = 1;
	public const ACTION_SWAP = 2;
	public const ACTION_DROP = 3;
	public const ACTION_DESTROY = 4;
	public const ACTION_CONSUME = 5;
	public const ACTION_CREATE = 6;
	public const ACTION_LAB_TABLE_COMBINE = 7;
	public const ACTION_BEACON_PAYMENT = 8;
	public const ACTION_CRAFT_RECIPE = 9;
	public const ACTION_CRAFT_RECIPE_AUTO = 10;
	public const ACTION_CRAFT_CREATIVE = 11;
	public const ACTION_CRAFT_NON_IMPLEMENTED_DEPRECATED = 12;
	public const ACTION_CRAFT_RESULTS_DEPRECATED = 13;

	public function write(DataPacket $out) : void{
		$out->putVarInt($this->requestId);

		$out->putUnsignedVarInt(count($this->actions));
		foreach($this->actions as $action){
			$actionId = $action->getActionId();
			if($actionId === parent::ACTION_MINE_BLOCK){
				throw new InvalidArgumentException("Unsupported action ID for protocol 422: {$actionId}");
			}elseif($actionId > parent::ACTION_MINE_BLOCK){
				--$actionId;
			}

			$out->putByte($actionId);
			$action->encode($out);
		}

		$out->putUnsignedVarInt(count($this->filterStrings));
		foreach($this->filterStrings as $filterString){
			$out->putString($filterString);
		}
	}

	protected function getActionById(int $actionType) : ?StackRequestAction{
		switch($actionType){
			case self::ACTION_TAKE:
				return new TakeStackRequestAction();
			case self::ACTION_PLACE:
				return new PlaceStackRequestAction();
			case self::ACTION_SWAP:
				return new SwapStackRequestAction();
			case self::ACTION_DROP:
				return new DropStackRequestAction();
			case self::ACTION_DESTROY:
				return new DestroyStackRequestAction();
			case self::ACTION_CONSUME:
				return new ConsumeStackRequestAction();
			case self::ACTION_CREATE:
				return new CreateStackRequestAction();
			case self::ACTION_LAB_TABLE_COMBINE:
				return new LabTableCombineStackRequestAction();
			case self::ACTION_BEACON_PAYMENT:
				return new BeaconPaymentStackRequestAction();
			case self::ACTION_CRAFT_RECIPE:
				return new CraftRecipeStackRequestAction();
			case self::ACTION_CRAFT_RECIPE_AUTO:
				return new AutoCraftRecipeStackRequestAction();
			case self::ACTION_CRAFT_CREATIVE:
				return new CraftCreativeStackRequestAction();
			case self::ACTION_CRAFT_NON_IMPLEMENTED_DEPRECATED:
				return new CraftNonImplementedStackRequestAction();
			case self::ACTION_CRAFT_RESULTS_DEPRECATED:
				return new CraftResultsDeprecatedStackRequestAction();
			default:
				throw new UnexpectedValueException("Unknown item stack request action type {$actionType}");
		}
	}
}

