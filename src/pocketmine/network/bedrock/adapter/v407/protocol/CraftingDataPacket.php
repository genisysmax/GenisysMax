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

namespace pocketmine\network\bedrock\adapter\v407\protocol;

use pocketmine\inventory\FurnaceRecipe;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\inventory\ShapelessRecipe;

class CraftingDataPacket extends \pocketmine\network\bedrock\adapter\v448\protocol\CraftingDataPacket {
	use PacketTrait;

	protected function writeEntry($entry, int $index) : void{
		if($entry instanceof ShapelessRecipe){
			$this->putVarInt(self::ENTRY_SHAPELESS);
			$this->writeShapelessRecipe($entry, $index);
		}elseif($entry instanceof ShapedRecipe){
			$this->putVarInt(self::ENTRY_SHAPED);
			$this->writeShapedRecipe($entry, $index);
		}elseif($entry instanceof FurnaceRecipe){
			if(!$entry->getInput()->hasAnyDamageValue()){
				$this->putVarInt(self::ENTRY_FURNACE);
				$this->writeFurnaceRecipe($entry);
			}else{
				$this->putVarInt(self::ENTRY_FURNACE_DATA);
				$this->writeFurnaceRecipeData($entry);
			}
		}else{
			$this->putVarInt(-1);
		}
	}

	protected function writeShapelessRecipe(ShapelessRecipe $recipe, int $networkId) : void{
		$this->putString($recipe->getId()->toString());
		$this->putUnsignedVarInt($recipe->getIngredientCount());
		foreach($recipe->getIngredientList() as $item){
			$this->putRecipeIngredient($item);
		}

		$this->putUnsignedVarInt(1);
		$this->putItemStackWithoutStackId($recipe->getResult());

		$this->putUUID($recipe->getId());
		$this->putString(self::CRAFTING_TAG_CRAFTING_TABLE);

		$this->putVarInt(50); //priority (???)
		$this->putUnsignedVarInt($networkId);
	}

	protected function writeShapedRecipe(ShapedRecipe $recipe, int $networkId) : void{
		$this->putString($recipe->getId()->toString());
		$this->putVarInt($recipe->getWidth());
		$this->putVarInt($recipe->getHeight());

		for($z = 0; $z < $recipe->getHeight(); ++$z){
			for($x = 0; $x < $recipe->getWidth(); ++$x){
				$this->putRecipeIngredient($recipe->getIngredient($x, $z));
			}
		}

		$this->putUnsignedVarInt(1);
		$this->putItemStackWithoutStackId($recipe->getResult());

		$this->putUUID($recipe->getId());
		$this->putString(self::CRAFTING_TAG_CRAFTING_TABLE);

		$this->putVarInt(50); //priority (???)
		$this->putUnsignedVarInt($networkId);
	}

	protected function writeFurnaceRecipe(FurnaceRecipe $recipe) : void{
		$this->putVarInt($recipe->getInput()->getId());
		$this->putItemStackWithoutStackId($recipe->getResult());
		$this->putString(self::CRAFTING_TAG_FURNACE);
	}
	
	protected function writeFurnaceRecipeData(FurnaceRecipe $recipe) : void{
		$this->putVarInt($recipe->getInput()->getId());
		$this->putVarInt($recipe->getInput()->getDamage());
		$this->putItemStackWithoutStackId($recipe->getResult());
		$this->putString(self::CRAFTING_TAG_FURNACE);
	}
}

