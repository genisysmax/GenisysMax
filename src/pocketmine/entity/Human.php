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

namespace pocketmine\entity;

use InvalidArgumentException;
use pocketmine\BedrockPlayer;
use pocketmine\entity\projectile\ProjectileSource;
use pocketmine\entity\utils\ExperienceUtils;
use pocketmine\event\entity\EntityConsumeTotemEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerExperienceChangeEvent;
use pocketmine\inventory\EnderChestInventory;
use pocketmine\inventory\FloatingInventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\PlayerOffHandInventory;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\Item as ItemItem;
use pocketmine\item\Totem;
use pocketmine\level\Level;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\bedrock\protocol\PlayerListPacket as BedrockPlayerListPacket;
use pocketmine\network\bedrock\protocol\PlayerSkinPacket;
use pocketmine\network\bedrock\protocol\types\PlayerListEntry as BedrockPlayerListEntry;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket as McpePlayerListPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry as McpePlayerListEntry;
use pocketmine\network\mcpe\protocol\types\Skin as McpeSkin;
use pocketmine\Player;
use pocketmine\utils\UUID;
use function array_values;
use function max;
use function min;
use function mt_rand;

class Human extends Creature implements ProjectileSource, InventoryHolder{

	public const int DATA_PLAYER_FLAG_SLEEP = 1;
	public const int DATA_PLAYER_FLAG_DEAD = 2; //TODO: CHECK

	public const int DATA_PLAYER_FLAGS = 27;
	public const int DATA_PLAYER_INDEX = 28;
	public const int DATA_PLAYER_BED_POSITION = 29;

	protected ?PlayerInventory $inventory = null;
    protected ?PlayerOffHandInventory $offHandInventory = null;
	protected ?EnderChestInventory $enderChestInventory = null;
	protected ?FloatingInventory $floatingInventory = null;

	/** @var UUID */
	protected $uuid;
	protected $rawUUID;

	public float $width = 0.6;
	public float $height = 1.8;
	public ?float $eyeHeight = 1.62;

	protected Skin $skin;

	protected int $foodTickTimer = 0;

    protected int $totalXp = 0;
    protected int $xpSeed = 0;
    protected int $xpCooldown = 0;

	protected float $baseOffset = 1.62;

    public function __construct(Level $level, CompoundTag $nbt, ?Skin $skin = null)
    {
        if ($skin !== null) {
            $mcpeSkin = $skin->getMcpeSkin();
            $nbt->setTag("Skin", CompoundTag::create()
                ->setString("Data", $mcpeSkin->getSkinData())
                ->setString("Name", $mcpeSkin->getSkinId()));
        }
        parent::__construct($level, $nbt);
    }

    public function getUniqueId() : ?UUID{
        return $this->uuid;
    }

	public function getRawUniqueId() : string{
		return $this->rawUUID;
	}

	public function getSkin() : Skin{
		return $this->skin;
	}

	public function setSkin(Skin $skin) : void{
		if(!$skin->isValid()){
			throw new \InvalidArgumentException("Invalid skin given");
		}
		$this->skin = $skin;
	}

    public function sendSkin(?array $targets = null) : void{
        $target = $target ?? $this->hasSpawned;
        if($target instanceof Player){
            $target = [$target];
        }

        $pk = new PlayerSkinPacket();
        $pk->uuid = $this->getUniqueId();
        $pk->skin = $this->skin->getBedrockSkin();

        foreach($target as $player){
            if($player instanceof BedrockPlayer){
                $player->sendDataPacket($pk);
            }elseif($player !== $this){
                // PW10 players need a respawn to update skin
                $this->despawnFrom($player, false);
                $this->spawnTo($player);
            }
        }
    }

    public function jump() : void{
        parent::jump();
        if($this->isSprinting()){
            $this->exhaust(0.3, PlayerExhaustEvent::CAUSE_SPRINT_JUMPING);
        }else{
            $this->exhaust(0.2, PlayerExhaustEvent::CAUSE_JUMPING);
        }
    }

