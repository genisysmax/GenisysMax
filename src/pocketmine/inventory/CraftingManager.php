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

namespace pocketmine\inventory;

use pocketmine\event\Timings;
use pocketmine\item\Item;
use pocketmine\item\Potion;
use pocketmine\network\bedrock\adapter\ProtocolAdapterFactory;
use pocketmine\network\bedrock\BedrockPacketBatch;
use pocketmine\network\bedrock\NetworkCompression as BedrockNetworkCompression;
use pocketmine\network\bedrock\protocol\CraftingDataPacket as BedrockCraftingData;
use pocketmine\network\mcpe\MCPEPacketBatch;
use pocketmine\network\mcpe\NetworkCompression as Pw10NetworkCompression;
use pocketmine\network\mcpe\protocol\CraftingDataPacket as Pw10CraftingData;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\UUID;
use function array_chunk;
use function array_key_exists;
use function array_values;
use function count;
use function min;
use function usort;

class CraftingManager{

	/** @var Recipe[] */
	public array $recipes = [];

	/** @var Recipe[][] */
	protected array $recipeLookup = [];

	/** @var FurnaceRecipe[] */
	public array $furnaceRecipes = [];

    /** @var BrewingRecipe[] */
    public array $brewingRecipes = [];

	private static $RECIPE_COUNT = 0;

	/** @var string|null */
	private $pw10CraftingDataCache;
	/** @var string[] */
	private $bedrockCraftingDataCache = [];

	public function __construct(){

		// load recipes from src/pocketmine/resources/recipes.json
		$recipes = new Config(\pocketmine\RESOURCE_PATH . "recipes.json", Config::JSON, []);

		\GlobalLogger::get()->info("Loading recipes...");

        $this->registerBrewingStand();

		foreach($recipes->getAll() as $recipe){
			switch($recipe["type"]){
				case 0:
					// TODO: handle multiple result items
					$first = $recipe["output"][0];
					$result = new ShapelessRecipe(Item::jsonDeserialize($first));

					foreach($recipe["input"] as $ingredient){
						$result->addIngredient(Item::jsonDeserialize($ingredient));
					}
					$this->registerRecipe($result);
					break;
				case 1:
					// TODO: handle multiple result items
					$first = $recipe["output"][0];
					$result = new ShapedRecipe(Item::jsonDeserialize($first), $recipe["height"], $recipe["width"]);

					$shape = array_chunk($recipe["input"], $recipe["width"]);
					foreach($shape as $y => $row){
						foreach($row as $x => $ingredient){
							$result->addIngredient($x, $y, Item::jsonDeserialize($ingredient));
						}
					}
					$this->registerRecipe($result);
					break;
				case 2:
				case 3:
					$result = $recipe["output"];
					$resultItem = Item::jsonDeserialize($result);
					$this->registerRecipe(new FurnaceRecipe($resultItem, Item::get($recipe["inputId"], $recipe["inputDamage"] ?? -1, 1)));
					break;
				default:
					break;
			}
		}
	}

	protected function buildPw10Cache() : void{
		Timings::$craftingDataCacheRebuildTimer->startTiming();
		$pk = new Pw10CraftingData();
		$pk->cleanRecipes = true;

		foreach($this->recipes as $recipe){
			if($recipe instanceof ShapedRecipe){
				$pk->addShapedRecipe($recipe);
			}elseif($recipe instanceof ShapelessRecipe){
				$pk->addShapelessRecipe($recipe);
			}
		}

		foreach($this->furnaceRecipes as $recipe){
			$pk->addFurnaceRecipe($recipe);
		}

		$stream = new MCPEPacketBatch();
		$stream->putPacket($pk);

		$this->pw10CraftingDataCache = ProtocolInfo::MCPE_RAKNET_PACKET_ID . Pw10NetworkCompression::compress($stream->buffer);
		Timings::$craftingDataCacheRebuildTimer->stopTiming();
	}

