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



namespace pocketmine\entity\object;

use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\entity\Human;
use pocketmine\event\player\PlayerPickupExpOrbEvent;
use pocketmine\nbt\tag\LongTag;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Player;

class XPOrb extends Entity{
	const NETWORK_ID = EntityIds::XP_ORB;

	/**
	 * Max distance an orb will follow a player across.
	 */
	public const MAX_TARGET_DISTANCE = 8.0;
	public const ORB_SPLIT_SIZES = [2477, 1237, 617, 307, 149, 73, 37, 17, 7, 3, 1]; //This is indexed biggest to smallest so that we can return as soon as we found the biggest value.

	public float $width = 0.25;
	public float $height = 0.25;

	public float $gravity = 0.04;
	public float $drag = 0.02;

	/** @var int */
	protected $age = 0;

	protected $experience = 0;

	protected $range = 6;

	/**
	 * @var int
	 * Ticker used for determining interval in which to look for new target players.
	 */
	protected $lookForTargetTime = 0;

	/**
	 * @var int|null
	 * Runtime entity ID of the player this XP orb is targeting.
	 */
	protected $targetPlayerRuntimeId = null;

	/**
	 * Returns the largest size of normal XP orb that will be spawned for the specified amount of XP. Used to split XP
	 * up into multiple orbs when an amount of XP is dropped.
	 */
	public static function getMaxOrbSize(int $amount) : int{
		foreach(self::ORB_SPLIT_SIZES as $split){
			if($amount >= $split){
				return $split;
			}
		}

		return 1;
	}

	/**
	 * Splits the specified amount of XP into an array of acceptable XP orb sizes.
	 *
	 * @return int[]
	 */
	public static function splitIntoOrbSizes(int $amount) : array{
		$result = [];

		while($amount > 0){
			$size = self::getMaxOrbSize($amount);
			$result[] = $size;
			$amount -= $size;
		}

		return $result;
	}

	public function initEntity(): void
    {
        parent::initEntity();
        if ($this->namedtag->hasTag("Experience", LongTag::class)) {
            $this->experience = $this->namedtag->getLong("Experience");
        } else {
            $this->close();
        }
    }

	public function hasTargetPlayer() : bool{
		return $this->targetPlayerRuntimeId !== null;
	}

	public function getTargetPlayer() : ?Human{
		if($this->targetPlayerRuntimeId === null){
			return null;
		}

		$entity = $this->level->getEntity($this->targetPlayerRuntimeId);
		if($entity instanceof Human){
			return $entity;
		}

		return null;
	}

	public function setTargetPlayer(?Human $player) : void{
		$this->targetPlayerRuntimeId = $player?->getId();
	}

	/**
	 * @param $tickDiff
	 *
	 * @return bool
	 */
	public function entityBaseTick($tickDiff = 1): bool
    {
		if($this->closed){
			return false;
		}

		$hasUpdate = parent::entityBaseTick($tickDiff);

		$this->age += $tickDiff;
		if($this->age > 6000){
			$this->flagForDespawn();
			return true;
		}

		$currentTarget = $this->getTargetPlayer();
		if($currentTarget !== null and (!$currentTarget->isAlive() or $currentTarget->distanceSquared($this) > self::MAX_TARGET_DISTANCE ** 2)){
			$currentTarget = null;
		}

		if($this->lookForTargetTime >= 20){
			if($currentTarget === null){
				$newTarget = $this->level->getNearestEntity($this, self::MAX_TARGET_DISTANCE, Human::class);

				if($newTarget instanceof Human and !($newTarget instanceof Player and $newTarget->isSpectator())){
					$currentTarget = $newTarget;
				}
			}

			$this->lookForTargetTime = 0;
		}else{
			$this->lookForTargetTime += $tickDiff;
		}

		$this->setTargetPlayer($currentTarget);

		if($currentTarget instanceof Player){
			$vector = $currentTarget->add(0, $currentTarget->getEyeHeight() / 2, 0)->subtract($this)->divide(self::MAX_TARGET_DISTANCE);

			$distance = $vector->lengthSquared();
			if($distance < 1){
				$diff = $vector->normalize()->multiply(0.2 * (1 - sqrt($distance)) ** 2);

				$this->motionX += $diff->x;
				$this->motionY += $diff->y;
				$this->motionZ += $diff->z;
			}

			if($currentTarget->canPickupXp() and $this->boundingBox->intersectsWith($currentTarget->getBoundingBox())){
				($ev = new PlayerPickupExpOrbEvent($currentTarget, $this->getExperience()))->call();
				if(!$ev->isCancelled()){
					$this->flagForDespawn();
					if($this->getExperience() > 0){
						$this->level->broadcastLevelEvent($this, LevelEventPacket::EVENT_SOUND_ORB, mt_rand());
						$currentTarget->addXp($this->getExperience());
						$currentTarget->resetXpCooldown();

						//TODO: check Mending enchantment
					}
				}
			}
		}

		return $hasUpdate;
	}

	protected function tryChangeMovement(): void
    {
		$this->checkObstruction($this->x, $this->y, $this->z);
		parent::tryChangeMovement();
	}

	/**
	 * @param Entity $entity
	 *
	 * @return bool
	 */
	public function canCollideWith(Entity $entity): bool
    {
		return false;
	}

	public function canBeCollidedWith() : bool{
		return false;
	}

	/**
	 * @param $exp
	 */
	public function setExperience($exp){
		$this->experience = $exp;
	}

	/**
	 * @return int
	 */
	public function getExperience(){
		return $this->experience;
	}
}