    public function getFood() : float{
        return $this->attributeMap->getAttribute(Attribute::HUNGER)->getValue();
    }

    /**
     * WARNING: This method does not check if full and may throw an exception if out of bounds.
     * Use {@link Human::addFood()} for this purpose
     *
     * @param float $new
     *
     * @throws InvalidArgumentException
     */
    public function setFood(float $new) : void{
        $attr = $this->attributeMap->getAttribute(Attribute::HUNGER);
        $old = $attr->getValue();
        $attr->setValue($new);

        // ranges: 18-20 (regen), 7-17 (none), 1-6 (no sprint), 0 (health depletion)
        foreach([17, 6, 0] as $bound){
            if(($old > $bound) !== ($new > $bound)){
                $this->foodTickTimer = 0;
                break;
            }
        }
    }

    public function getMaxFood() : float{
        return $this->attributeMap->getAttribute(Attribute::HUNGER)->getMaxValue();
    }

    public function addFood(float $amount) : void{
        $attr = $this->attributeMap->getAttribute(Attribute::HUNGER);
        $amount += $attr->getValue();
        $amount = max(min($amount, $attr->getMaxValue()), $attr->getMinValue());
        $this->setFood($amount);
    }

    /**
     * Returns whether this Human may consume objects requiring hunger.
     *
     * @return bool
     */
    public function isHungry() : bool{
        return $this->getFood() < $this->getMaxFood();
    }

    public function getSaturation() : float{
        return $this->attributeMap->getAttribute(Attribute::SATURATION)->getValue();
    }

    /**
     * WARNING: This method does not check if saturated and may throw an exception if out of bounds.
     * Use {@link Human::addSaturation()} for this purpose
     *
     * @param float $saturation
     *
     * @throws InvalidArgumentException
     */
    public function setSaturation(float $saturation) : void{
        $this->attributeMap->getAttribute(Attribute::SATURATION)->setValue($saturation);
    }

    public function addSaturation(float $amount) : void{
        $attr = $this->attributeMap->getAttribute(Attribute::SATURATION);
        $attr->setValue($attr->getValue() + $amount, true);
    }

    public function getExhaustion() : float{
        return $this->attributeMap->getAttribute(Attribute::EXHAUSTION)->getValue();
    }

    /**
     * WARNING: This method does not check if exhausted and does not consume saturation/food.
     * Use {@link Human::exhaust()} for this purpose.
     *
     * @param float $exhaustion
     */
    public function setExhaustion(float $exhaustion) : void{
        $this->attributeMap->getAttribute(Attribute::EXHAUSTION)->setValue($exhaustion);
    }

    /**
     * Increases a human's exhaustion level.
     *
     * @param float $amount
     * @param int   $cause
     *
     * @return float the amount of exhaustion level increased
     */
    public function exhaust(float $amount, int $cause = PlayerExhaustEvent::CAUSE_CUSTOM) : float{
        $ev = new PlayerExhaustEvent($this, $amount, $cause);
        $ev->call();
        if($ev->isCancelled()){
            return 0.0;
        }

        $exhaustion = $this->getExhaustion();
        $exhaustion += $ev->getAmount();

        while($exhaustion >= 4.0){
            $exhaustion -= 4.0;

            $saturation = $this->getSaturation();
            if($saturation > 0){
                $saturation = max(0, $saturation - 1.0);
                $this->setSaturation($saturation);
            }else{
                $food = $this->getFood();
                if($food > 0){
                    $food--;
                    $this->setFood(max($food, 0));
                }
            }
        }
        $this->setExhaustion($exhaustion);

        return $ev->getAmount();
    }

    /**
     * Returns the player's experience level.
     * @return int
     */
    public function getXpLevel() : int{
        return (int) $this->attributeMap->getAttribute(Attribute::EXPERIENCE_LEVEL)->getValue();
    }

