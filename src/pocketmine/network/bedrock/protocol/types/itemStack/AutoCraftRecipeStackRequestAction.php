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
use function count;

class AutoCraftRecipeStackRequestAction extends StackRequestAction{

	/** @var int */
	protected $recipeNetworkId;
	/** @var int */
	protected $repetitions;
	/** @var Item[] */
	protected $ingredients = [];

	/**
	 * @return int
	 */
	public function getRecipeNetworkId() : int{
		return $this->recipeNetworkId;
	}

	/**
	 * @return int
	 */
	public function getRepetitions() : int{
		return $this->repetitions;
	}

	/**
	 * @return Item[]
	 */
	public function getIngredients() : array{
		return $this->ingredients;
	}

	public function getActionId() : int{
		return ItemStackRequestPacket::ACTION_CRAFT_RECIPE_AUTO;
	}

	public function decode(DataPacket $stream) : void{
		$this->recipeNetworkId = $stream->getUnsignedVarInt();
		$this->repetitions = $stream->getByte();
		$this->ingredients = [];
		for($i = 0, $count = $stream->getUnsignedVarInt(); $i < $count; ++$i){
			$this->ingredients[] = $stream->getRecipeIngredient();
		}
	}

	public function encode(DataPacket $stream) : void{
		$stream->putUnsignedVarInt($this->recipeNetworkId);
		$stream->putByte($this->repetitions);
		$stream->putUnsignedVarInt(count($this->ingredients));
		foreach($this->ingredients as $ingredient){
			$stream->putRecipeIngredient($ingredient);
		}
	}
}

