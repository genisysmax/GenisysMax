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

namespace pocketmine\tile;

use pocketmine\inventory\BrewingInventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ContainerSetDataPacket;
use pocketmine\Server;

class BrewingStand extends Spawnable implements InventoryHolder, Container, Nameable {
    use NameableTrait {
        addAdditionalSpawnData as addNameSpawnData;
    }
    use ContainerTrait;

    public const TAG_COOK_TIME = "CookTime";
    public const TAG_FUEL_AMOUNT = "FuelAmount";
    public const TAG_FUEL_TOTAL = "FuelTotal";

	public const MAX_BREW_TIME = 400;

	/** @var NULL|BrewingInventory */
	protected ?BrewingInventory $inventory = null;

    public int $cookTime = 0;

    protected function readSaveData(CompoundTag $nbt) : void{
        $this->cookTime = $nbt->getShort(self::TAG_COOK_TIME, 0);
        $this->loadName($nbt);

        $this->inventory = new BrewingInventory($this);
        $this->loadItems($nbt);
    }

    protected function writeSaveData(CompoundTag $nbt) : void{
        $nbt->setShort(self::TAG_COOK_TIME, $this->cookTime);

        $this->saveName($nbt);
        $this->saveItems($nbt);
    }

	/**
	 * @return string
	 */
	public function getDefaultName() : string
    {
        return "Brewing Stand";
    }

	/**
	 * @return BrewingInventory
	 */
	public function getInventory(){
		return $this->inventory;
	}

    /**
     * @return BrewingInventory
     */
    public function getRealInventory(){
        return $this->inventory;
    }

	public function updateSurface(): void{
		$this->saveNBT();
		$this->onChanged();
	}

	/**
	 * @return bool
	 */
	public function onUpdate(): bool
    {
		if($this->closed === true){
			return false;
		}

		$this->timings->startTiming();

		$ret = false;

		$ingredient = $this->inventory->getIngredient();
		$canBrew = false;

		for($i = 1; $i <= 3; $i++){
			if($this->inventory->getItem($i)->getId() === Item::POTION or
				$this->inventory->getItem($i)->getId() === Item::SPLASH_POTION
			){
				$canBrew = true;
			}
		}

		if($ingredient->getId() !== Item::AIR and $ingredient->getCount() > 0){
			if($canBrew){
				for($i = 1; $i <= 3; $i++){
					$potion = $this->inventory->getItem($i);
					$recipe = Server::getInstance()->getCraftingManager()->matchBrewingRecipe($ingredient, $potion);
					if($recipe !== null){
						$canBrew = true;
						break;
					}
					$canBrew = false;
				}
			}
		}else{
			$canBrew = false;
		}

		if($canBrew){
            $this->cookTime -= 1;
			foreach($this->getInventory()->getViewers() as $player){
				$windowId = $player->getWindowId($this->getInventory());
				if($windowId > 0){
					$pk = new ContainerSetDataPacket();
					$pk->windowId = $windowId;
					$pk->property = 0; //Brew
					$pk->value = $this->cookTime;
					$player->sendDataPacket($pk);
				}
			}

			if($this->cookTime <= 0){
                $this->cookTime = self::MAX_BREW_TIME;
				for($i = 1; $i <= 3; $i++){
					$potion = $this->inventory->getItem($i);
					$recipe = Server::getInstance()->getCraftingManager()->matchBrewingRecipe($ingredient, $potion);
					if($recipe != null and $potion->getId() !== Item::AIR){
						$this->inventory->setItem($i, $recipe->getResult());
					}
				}

				$ingredient->pop();
				if($ingredient->getCount() <= 0) $ingredient = Item::get(Item::AIR);
				$this->inventory->setIngredient($ingredient);
			}

			$ret = true;
		}else{
            $this->cookTime = self::MAX_BREW_TIME;
			foreach($this->getInventory()->getViewers() as $player){
				$windowId = $player->getWindowId($this->getInventory());
				if($windowId > 0){
					$pk = new ContainerSetDataPacket();
					$pk->windowId = $windowId;
					$pk->property = ContainerSetDataPacket::PROPERTY_BREWING_STAND_BREW_TIME; //Brew
					$pk->value = 0;
					$player->sendDataPacket($pk);
				}
			}
		}

		$this->timings->stopTiming();

		return $ret;
	}

    protected function addAdditionalSpawnData(CompoundTag $nbt, bool $isBedrock): void
    {
        if($isBedrock) {
            $nbt->setShort(self::TAG_FUEL_AMOUNT, 20);
            $nbt->setShort(self::TAG_FUEL_TOTAL, 20);
        }

        $nbt->setShort(self::TAG_COOK_TIME, $this->cookTime);
        $this->addNameSpawnData($nbt, $isBedrock);
    }
}