	protected function buildBedrockCache(int $protocol) : void{
		Timings::$craftingDataCacheRebuildTimer->startTiming();
		$pk = new BedrockCraftingData();
		$pk->cleanRecipes = false;

		foreach($this->recipes as $recipe){
			if($recipe instanceof ShapedRecipe){
				$pk->addShapedRecipe($recipe);
			}elseif($recipe instanceof ShapelessRecipe){
				$pk->addShapelessRecipe($recipe);
			}elseif($recipe instanceof BrewingRecipe){
                $pk->addBrewingStand($recipe);
            }
		}

		foreach($this->furnaceRecipes as $recipe){
			$pk->addFurnaceRecipe($recipe);
		}

		$adapter = ProtocolAdapterFactory::get($protocol);
		if($adapter !== null){
			$pk = $adapter->processServerToClient($pk);
			if($pk === null){
				$this->bedrockCraftingDataCache[$protocol] = null;
				Timings::$craftingDataCacheRebuildTimer->stopTiming();
				return;
			}
		}

		$stream = new BedrockPacketBatch();
		$stream->putPacket($pk);

		$this->bedrockCraftingDataCache[$protocol] = ProtocolInfo::MCPE_RAKNET_PACKET_ID . BedrockNetworkCompression::compress($stream->buffer);
		Timings::$craftingDataCacheRebuildTimer->stopTiming();
	}


	/**
	 * @param int $protocol
	 *
	 * @return string|null
	 */
	public function getCraftingDataPacket(int $protocol) : ?string{
		if($protocol === ProtocolInfo::CURRENT_PROTOCOL){
			if($this->pw10CraftingDataCache === null){
				$this->buildPw10Cache();
			}
			return $this->pw10CraftingDataCache;
		}else{
			if(!array_key_exists($protocol, $this->bedrockCraftingDataCache)){
				$this->buildBedrockCache($protocol);
			}
			return $this->bedrockCraftingDataCache[$protocol];
		}
	}

	public function sort(Item $i1, Item $i2){
		if($i1->getId() > $i2->getId()){
			return 1;
		}elseif($i1->getId() < $i2->getId()){
			return -1;
		}elseif($i1->getDamage() > $i2->getDamage()){
			return 1;
		}elseif($i1->getDamage() < $i2->getDamage()){
			return -1;
		}elseif($i1->getCount() > $i2->getCount()){
			return 1;
		}elseif($i1->getCount() < $i2->getCount()){
			return -1;
		}else{
			return 0;
		}
	}

	/**
	 * @param UUID $id
	 * @return Recipe|null
	 */
	public function getRecipe(UUID $id){
		$index = $id->toBinary();
		return $this->recipes[$index] ?? null;
	}

	/**
	 * @return Recipe[]
	 */
	public function getRecipes() : array{
		return $this->recipes;
	}

	public function getRecipesByResult(Item $item){
   if (isset($this->recipeLookup[$item->getId() . ":" . $item->getDamage()])) {
    return array_values($this->recipeLookup[$item->getId() . ":" . $item->getDamage()]);
      } else {
    return [];
     }
   }

	/**
	 * @return FurnaceRecipe[]
	 */
	public function getFurnaceRecipes() : array{
		return $this->furnaceRecipes;
	}

	/**
	 * @param Item $input
	 *
	 * @return FurnaceRecipe|null
	 */
	public function matchFurnaceRecipe(Item $input){
		if(isset($this->furnaceRecipes[$input->getId() . ":" . $input->getDamage()])){
			return $this->furnaceRecipes[$input->getId() . ":" . $input->getDamage()];
		}elseif(isset($this->furnaceRecipes[$input->getId() . ":?"])){
			return $this->furnaceRecipes[$input->getId() . ":?"];
		}

		return null;
	}

