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

use pocketmine\block\Block;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\inventory\BeaconInventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;

class Beacon extends Spawnable implements Nameable, InventoryHolder{
    use NameableTrait {
        addAdditionalSpawnData as addNameSpawnData;
    }
    use ContainerTrait;

    public const TAG_PRIMARY = "primary";
    public const TAG_SECONDARY = "secondary";

    private ?BeaconInventory $inventory = null;
    private int $primary = 0;
    private int $secondary = 0;
    protected int $currentTick = 0;
    /** @var array */
    protected array $minerals = [
        Block::IRON_BLOCK,
        Block::GOLD_BLOCK,
        Block::EMERALD_BLOCK,
        Block::DIAMOND_BLOCK
    ];
    protected AxisAlignedBB $rangeBox;

	public const POWER_LEVEL_MAX = 4;

	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);

		$this->scheduleUpdate();
        $this->rangeBox = new AxisAlignedBB($this->x, $this->y, $this->z, $this->x, $this->y, $this->z);
	}

    protected function readSaveData(CompoundTag $nbt) : void
    {
        $this->primary = $nbt->getInt(self::TAG_PRIMARY, 0);
        $this->secondary = $nbt->getInt(self::TAG_SECONDARY, 0);

        $this->inventory = new BeaconInventory($this);

        $this->loadName($nbt);
        $this->loadItems($nbt);
    }

    protected function writeSaveData(CompoundTag $nbt) : void{
        $nbt->setInt(self::TAG_PRIMARY, $this->primary);
        $nbt->setInt(self::TAG_SECONDARY, $this->secondary);

        $this->saveName($nbt);
        $this->saveItems($nbt);
    }

    public function close() : void{
        if(!$this->closed){
            foreach($this->getInventory()->getViewers() as $player){
                $player->removeWindow($this->getInventory());
            }
            $this->inventory = null;

            parent::close();
        }
    }

    protected function addAdditionalSpawnData(CompoundTag $nbt, bool $isBedrock) : void{
        $nbt->setInt(self::TAG_PRIMARY, $this->primary);
        $nbt->setInt(self::TAG_SECONDARY, $this->secondary);

        $this->addNameSpawnData($nbt, $isBedrock);
    }

    public function getDefaultName(): string
    {
        return "Beacon";
    }

	/**
	 * @return BeaconInventory
	 */
	public function getInventory(): ?BeaconInventory{
		return $this->inventory;
	}

    public function getRealInventory() : ?BeaconInventory{
        return $this->getInventory();
    }

	/**
	 * @param CompoundTag $nbt
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function updateCompoundTag(CompoundTag $nbt, Player $player) : bool{
		if($nbt->getString("id") !== Tile::BEACON){
			return false;
		}

        $this->primary = $nbt->getInt(self::TAG_PRIMARY);
        $this->secondary = $nbt->getInt(self::TAG_SECONDARY);

		return true;
	}

	/**
	 * @return bool
	 */
	public function onUpdate(): bool {
        if($this->closed === true){
            return false;
        }

        if($this->currentTick++ % 80 === 0){
            if(($effectPrim = Effect::getEffect($this->primary)) !== null){
                if(($pyramidLevels = $this->getPyramidLevels()) > 0){
                    $duration = 180 + $pyramidLevels * 40;
                    $range = (10 + $pyramidLevels * 10);
                    $effectPrim = new EffectInstance($effectPrim, $duration, $pyramidLevels == 4 && $this->primary == $this->secondary ? 1 : 0);
                    $players = array_filter($this->level->getCollidingEntities($this->rangeBox->expandedCopy($range, $range, $range)), function(Entity $player) : bool{
                        return $player instanceof Player and $player->spawned;
                    });

                    foreach($players as $player){
                        /** @var Player $player */
                        $player->addEffect($effectPrim);

                        if($pyramidLevels == 4 && $this->primary != $this->secondary){
                            $player->addEffect(new EffectInstance(Effect::getEffect(Effect::REGENERATION), $duration));
                        }
                    }
                }
            }
        }

        return true;
	}

    protected function getPyramidLevels() : int{
        $allMineral = true;
        for($i = 1; $i < 5; $i++){
            for($x = -$i; $x < $i + 1; $x++){
                for($z = -$i; $z < $i + 1; $z++){
                    $allMineral = in_array($this->level->getBlockAt($this->x + $x, $this->y - $i, $this->z + $z)->getId(), $this->minerals);
                    if(!$allMineral) return $i - 1;
                }
            }
        }

        return 4;
    }
}