    /**
     * Sets the player's experience level. This does not affect their total XP or their XP progress.
     *
     * @param int $level
     *
     * @return bool
     */
    public function setXpLevel(int $level) : bool{
        return $this->setXpAndProgress($level, null);
    }

    /**
     * Adds a number of XP levels to the player.
     *
     * @param int  $amount
     * @param bool $playSound
     *
     * @return bool
     */
    public function addXpLevels(int $amount, bool $playSound = true) : bool{
        $oldLevel = $this->getXpLevel();
        if($this->setXpLevel($oldLevel + $amount)){
            if($playSound){
                $newLevel = $this->getXpLevel();
                if((int) ($newLevel / 5) > (int) ($oldLevel / 5)){
                    $this->playLevelUpSound($newLevel);
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Subtracts a number of XP levels from the player.
     *
     * @param int $amount
     *
     * @return bool
     */
    public function subtractXpLevels(int $amount) : bool{
        return $this->addXpLevels(-$amount);
    }

    /**
     * Returns a value between 0.0 and 1.0 to indicate how far through the current level the player is.
     * @return float
     */
    public function getXpProgress() : float{
        return $this->attributeMap->getAttribute(Attribute::EXPERIENCE)->getValue();
    }

    /**
     * Sets the player's progress through the current level to a value between 0.0 and 1.0.
     *
     * @param float $progress
     *
     * @return bool
     */
    public function setXpProgress(float $progress) : bool{
        return $this->setXpAndProgress(null, $progress);
    }

    /**
     * Returns the number of XP points the player has progressed into their current level.
     * @return int
     */
    public function getRemainderXp() : int{
        return (int) (ExperienceUtils::getXpToCompleteLevel($this->getXpLevel()) * $this->getXpProgress());
    }

    /**
     * Returns the amount of XP points the player currently has, calculated from their current level and progress
     * through their current level. This will be reduced by enchanting deducting levels and is used to calculate the
     * amount of XP the player drops on death.
     *
     * @return int
     */
    public function getCurrentTotalXp() : int{
        return ExperienceUtils::getXpToReachLevel($this->getXpLevel()) + $this->getRemainderXp();
    }

    /**
     * Sets the current total of XP the player has, recalculating their XP level and progress.
     * Note that this DOES NOT update the player's lifetime total XP.
     *
     * @param int $amount
     *
     * @return bool
     */
    public function setCurrentTotalXp(int $amount) : bool{
        $newLevel = ExperienceUtils::getLevelFromXp($amount);

        return $this->setXpAndProgress((int) $newLevel, $newLevel - ((int) $newLevel));
    }

    /**
     * Adds an amount of XP to the player, recalculating their XP level and progress. XP amount will be added to the
     * player's lifetime XP.
     *
     * @param int  $amount
     * @param bool $playSound Whether to play level-up and XP gained sounds.
     *
     * @return bool
     */
    public function addXp(int $amount, bool $playSound = true) : bool{
        $this->totalXp += $amount;

        $oldLevel = $this->getXpLevel();
        $oldTotal = $this->getCurrentTotalXp();

        if($this->setCurrentTotalXp($oldTotal + $amount)){
            if($playSound){
                $newLevel = $this->getXpLevel();
                if((int) ($newLevel / 5) > (int) ($oldLevel / 5)){
                    $this->playLevelUpSound($newLevel);
                }elseif($this->getCurrentTotalXp() > $oldTotal){
                    $this->level->broadcastLevelEvent($this, LevelEventPacket::EVENT_SOUND_ORB, mt_rand());
                }
            }

            return true;
        }

        return false;
    }

    private function playLevelUpSound(int $newLevel) : void{
        $volume = 0x10000000 * (min(30, $newLevel) / 5); //No idea why such odd numbers, but this works...
        $this->level->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_LEVELUP, (int) $volume);
    }

    /**
     * Takes an amount of XP from the player, recalculating their XP level and progress.
     *
     * @param int $amount
     *
     * @return bool
     */
    public function subtractXp(int $amount) : bool{
        return $this->addXp(-$amount);
    }

    protected function setXpAndProgress(?int $level, ?float $progress) : bool{
        if(!$this->justCreated){
            $ev = new PlayerExperienceChangeEvent($this, $this->getXpLevel(), $this->getXpProgress(), $level, $progress);
            $ev->call();

            if($ev->isCancelled()){
                return false;
            }

            $level = $ev->getNewLevel();
            $progress = $ev->getNewProgress();
        }

        if($level !== null){
            $this->getAttributeMap()->getAttribute(Attribute::EXPERIENCE_LEVEL)->setValue($level);
        }

        if($progress !== null){
            $this->getAttributeMap()->getAttribute(Attribute::EXPERIENCE)->setValue($progress);
        }

        return true;
    }

    /**
     * Returns the total XP the player has collected in their lifetime. Resets when the player dies.
     * XP levels being removed in enchanting do not reduce this number.
     *
     * @return int
     */
    public function getLifetimeTotalXp() : int{
        return $this->totalXp;
    }

    /**
     * Sets the lifetime total XP of the player. This does not recalculate their level or progress. Used for player
     * score when they die. (TODO: add this when MCPE supports it)
     *
     * @param int $amount
     */
    public function setLifetimeTotalXp(int $amount) : void{
        if($amount < 0){
            throw new InvalidArgumentException("XP must be greater than 0");
        }

        $this->totalXp = $amount;
    }

    /**
     * Returns whether the human can pickup XP orbs (checks cooldown time)
     * @return bool
     */
    public function canPickupXp() : bool{
        return $this->xpCooldown === 0;
    }

    public function onPickupXp(int $xpValue) : void{
        static $mainHandIndex = -1;
        static $offHandIndex = -2;

        //TODO: replace this with a more generic equipment getting/setting interface
        /** @var Durable[] $equipment */
        $equipment = [];

        if(($item = $this->inventory->getItemInHand()) instanceof Durable and $item->hasEnchantment(Enchantment::MENDING)){
            $equipment[$mainHandIndex] = $item;
        }
        if(($item = $this->offHandInventory->getItem(0)) instanceof Durable and $item->hasEnchantment(Enchantment::MENDING)){
            $equipment[$offHandIndex] = $item;
        }
        foreach($this->armorInventory->getContents() as $k => $item){
            if($item instanceof Durable and $item->hasEnchantment(Enchantment::MENDING)){
                $equipment[$k] = $item;
            }
        }

        if(!empty($equipment)){
            $repairItem = $equipment[$k = array_rand($equipment)];
            if($repairItem->getDamage() > 0){
                $repairAmount = min($repairItem->getDamage(), $xpValue * 2);
                $repairItem->setDamage($repairItem->getDamage() - $repairAmount);
                $xpValue -= (int) ceil($repairAmount / 2);

                if($k === $mainHandIndex){
                    $this->inventory->setItemInHand($repairItem);
                }elseif($k === $offHandIndex){
                    $this->offHandInventory->setItem(0, $repairItem);
                }else{
                    $this->armorInventory->setItem($k, $repairItem);
                }
            }
        }

        $this->addXp($xpValue); //this will still get fired even if the value is 0 due to mending, to play sounds
        $this->resetXpCooldown();
    }

    /**
     * Sets the duration in ticks until the human can pick up another XP orb.
     *
     * @param int $value
     */
    public function resetXpCooldown(int $value = 2) : void{
        $this->xpCooldown = $value;
    }

    public function getXpDropAmount() : int{
        //this causes some XP to be lost on death when above level 1 (by design), dropping at most enough points for
        //about 7.5 levels of XP.
        return (int) min(100, 7 * $this->getXpLevel());
    }

	public function getInventory(){
		return $this->inventory;
	}

    public function getOffHandInventory() : PlayerOffHandInventory{
        return $this->offHandInventory;
    }

	public function getEnderChestInventory() : EnderChestInventory{
		return $this->enderChestInventory;
	}

	public function getFloatingInventory(){
		return $this->floatingInventory;
	}

    /**
     * For Human entities which are not players, sets their properties such as nametag, skin and UUID from NBT.
     */
    protected function initHumanData() : void{
        if($this->namedtag->hasTag("NameTag", StringTag::class)){
            $this->setNameTag($this->namedtag->getString("NameTag"));
        }

        if($this->namedtag->hasTag("Skin", CompoundTag::class)){
            $skinTag = $this->namedtag->getCompoundTag("Skin");
            $this->setSkin(Skin::fromMcpeSkin(new McpeSkin($skinTag->getString("Name"), $skinTag->getTag("Data")->getValue())));
        }else{
            throw new \InvalidStateException((new \ReflectionClass($this))->getShortName() . " must have a valid skin set");
        }

        $this->uuid = UUID::fromData((string) $this->getId(), $this->skin->getMcpeSkin()->getSkinData(), $this->getNameTag());
    }

	protected function initEntity() : void{
        parent::initEntity();

        $this->setPlayerFlag(self::DATA_PLAYER_FLAG_SLEEP, false);
        $this->propertyManager->setBlockPos(self::DATA_PLAYER_BED_POSITION, null);

		$inventoryContents = $this->namedtag->getListTag("Inventory");
		$this->inventory = new PlayerInventory($this, $inventoryContents);
        $offhandContents = $this->namedtag->getCompoundTag("OffHandItem");
        $this->offHandInventory = new PlayerOffHandInventory($this, $offhandContents);
		$this->enderChestInventory = new EnderChestInventory($this);

		//Virtual inventory for desktop GUI crafting and anti-cheat transaction processing
		$this->floatingInventory = new FloatingInventory($this);
        $this->initHumanData();

		if($this->namedtag->hasTag("EnderChestInventory", ListTag::class)){
			$itemList = $this->namedtag->getListTag("EnderChestInventory");
			if($itemList->getTagType() === NBT::TAG_Compound){
				foreach($itemList as $item){
					/** @var CompoundTag $item */
					$this->enderChestInventory->setItem($item->getByte("Slot"), ItemItem::nbtDeserialize($item), false);
				}
			}
		}

		if($this->namedtag->hasTag("SelectedInventorySlot", IntTag::class)){
			$this->inventory->setHeldItemSlot($this->namedtag->getInt("SelectedInventorySlot"), false);
		}else{
			$this->inventory->setHeldItemSlot(0, false);
		}

		if(!$this->namedtag->hasTag("foodLevel", IntTag::class)){
			$this->namedtag->setInt("foodLevel", (int) $this->getFood());
		}else{
			$this->setFood((float) $this->namedtag->getInt("foodLevel"));
		}

		if(!$this->namedtag->hasTag("foodExhaustionLevel", FloatTag::class)){
			$this->namedtag->setFloat("foodExhaustionLevel", $this->getExhaustion());
		}else{
			$this->setExhaustion($this->namedtag->getFloat("foodExhaustionLevel"));
		}

		if(!$this->namedtag->hasTag("foodSaturationLevel", FloatTag::class)){
			$this->namedtag->setFloat("foodSaturationLevel", $this->getSaturation());
		}else{
			$this->setSaturation($this->namedtag->getFloat("foodSaturationLevel"));
		}

		if(!$this->namedtag->hasTag("foodTickTimer", IntTag::class)){
			$this->namedtag->setInt("foodTickTimer", $this->foodTickTimer);
		}else{
			$this->foodTickTimer = $this->namedtag->getInt("foodTickTimer");
		}

		if(!$this->namedtag->hasTag("XpLevel", IntTag::class)){
			$this->namedtag->setInt("XpLevel", $this->getXpLevel());
		}else{
			$this->setXpLevel($this->namedtag->getInt("XpLevel"));
		}

		if(!$this->namedtag->hasTag("XpP", FloatTag::class)){
			$this->namedtag->setFloat("XpP", $this->getXpProgress());
		}else{
            $this->setXpProgress($this->namedtag->getFloat("XpP"));
        }

		if(!$this->namedtag->hasTag("XpTotal", IntTag::class)){
			$this->namedtag->setInt("XpTotal", $this->totalXp);
		}else{
			$this->totalXp = $this->namedtag->getInt("XpTotal");
		}

		if(!$this->namedtag->hasTag("XpSeed", IntTag::class)){
			$this->namedtag->setInt("XpSeed", $this->xpSeed ?? ($this->xpSeed = mt_rand(INT32_MIN, INT32_MAX)));
		}else{
			$this->xpSeed = $this->namedtag->getInt("XpSeed");
		}
	}

    protected function addAttributes() : void{
		parent::addAttributes();

        $this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::SATURATION));
        $this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::EXHAUSTION));
        $this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::HUNGER));
        $this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::EXPERIENCE_LEVEL));
        $this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::EXPERIENCE));
	}

    public function entityBaseTick(int $tickDiff = 1) : bool{
        $hasUpdate = parent::entityBaseTick($tickDiff);

        $this->doFoodTick($tickDiff);

        if($this->xpCooldown > 0){
            $this->xpCooldown--;
        }

        return $hasUpdate;
    }

    protected function doFoodTick(int $tickDiff = 1) : void{
		if($this->isAlive()){
			$food = $this->getFood();
			$health = $this->getHealth();
			$difficulty = $this->server->getDifficulty();

			$this->foodTickTimer += $tickDiff;
			if($this->foodTickTimer >= 80){
				$this->foodTickTimer = 0;
			}

            if($difficulty === Level::DIFFICULTY_PEACEFUL and $this->foodTickTimer % 10 === 0){
                if($food < $this->getMaxFood()){
                    $this->addFood(1.0);
                    $food = $this->getFood();
                }
                if($this->foodTickTimer % 20 === 0 and $health < $this->getMaxHealth()){
                    $this->heal(new EntityRegainHealthEvent($this, 1, EntityRegainHealthEvent::CAUSE_SATURATION));
                }
            }

            if($this->foodTickTimer === 0){
                if($food >= 18){
                    if($health < $this->getMaxHealth()){
                        $this->heal(new EntityRegainHealthEvent($this, 1, EntityRegainHealthEvent::CAUSE_SATURATION));
                        $this->exhaust(3.0, PlayerExhaustEvent::CAUSE_HEALTH_REGEN);
                    }
                }elseif($food <= 0){
                    if(($difficulty === Level::DIFFICULTY_EASY and $health > 10) or ($difficulty === Level::DIFFICULTY_NORMAL and $health > 1) or $difficulty === Level::DIFFICULTY_HARD){
                        $this->attack(new EntityDamageEvent($this, EntityDamageEvent::CAUSE_STARVATION, 1));
                    }
                }
            }

			if($food <= 6){
				if($this->isSprinting()){
					$this->setSprinting(false);
				}
			}
		}
	}

    public function getName() : string{
        return $this->getNameTag();
    }

    public function applyDamageModifiers(EntityDamageEvent $source) : void{
        parent::applyDamageModifiers($source);

        $type = $source->getCause();
        if($type !== EntityDamageEvent::CAUSE_SUICIDE and $type !== EntityDamageEvent::CAUSE_VOID
            and ($this->inventory->getItemInHand() instanceof Totem || $this->offHandInventory->getItem(0) instanceof Totem)){

            $compensation = $this->getHealth() - $source->getFinalDamage() - 1;
            if($compensation < 0){
                $source->setModifier($compensation, EntityDamageEvent::MODIFIER_TOTEM);
            }
        }
    }

    protected function applyPostDamageEffects(EntityDamageEvent $source) : void{
        parent::applyPostDamageEffects($source);
        $totemModifier = $source->getModifier(EntityDamageEvent::MODIFIER_TOTEM);
        if($totemModifier < 0){ //Totem prevented death
            ($event = new EntityConsumeTotemEvent($this))->call();

            if(!$event->isCancelled()){
                $this->removeAllEffects();

                $this->addEffect(new EffectInstance(Effect::getEffect(Effect::REGENERATION), 40 * 20, 1));
                $this->addEffect(new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 40 * 20, 1));
                $this->addEffect(new EffectInstance(Effect::getEffect(Effect::ABSORPTION), 5 * 20, 1));

                $this->broadcastEntityEvent(EntityEventPacket::CONSUME_TOTEM);
                $this->level->broadcastLevelEvent($this->add(0, $this->eyeHeight, 0), LevelEventPacket::EVENT_SOUND_TOTEM);

                $hand = $this->inventory->getItemInHand();
                if($hand instanceof Totem){
                    $hand->pop(); //Plugins could alter max stack size
                    $this->inventory->setItemInHand($hand);
                }elseif(($offHand = $this->offHandInventory->getItem(0)) instanceof Totem){
                    $offHand->pop();
                    $this->offHandInventory->setItem(0, $offHand);
                }
            }
        }
    }

    public function getDrops() : array{
        return array_filter(array_merge(
            $this->inventory !== null ? array_values($this->inventory->getContents()) : [],
            $this->offHandInventory !== null ? array_values($this->offHandInventory->getContents()) : [],
            $this->armorInventory !== null ? array_values($this->armorInventory->getContents()) : []
        ), function(Item $item) : bool{ return !$item->hasEnchantment(Enchantment::VANISHING); });
    }

	public function saveNBT() : void{
		parent::saveNBT();

        $this->namedtag->setInt("foodLevel", (int) $this->getFood());
        $this->namedtag->setFloat("foodExhaustionLevel", $this->getExhaustion());
        $this->namedtag->setFloat("foodSaturationLevel", $this->getSaturation());
        $this->namedtag->setInt("foodTickTimer", $this->foodTickTimer);

        $this->namedtag->setInt("XpLevel", $this->getXpLevel());
        $this->namedtag->setFloat("XpP", $this->getXpProgress());
        $this->namedtag->setInt("XpTotal", $this->totalXp);
        $this->namedtag->setInt("XpSeed", $this->xpSeed);

        if($this->offHandInventory !== null){
            $this->namedtag->setTag("OffHandItem", $this->getOffHandInventory()->getItemInOffhand()->nbtSerialize(0));
        }

		$this->namedtag->setTag("Inventory", $inventoryTag = new ListTag([], NBT::TAG_Compound));
		if($this->inventory !== null){
			//Normal inventory
			$slotCount = $this->inventory->getSize();
			for($slot = 0; $slot < $slotCount; ++$slot){
				$item = $this->inventory->getItem($slot);
				if($item->getId() !== ItemItem::AIR){
					$inventoryTag->push($item->nbtSerialize($slot));
				}
			}

			$this->namedtag->setInt("SelectedInventorySlot", $this->inventory->getHeldItemSlot());
		}

		if($this->enderChestInventory !== null){
			/** @var CompoundTag[] $items */
			$items = [];

			$slotCount = $this->enderChestInventory->getSize();
			for($slot = 0; $slot < $slotCount; ++$slot){
				$item = $this->enderChestInventory->getItem($slot);
				if(!$item->isNull()){
					$items[] = $item->nbtSerialize($slot);
				}
			}

			$this->namedtag->setTag("EnderChestInventory", new ListTag($items));
		}

		// TODO: saving in bedrock format?
		$this->namedtag->setTag("Skin", CompoundTag::create()
			->setString("Data", $this->skin->getMcpeSkin()->getSkinData())
			->setString("Name", $this->skin->getMcpeSkin()->getSkinId()));
	}

	public function spawnTo(Player $player) : void{
		if($player !== $this){
			parent::spawnTo($player);
		}
	}

	protected function sendSpawnPacket(Player $player) : void{
		$packets = [];

		if(!($this instanceof Player)){
			if($player instanceof BedrockPlayer){
				$pk = new BedrockPlayerListPacket();
				$pk->type = BedrockPlayerListPacket::TYPE_ADD;
				$pk->entries[] = BedrockPlayerListEntry::createAdditionEntry($this->getUniqueId(), $this->getId(), $this->getName(), $this->skin->getBedrockSkin());
			}else{
				$pk = new McpePlayerListPacket();
				$pk->type = McpePlayerListPacket::TYPE_ADD;
				$pk->entries[] = McpePlayerListEntry::createAdditionEntry($this->getUniqueId(), $this->getId(), $this->getName(), $this->skin->getMcpeSkin());
			}
			$packets[] = $pk;
		}

		$pk = new AddPlayerPacket();
		$pk->uuid = $this->getUniqueId();
		$pk->username = $this->getName();
		$pk->entityRuntimeId = $this->getId();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->item = $this->getInventory()->getItemInHand();
		$pk->metadata = $this->propertyManager->getAll();
		$packets[] = $pk;

		if($player instanceof BedrockPlayer){
			//TODO: Hack for MCPE 1.2.13+: DATA_NAMETAG is useless in AddPlayerPacket, so it has to be sent separately
			$this->sendData($player, [self::DATA_NAMETAG => [self::DATA_TYPE_STRING, $this->getNameTag()]]);
		}else{
            $this->sendData($player);
        }

		$this->armorInventory->sendContents($player);
        $this->offHandInventory->sendContents($player);

        if(!($this instanceof Player)){
            if($player instanceof BedrockPlayer){
                $pk = new BedrockPlayerListPacket();
                $pk->type = BedrockPlayerListPacket::TYPE_REMOVE;
                $pk->entries[] = BedrockPlayerListEntry::createRemovalEntry($this->getUniqueId());
            }else{
                $pk = new McpePlayerListPacket();
                $pk->type = McpePlayerListPacket::TYPE_REMOVE;
                $pk->entries[] = McpePlayerListEntry::createRemovalEntry($this->getUniqueId());
            }
            $packets[] = $pk;
        }
        $this->server->batchPackets([$player], $packets, true);
	}

    public function close() : void{
		if(!$this->closed){
			if($this->getFloatingInventory() instanceof FloatingInventory){
 				foreach($this->getFloatingInventory()->getContents() as $craftingItem){
 					$this->getInventory()->addItem($craftingItem);
					$this->getFloatingInventory()->removeItem($craftingItem);
 				}
 			}else{
 				$this->server->getLogger()->debug("Attempted to drop a null crafting inventory\n");
 			}
            if($this->inventory !== null){
                $this->inventory->removeAllViewers(true);
                $this->inventory = null;
            }
            if($this->enderChestInventory !== null){
                $this->enderChestInventory->removeAllViewers(true);
                $this->enderChestInventory = null;
            }
            if($this->offHandInventory !== null){
                $this->offHandInventory->removeAllViewers(true);
                $this->offHandInventory = null;
            }
			parent::close();
		}
	}


    /**
     * Wrapper around {@link Entity#getDataFlag} for player-specific data flag reading.
     */
    public function getPlayerFlag(int $flagId) : bool{
        return $this->getDataFlag(self::DATA_PLAYER_FLAGS, $flagId);
    }

    /**
     * Wrapper around {@link Entity#setDataFlag} for player-specific data flag setting.
     */
    public function setPlayerFlag(int $flagId, bool $value = true) : void{
        $this->setDataFlag(self::DATA_PLAYER_FLAGS, $flagId, $value, self::DATA_TYPE_BYTE);
    }
}

