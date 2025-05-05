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

namespace pocketmine\network\bedrock\protocol;

#include <rules/DataPacket.h>


use pocketmine\inventory\BrewingRecipe;
use pocketmine\inventory\FurnaceRecipe;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\inventory\ShapelessRecipe;
use pocketmine\item\Item;
use pocketmine\network\bedrock\palette\item\ItemPalette;
use pocketmine\network\bedrock\protocol\types\MaterialReducerRecipe;
use pocketmine\network\bedrock\protocol\types\MaterialReducerRecipeOutput;
use pocketmine\network\bedrock\protocol\types\PotionContainerChangeRecipe;
use pocketmine\network\bedrock\protocol\types\PotionTypeRecipe;
use pocketmine\network\NetworkSession;
use function count;

class CraftingDataPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::CRAFTING_DATA_PACKET;

	public const ENTRY_SHAPELESS = 0;
	public const ENTRY_SHAPED = 1;
	public const ENTRY_FURNACE = 2;
	public const ENTRY_FURNACE_DATA = 3;
	public const ENTRY_MULTI = 4; //TODO
	public const ENTRY_SHULKER_BOX = 5; //TODO
	public const ENTRY_SHAPELESS_CHEMISTRY = 6; //TODO
	public const ENTRY_SHAPED_CHEMISTRY = 7; //TODO
	public const ENTRY_SMITHING_TRANSFORM = 8; // TODO

	public const CRAFTING_TAG_CRAFTING_TABLE = "crafting_table";
	public const CRAFTING_TAG_CARTOGRAPHY_TABLE = "cartography_table";
	public const CRAFTING_TAG_STONECUTTER = "stonecutter";
	public const CRAFTING_TAG_FURNACE = "furnace";
	public const CRAFTING_TAG_CAMPFIRE = "campfire";
	public const CRAFTING_TAG_BLAST_FURNACE = "blast_furnace";
	public const CRAFTING_TAG_SMOKER = "smoker";

	/** @var object[] */
	public $entries = [];
	/** @var PotionTypeRecipe[] */
	public $potionTypeRecipes = [];
	/** @var PotionContainerChangeRecipe[] */
	public $potionContainerRecipes = [];
    /** @var MaterialReducerRecipe[] */
    public $materialReducerRecipes = [];
	/** @var bool */
	public $cleanRecipes = false;

	public function decodePayload(){
		$entries = [];
		$recipeCount = $this->getUnsignedVarInt();
		for($i = 0; $i < $recipeCount; ++$i){
			$entry = [];
			$entry["type"] = $recipeType = $this->getVarInt();

			switch($recipeType){
				case self::ENTRY_SHAPELESS:
				case self::ENTRY_SHULKER_BOX:
					$entry["recipe_id"] = $this->getString();
					$ingredientCount = $this->getUnsignedVarInt();
					/** @var Item */
					$entry["input"] = [];
					for($j = 0; $j < $ingredientCount; ++$j){
						$entry["input"][] = $this->getRecipeIngredient();
					}
					$resultCount = $this->getUnsignedVarInt();
					$entry["output"] = [];
					for($k = 0; $k < $resultCount; ++$k){
						$entry["output"][] = $this->getItemStackWithoutStackId();
					}
					$entry["uuid"] = $this->getUUID()->toString();
					$entry["craftingFlag"] = $this->getString();
					$entry["priority"] = $this->getVarInt();

					break;
				case self::ENTRY_SHAPED:
					$entry["recipe_id"] = $this->getString();
					$entry["width"] = $this->getVarInt();
					$entry["height"] = $this->getVarInt();
					$count = $entry["width"] * $entry["height"];
					$entry["input"] = [];
					for($j = 0; $j < $count; ++$j){
						$entry["input"][] = $this->getRecipeIngredient();
					}
					$resultCount = $this->getUnsignedVarInt();
					$entry["output"] = [];
					for($k = 0; $k < $resultCount; ++$k){
						$entry["output"][] = $this->getItemStackWithoutStackId();
					}
					$entry["uuid"] = $this->getUUID()->toString();
					$entry["craftingFlag"] = $this->getString();
					$entry["priority"] = $this->getVarInt();
					break;
				case self::ENTRY_FURNACE:
				case self::ENTRY_FURNACE_DATA:
					$inputIdNet = $this->getVarInt();
					if($recipeType === self::ENTRY_FURNACE){
						[$inputId, $inputData] = ItemPalette::getLegacyFromRuntimeIdWildcard($inputIdNet, 0x7fff);
					}else{
						$inputMetaNet = $this->getVarInt();
						[$inputId, $inputData] = ItemPalette::getLegacyFromRuntimeIdWildcard($inputIdNet, $inputMetaNet);
					}

					$entry["input"] = Item::get($inputId, $inputData);
					$entry["output"] = $out = $this->getItemStackWithoutStackId();
					if($out->getDamage() === 0x7fff){
						$out->setDamage(0); //TODO HACK: some 1.12 furnace recipe outputs have wildcard damage values
					}
					$entry["craftingFlag"] = $this->getString();
					break;
				case self::ENTRY_MULTI:
					$entry["uuid"] = $this->getUUID()->toString();
					$entry["craftingFlag"] = $this->getString();
					break;
				default:
					throw new \UnexpectedValueException("Unhandled recipe type $recipeType!"); //do not continue attempting to decode
			}
			$entries[] = $entry;
		}
		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			$inputIdNet = $this->getVarInt();
			$inputMetaNet = $this->getVarInt();
			[$inputId, $inputMeta] = ItemPalette::getLegacyFromRuntimeId($inputIdNet, $inputMetaNet);
			$ingredientIdNet = $this->getVarInt();
			$ingredientMetaNet = $this->getVarInt();
			[$ingredientId, $ingredientMeta] = ItemPalette::getLegacyFromRuntimeId($ingredientIdNet, $ingredientMetaNet);
			$outputIdNet = $this->getVarInt();
			$outputMetaNet = $this->getVarInt();
			[$outputId, $outputMeta] = ItemPalette::getLegacyFromRuntimeId($outputIdNet, $outputMetaNet);
			$this->potionTypeRecipes[] = new PotionTypeRecipe($inputId, $inputMeta, $ingredientId, $ingredientMeta, $outputId, $outputMeta);
		}

		for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
			//TODO: we discard inbound ID here, not safe because netID on its own might map to internalID+internalMeta for us
			$inputIdNet = $this->getVarInt();
			[$input, ] = ItemPalette::getLegacyFromRuntimeId($inputIdNet, 0);
			$ingredientIdNet = $this->getVarInt();
			[$ingredient, ] = ItemPalette::getLegacyFromRuntimeId($ingredientIdNet, 0);
			$outputIdNet = $this->getVarInt();
			[$output, ] = ItemPalette::getLegacyFromRuntimeId($outputIdNet, 0);
			$this->potionContainerRecipes[] = new PotionContainerChangeRecipe($input, $ingredient, $output);
		}

        for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
            $inputIdAndData = $this->getVarInt();
            [$inputId, $inputMeta] = [$inputIdAndData >> 16, $inputIdAndData & 0x7fff];
            $outputs = [];
            for($j = 0, $outputCount = $this->getUnsignedVarInt(); $j < $outputCount; ++$j){
                $outputItemId = $this->getVarInt();
                $outputItemCount = $this->getVarInt();
                $outputs[] = new MaterialReducerRecipeOutput($outputItemId, $outputItemCount);
            }
            $this->materialReducerRecipes[] = new MaterialReducerRecipe($inputId, $inputMeta, $outputs);
        }

		$this->cleanRecipes = $this->getBool();
	}

	protected function writeEntry($entry, int $index) : void{
		if($entry instanceof ShapelessRecipe){
			$this->putVarInt(CraftingDataPacket::ENTRY_SHAPELESS);
			$this->writeShapelessRecipe($entry, $index);
		}elseif($entry instanceof ShapedRecipe){
			$this->putVarInt(CraftingDataPacket::ENTRY_SHAPED);
			$this->writeShapedRecipe($entry, $index);
		}elseif($entry instanceof FurnaceRecipe){
			$this->putVarInt(CraftingDataPacket::ENTRY_FURNACE_DATA);
			$this->writeFurnaceRecipe($entry);
		}else{
			$this->putVarInt(-1);
		}
	}

	protected function writeShapelessRecipe(ShapelessRecipe $recipe, int $networkId) : void{
		$this->putString($recipe->getId()->toString());
		$this->putUnsignedVarInt(count($recipe->getIngredientList()));
		foreach($recipe->getIngredientList() as $item){
			$this->putRecipeIngredient($item);
		}

		$this->putUnsignedVarInt(1);
		$this->putItemStackWithoutStackId($recipe->getResult());

		$this->putUUID($recipe->getId());
		$this->putString(self::CRAFTING_TAG_CRAFTING_TABLE);
		$this->putVarInt(50); //priority (???)
        $this->putBool(true);

		$this->putVarInt($networkId);
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
        $this->putBool(true); //symmetry
        $this->putBool(true);

		$this->putVarInt($networkId);
	}

	protected function writeFurnaceRecipe(FurnaceRecipe $recipe) : void{
		$input = $recipe->getInput();
		if($input->hasAnyDamageValue()){
			[$netId, ] = ItemPalette::getRuntimeFromLegacyId($input->getId(), 0);
			$netData = 0x7fff;
		}else{
			[$netId, $netData] = ItemPalette::getRuntimeFromLegacyId($input->getId(), $input->getDamage());
		}
		$this->putVarInt($netId);
		$this->putVarInt($netData);
		$this->putItemStackWithoutStackId($recipe->getResult());
		$this->putString(self::CRAFTING_TAG_FURNACE); //TODO: blocktype (no prefix) (this might require internal API breaks)
	}

	public function addShapelessRecipe(ShapelessRecipe $recipe){
		$this->entries[] = $recipe;
	}

	public function addShapedRecipe(ShapedRecipe $recipe){
		$this->entries[] = $recipe;
	}

	public function addFurnaceRecipe(FurnaceRecipe $recipe){
		$this->entries[] = $recipe;
	}

    public function addBrewingStand(BrewingRecipe $recipe):void{
        $input = $recipe->getPotion();
        $ingredient = $recipe->getInput();
        $output = $recipe->getResult();
        $potionTypeRecipes = new PotionTypeRecipe($input->getId(), $input->getDamage(), $ingredient->getId(), $ingredient->getDamage(), $output->getId(), $output->getDamage());
        $this->potionTypeRecipes[] = $potionTypeRecipes;
    }

    public function encodePayload(){
		$this->putUnsignedVarInt(count($this->entries));

		$networkId = 1;
		foreach($this->entries as $d){
			$this->writeEntry($d, $networkId++);
		}

		$this->putUnsignedVarInt(count($this->potionTypeRecipes));
        foreach($this->potionTypeRecipes as $entry){
            $this->writePotionTypes($entry);
        }

        $this->putUnsignedVarInt(count($this->potionContainerRecipes));
        foreach($this->potionContainerRecipes as $entry){
            $this->putVarInt($entry->getInputItemId());
            $this->putVarInt($entry->getIngredientItemId());
            $this->putVarInt($entry->getOutputItemId());
        }

        $this->putUnsignedVarInt(count($this->materialReducerRecipes));
        foreach($this->materialReducerRecipes as $recipe){
            $this->putVarInt(($recipe->getInputItemId() << 16) | $recipe->getInputItemMeta());
            $this->putUnsignedVarInt(count($recipe->getOutputs()));
            foreach($recipe->getOutputs() as $output){
                $this->putVarInt($output->getItemId());
                $this->putVarInt($output->getCount());
            }
        }

		$this->putBool($this->cleanRecipes);
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCraftingData($this);
	}
}


