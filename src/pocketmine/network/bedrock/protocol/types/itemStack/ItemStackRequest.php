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
use UnexpectedValueException;
use function count;

class ItemStackRequest{

	public const ACTION_TAKE = 0;
	public const ACTION_PLACE = 1;
	public const ACTION_SWAP = 2;
	public const ACTION_DROP = 3;
	public const ACTION_DESTROY = 4;
	public const ACTION_CONSUME = 5;
	public const ACTION_CREATE = 6;
	public const ACTION_PLACE_INTO_BUNDLE = 7;
	public const ACTION_TAKE_FROM_BUNDLE = 8;
	public const ACTION_LAB_TABLE_COMBINE = 9;
	public const ACTION_BEACON_PAYMENT = 10;
	public const ACTION_MINE_BLOCK = 11;
	public const ACTION_CRAFT_RECIPE = 12;
	public const ACTION_CRAFT_RECIPE_AUTO = 13; //recipe book?
	public const ACTION_CRAFT_CREATIVE = 14;
	public const ACTION_CRAFT_RECIPE_OPTIONAL = 15; //anvil/cartography table rename
	public const ACTION_CRAFT_GRINDSTONE = 16;
	public const ACTION_CRAFT_LOOM = 17;
	public const ACTION_CRAFT_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING = 18;
	public const ACTION_CRAFT_RESULTS_DEPRECATED_ASK_TY_LAING = 19; //no idea what this is for

	/** @var int|null */
	public $requestId;
	/** @var StackRequestAction[]|null */
	public $actions;
	/** @var string[]|null */
	public $filterStrings;
	/** @var int|null */
	public $filterStringClause;

	public function __construct(?int $requestId = null, ?array $actions = null, ?array $filterStrings = null, ?int $filterStringClause = null){
		$this->requestId = $requestId;
		$this->actions = $actions;
		$this->filterStrings = $filterStrings;
		$this->filterStringClause = $filterStringClause;
	}

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
		$this->filterStringClause = $in->getLInt();
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

		$out->putLInt($this->filterStringClause);
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
			case self::ACTION_MINE_BLOCK:
				return new MineBlockStackRequestAction();
			case self::ACTION_CRAFT_RECIPE:
				return new CraftRecipeStackRequestAction();
			case self::ACTION_CRAFT_RECIPE_AUTO:
				return new AutoCraftRecipeStackRequestAction();
			case self::ACTION_CRAFT_CREATIVE:
				return new CraftCreativeStackRequestAction();
			case self::ACTION_CRAFT_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING:
				return new CraftNonImplementedStackRequestAction();
			case self::ACTION_CRAFT_RESULTS_DEPRECATED_ASK_TY_LAING:
				return new CraftResultsDeprecatedStackRequestAction();
			case self::ACTION_PLACE_INTO_BUNDLE:
			case self::ACTION_TAKE_FROM_BUNDLE:
			case self::ACTION_CRAFT_RECIPE_OPTIONAL:
			case self::ACTION_CRAFT_GRINDSTONE:
			case self::ACTION_CRAFT_LOOM:
				// TODO
				return null;
			default:
				throw new UnexpectedValueException("Unknown item stack request action type {$actionType}");
		}
	}
}