	/**
	 * @param ShapedRecipe $recipe
	 */
	public function registerShapedRecipe(ShapedRecipe $recipe){
		$result = $recipe->getResult();
		$this->recipes[$recipe->getId()->toBinary()] = $recipe;
		$ingredients = $recipe->getIngredientMap();
		$hash = "";
		foreach($ingredients as $v){
			foreach($v as $item){
				if($item !== null){
					/** @var Item $item */
					$hash .= $item->getId() . ":" . ($item->hasAnyDamageValue() ? "?" : $item->getDamage()) . "x" . $item->getCount() . ",";
				}
			}

			$hash .= ";";
		}

		$this->recipeLookup[$result->getId() . ":" . $result->getDamage()][$hash] = $recipe;

		$this->pw10CraftingDataCache = null;
		$this->bedrockCraftingDataCache = [];
	}

	/**
	 * @param ShapelessRecipe $recipe
	 */
	public function registerShapelessRecipe(ShapelessRecipe $recipe){
		$result = $recipe->getResult();
		$this->recipes[$recipe->getId()->toBinary()] = $recipe;
		$hash = "";
		$ingredients = $recipe->getIngredientList();
		usort($ingredients, [$this, "sort"]);
		foreach($ingredients as $item){
			$hash .= $item->getId() . ":" . ($item->hasAnyDamageValue() ? "?" : $item->getDamage()) . "x" . $item->getCount() . ",";
		}
		$this->recipeLookup[$result->getId() . ":" . $result->getDamage()][$hash] = $recipe;

		$this->pw10CraftingDataCache = null;
		$this->bedrockCraftingDataCache = [];
	}

	/**
	 * @param FurnaceRecipe $recipe
	 */
	public function registerFurnaceRecipe(FurnaceRecipe $recipe){
		$input = $recipe->getInput();
		$this->furnaceRecipes[$input->getId() . ":" . ($input->hasAnyDamageValue() ? "?" : $input->getDamage())] = $recipe;

		$this->pw10CraftingDataCache = null;
		$this->bedrockCraftingDataCache = [];
	}

    /**
     * @param BrewingRecipe $recipe
     */
    public function registerBrewingRecipe(BrewingRecipe $recipe): void{
        $this->recipes[$recipe->getId()->toBinary()] = $recipe;
        $input = $recipe->getInput();
        $potion = $recipe->getPotion();
        $this->brewingRecipes[$input->getId() . ":" . ($input->getDamage() === null ? "0" : $input->getDamage()) . ":" . $potion->getId() . ":" . ($potion->getDamage() === null ? "0" : $potion->getDamage())] = $recipe;

        $this->pw10CraftingDataCache = null;
        $this->bedrockCraftingDataCache = [];
    }

	/**
	 * @param ShapelessRecipe $recipe
	 * @return bool
	 */
	public function matchRecipe(ShapelessRecipe $recipe) : bool{
		if(!isset($this->recipeLookup[$idx = $recipe->getResult()->getId() . ":" . $recipe->getResult()->getDamage()])){
			return false;
		}

		$hash = "";
		$ingredients = $recipe->getIngredientList();
		usort($ingredients, [$this, "sort"]);
		foreach($ingredients as $item){
			$hash .= $item->getId() . ":" . ($item->hasAnyDamageValue() ? "?" : $item->getDamage()) . "x" . $item->getCount() . ",";
		}

		if(isset($this->recipeLookup[$idx][$hash])){
			return true;
		}

		$hasRecipe = null;
		foreach($this->recipeLookup[$idx] as $possibleRecipe){
			if($possibleRecipe instanceof ShapelessRecipe){
				if($possibleRecipe->getIngredientCount() !== count($ingredients)){
					continue;
				}
				$checkInput = $possibleRecipe->getIngredientList();
				foreach($ingredients as $item){
					$amount = $item->getCount();
					foreach($checkInput as $k => $checkItem){
						if($checkItem->equals($item, !$checkItem->hasAnyDamageValue(), $checkItem->hasCompoundTag())){
							$remove = min($checkItem->getCount(), $amount);
							$checkItem->setCount($checkItem->getCount() - $remove);
							if($checkItem->getCount() === 0){
								unset($checkInput[$k]);
							}
							$amount -= $remove;
							if($amount === 0){
								break;
							}
						}
					}
				}

				if(count($checkInput) === 0){
					$hasRecipe = $possibleRecipe;
					break;
				}
			}
			if($hasRecipe instanceof Recipe){
				break;
			}
		}

		return $hasRecipe !== null;

	}

