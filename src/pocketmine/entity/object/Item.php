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

namespace pocketmine\entity\object;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ItemDespawnEvent;
use pocketmine\event\entity\ItemSpawnEvent;
use pocketmine\item\Item as ItemItem;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\AddItemEntityPacket;
use pocketmine\Player;
use function min;

class Item extends Entity{
	public const NETWORK_ID = self::ITEM;

	/** @var string */
	protected $owner = "";
	/** @var string */
	protected $thrower = "";
	/** @var int */
	protected $pickupDelay = 0;
	/** @var ItemItem */
	protected $item;

	public float $width = 0.25;
	public float $height = 0.25;
	protected float $baseOffset = 0.125;

	public float $gravity = 0.04;
	public float $drag = 0.02;

	public bool $canCollide = false;

	protected function initEntity() : void{
		parent::initEntity();

		$this->setMaxHealth(5);
		$this->setHealth($this->namedtag->getShort("Health"));
		if($this->namedtag->hasTag("Age", ShortTag::class)){
			$this->age = $this->namedtag->getShort("Age");
		}
		if($this->namedtag->hasTag("PickupDelay", ShortTag::class)){
			$this->pickupDelay = $this->namedtag->getShort("PickupDelay");
		}
		if($this->namedtag->hasTag("Owner", StringTag::class)){
			$this->owner = $this->namedtag->getString("Owner");
		}
		if($this->namedtag->hasTag("Thrower", StringTag::class)){
			$this->thrower = $this->namedtag->getString("Thrower");
		}


		if(!$this->namedtag->hasTag("Item", CompoundTag::class)){
			$this->close();
			return;
		}

		$this->item = ItemItem::nbtDeserialize($this->namedtag->getCompoundTag("Item"));


		(new ItemSpawnEvent($this))->call();
	}

	public function attack(EntityDamageEvent $source): void
    {
		if(
			$source->getCause() === EntityDamageEvent::CAUSE_VOID or
			$source->getCause() === EntityDamageEvent::CAUSE_FIRE_TICK or
			$source->getCause() === EntityDamageEvent::CAUSE_ENTITY_EXPLOSION or
			$source->getCause() === EntityDamageEvent::CAUSE_BLOCK_EXPLOSION
		){
			parent::attack($source);
		}
	}

	protected function tryChangeMovement() : void{
		$this->checkObstruction($this->x, $this->y, $this->z);

		parent::tryChangeMovement();
	}

	public function entityBaseTick($tickDiff = 1): bool
    {
		if($this->closed){
			return false;
		}

		$hasUpdate = parent::entityBaseTick($tickDiff);

		if(!$this->isFlaggedForDespawn() and $this->pickupDelay > -1 and $this->pickupDelay < 32767){ //Infinite delay
			$this->pickupDelay -= $tickDiff;
			if($this->pickupDelay < 0){
				$this->pickupDelay = 0;
			}

			if($this->age > 6000){
				$ev = new ItemDespawnEvent($this);
				$ev->call();
				if($ev->isCancelled()){
					$this->age = 0;
				}else{
					$this->flagForDespawn();
					$hasUpdate = true;
				}
			}
		}

		return $hasUpdate;
	}

	public function saveNBT(): void
    {
		parent::saveNBT();
		$this->namedtag->setTag("Item", $this->item->nbtSerialize());
        $this->namedtag->setShort("Health", (int) $this->getHealth());
		$this->namedtag->setShort("Age", min($this->age, 0x7fff));
		$this->namedtag->setShort("PickupDelay", $this->pickupDelay);
		if($this->owner !== null){
			$this->namedtag->setString("Owner", $this->owner);
		}
		if($this->thrower !== null){
			$this->namedtag->setString("Thrower", $this->thrower);
		}
	}

	/**
	 * @return ItemItem
	 */
	public function getItem(){
		return $this->item;
	}

	public function canCollideWith(Entity $entity): bool
    {
		return false;
	}

	protected function applyDragBeforeGravity() : bool{
		return true;
	}

	/**
	 * @return int
	 */
	public function getPickupDelay(){
		return $this->pickupDelay;
	}

	/**
	 * @param int $delay
	 */
	public function setPickupDelay($delay){
		$this->pickupDelay = $delay;
	}

	/**
	 * @return string
	 */
	public function getOwner(){
		return $this->owner;
	}

	/**
	 * @param string $owner
	 */
	public function setOwner($owner){
		$this->owner = $owner;
	}

	/**
	 * @return string
	 */
	public function getThrower(){
		return $this->thrower;
	}

	/**
	 * @param string $thrower
	 */
	public function setThrower($thrower){
		$this->thrower = $thrower;
	}

	protected function sendSpawnPacket(Player $player) : void{
		$pk = new AddItemEntityPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->item = $this->getItem();
		$pk->metadata = $this->propertyManager->getAll();

		$player->sendDataPacket($pk);
	}
}