    /**
     * @param Item $input
     * @param Item $potion
     *
     * @return BrewingRecipe
     */
    public function matchBrewingRecipe(Item $input, Item $potion){
        $subscript = $input->getId() . ":" . ($input->getDamage() === null ? "0" : $input->getDamage()) . ":" . $potion->getId() . ":" . ($potion->getDamage() === null ? "0" : $potion->getDamage());
        if(isset($this->brewingRecipes[$subscript])){
            return $this->brewingRecipes[$subscript];
        }
        return null;
    }

	/**
	 * @param Recipe $recipe
	 */
	public function registerRecipe(Recipe $recipe){
		$recipe->setId(UUID::fromData((string) ++self::$RECIPE_COUNT, (string) $recipe->getResult()->getId(), (string) $recipe->getResult()->getDamage(), (string) $recipe->getResult()->getCount(), $recipe->getResult()->getCompoundTag()));

		if($recipe instanceof ShapedRecipe){
			$this->registerShapedRecipe($recipe);
		}elseif($recipe instanceof ShapelessRecipe){
			$this->registerShapelessRecipe($recipe);
		}elseif($recipe instanceof FurnaceRecipe){
            $this->registerFurnaceRecipe($recipe);
        }elseif($recipe instanceof BrewingRecipe){
            $this->registerBrewingRecipe($recipe);
        }
	}

    protected function registerBrewingStand(): void{

        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::AWKWARD, 1), Item::get(Item::NETHER_WART, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::THICK, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE_EXTENDED, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::WEAKNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));

        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::GHAST_TEAR, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::GLISTERING_MELON, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::BLAZE_POWDER, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::MAGMA_CREAM, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::SUGAR, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::RABBIT_FOOT, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1)));

        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::WEAKNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::MUNDANE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::WEAKNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::THICK, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::WEAKNESS_T, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::MUNDANE_EXTENDED, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::WEAKNESS_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::WEAKNESS, 1)));

        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::REGENERATION, 1), Item::get(Item::GHAST_TEAR, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::REGENERATION_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::REGENERATION, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::REGENERATION_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::REGENERATION, 1)));

        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::STRENGTH, 1), Item::get(Item::BLAZE_POWDER, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::STRENGTH_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::STRENGTH, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::STRENGTH_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::STRENGTH, 1)));

        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::POISON, 1), Item::get(Item::SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::POISON_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::POISON, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::POISON_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::POISON, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::HEALING, 1), Item::get(Item::GLISTERING_MELON, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::HEALING_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::HEALING, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::WATER_BREATHING, 1), Item::get(Item::PUFFERFISH, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::WATER_BREATHING_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::WATER_BREATHING, 1)));

        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::WATER_BREATHING, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::HEALING, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::POISON, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::HARMING_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::HARMING, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::HARMING_TWO, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::HEALING_TWO, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::HARMING_TWO, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::POISON_T, 1)));

        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SPEED, 1), Item::get(Item::SUGAR, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SPEED_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::SPEED, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SPEED_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::SPEED, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::FIRE_RESISTANCE, 1), Item::get(Item::MAGMA_CREAM, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::FIRE_RESISTANCE_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::FIRE_RESISTANCE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LEAPING, 1), Item::get(Item::RABBIT_FOOT, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LEAPING_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::LEAPING, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::LEAPING_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::LEAPING, 1)));

        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::FIRE_RESISTANCE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::SPEED, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::LEAPING, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SLOWNESS_T, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::FIRE_RESISTANCE_T, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SLOWNESS_T, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::LEAPING_T, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SLOWNESS_T, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::SPEED_T, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::SLOWNESS_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::SLOWNESS, 1)));

        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::NIGHT_VISION, 1), Item::get(Item::GOLDEN_CARROT, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::NIGHT_VISION_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::NIGHT_VISION, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::INVISIBILITY, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::NIGHT_VISION, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::INVISIBILITY_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::INVISIBILITY, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::POTION, Potion::INVISIBILITY_T, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::NIGHT_VISION_T, 1)));

        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1), Item::get(Item::NETHER_WART, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER_BOTTLE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::THICK, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER_BOTTLE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE_EXTENDED, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER_BOTTLE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::WEAKNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER_BOTTLE, 1)));

        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), Item::get(Item::GHAST_TEAR, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER_BOTTLE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), Item::get(Item::GLISTERING_MELON, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER_BOTTLE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), Item::get(Item::BLAZE_POWDER, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER_BOTTLE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), Item::get(Item::MAGMA_CREAM, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER_BOTTLE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), Item::get(Item::SUGAR, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER_BOTTLE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), Item::get(Item::SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER_BOTTLE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1), Item::get(Item::RABBIT_FOOT, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER_BOTTLE, 1)));

        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::WEAKNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::MUNDANE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::WEAKNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::THICK, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::WEAKNESS_T, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::MUNDANE_EXTENDED, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::WEAKNESS_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WEAKNESS, 1)));

        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::REGENERATION, 1), Item::get(Item::GHAST_TEAR, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::REGENERATION_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::REGENERATION, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::REGENERATION_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::REGENERATION, 1)));

        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::STRENGTH, 1), Item::get(Item::BLAZE_POWDER, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::STRENGTH_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::STRENGTH, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::STRENGTH_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::STRENGTH, 1)));

        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::POISON, 1), Item::get(Item::SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::POISON_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::POISON, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::POISON_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::POISON, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::HEALING, 1), Item::get(Item::GLISTERING_MELON, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::HEALING_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::HEALING, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::WATER_BREATHING, 1), Item::get(Item::PUFFERFISH, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::WATER_BREATHING_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER_BREATHING, 1)));

        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::WATER_BREATHING, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::HEALING, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::POISON, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::HARMING_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::HARMING, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::HARMING_TWO, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::HEALING_TWO, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::HARMING_TWO, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::POISON_T, 1)));

        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::SPEED, 1), Item::get(Item::SUGAR, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::SPEED_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::SPEED, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::SPEED_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::SPEED, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::FIRE_RESISTANCE, 1), Item::get(Item::MAGMA_CREAM, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::FIRE_RESISTANCE_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::FIRE_RESISTANCE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LEAPING, 1), Item::get(Item::RABBIT_FOOT, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LEAPING_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::LEAPING, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::LEAPING_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::LEAPING, 1)));

        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::FIRE_RESISTANCE, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::SPEED, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::LEAPING, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::SLOWNESS_T, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::FIRE_RESISTANCE_T, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::SLOWNESS_T, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::LEAPING_T, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::SLOWNESS_T, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::SPEED_T, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::SLOWNESS_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::SLOWNESS, 1)));

        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::NIGHT_VISION, 1), Item::get(Item::GOLDEN_CARROT, 0, 1), Item::get(Item::SPLASH_POTION, Potion::AWKWARD, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::NIGHT_VISION_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::NIGHT_VISION, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::INVISIBILITY, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::NIGHT_VISION, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::INVISIBILITY_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::SPLASH_POTION, Potion::INVISIBILITY, 1)));
        $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, Potion::INVISIBILITY_T, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::SPLASH_POTION, Potion::NIGHT_VISION_T, 1)));

        foreach(Potion::POTIONS as $potion => $effect){
            $this->registerRecipe(new BrewingRecipe(Item::get(Item::SPLASH_POTION, $potion, 1), Item::get(Item::GUNPOWDER, 0, 1), Item::get(Item::POTION, $potion, 1)));
        }
    }

}


