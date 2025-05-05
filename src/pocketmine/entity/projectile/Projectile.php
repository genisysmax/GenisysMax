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

namespace pocketmine\entity\projectile;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\object\EnderCrystal;
use pocketmine\event\entity\EntityCombustByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\Timings;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\Player;
use function atan2;
use function ceil;
use function min;
use function sqrt;
use const M_PI;
use const PHP_INT_MAX;

abstract class Projectile extends Entity{

	/** @var Block|null */
	protected $blockHit;

	protected $damage = 0;

	protected $shootingEntity;

	public function __construct(Level $level, CompoundTag $nbt, ?Entity $shootingEntity = null){
		parent::__construct($level, $nbt);
        if($shootingEntity !== null){
            $this->setShootingEntity($shootingEntity);
        }
	}

	public function getShootingEntity(){
		return $this->shootingEntity;
	}

	public function setShootingEntity(Entity $entity){
		$this->setOwningEntity($entity);
		$this->shootingEntity = $entity;
	}

	public function attack(EntityDamageEvent $source): void
    {
		if($source->getCause() === EntityDamageEvent::CAUSE_VOID){
			parent::attack($source);
		}
	}

	protected function initEntity() : void{
		parent::initEntity();

		$this->setMaxHealth(1);
		$this->setHealth(1);

		if($this->namedtag->hasTag("Age", ShortTag::class)){
			$this->age = $this->namedtag->getShort("Age");
		}
		if($this->namedtag->hasTag("Damage", FloatTag::class)){
			$this->damage = $this->namedtag->getFloat("Damage");
		}

		do{
			$blockPos = null;
			$blockId = null;
			$blockData = null;

			if(
				$this->namedtag->hasTag("tileX", IntTag::class) and
				$this->namedtag->hasTag("tileZ", IntTag::class) and
				$this->namedtag->hasTag("tileY", IntTag::class)
			){
				$blockPos = new Position($this->namedtag->getInt("tileX"), $this->namedtag->getInt("tileY"), $this->namedtag->getInt("tileZ"), $this->level);
			}else{
				break;
			}

			if($this->namedtag->hasTag("blockId", IntTag::class)){
				$blockId = $this->namedtag->getInt("blockId");
			}else{
				break;
			}

			if($this->namedtag->hasTag("blockData", IntTag::class)){
				$blockData = $this->namedtag->getInt("blockData");
			}else{
				break;
			}

			$this->blockHit = Block::get($blockId, $blockData, $blockPos);
		}while(false);
	}

    public function canCollideWith(Entity $entity): bool
    {
        return ($entity instanceof Living || $entity instanceof EnderCrystal) && !$this->onGround and !($entity instanceof Player and $entity->isSpectator());
    }

	public function entityBaseTick($tickDiff = 1): bool
    {
		if($this->closed){
			return false;
		}

		$hasUpdate = parent::entityBaseTick($tickDiff);

		if(!$this->isFlaggedForDespawn()){
			if($this->age > 6000){
				$this->flagForDespawn();
				$hasUpdate = true;
			}
		}

		return $hasUpdate;
	}

	/**
	 * @param float $damage
	 */
	public function setDamage(float $damage){
		$this->damage = $damage;
	}

	/**
	 * Returns the amount of damage this projectile will deal to the entity it hits.
	 * @return int
	 */
	public function getResultDamage() : int{
		return (int) ceil(sqrt($this->motionX ** 2 + $this->motionY ** 2 + $this->motionZ ** 2) * $this->damage);
	}

	/**
	 * @return float
	 */
	public function getBaseKnockBack() : float{
		return 1.0;
	}

    public function saveNBT(): void
    {
        parent::saveNBT();
        $this->namedtag->setShort("Age", min($this->age, 0x7fff));
        $this->namedtag->setFloat("Damage", $this->damage);

        if($this->blockHit !== null){
            $this->namedtag->setInt("tileX", $this->blockHit->x);
            $this->namedtag->setInt("tileY", $this->blockHit->y);
            $this->namedtag->setInt("tileZ", $this->blockHit->z);

            $this->namedtag->setInt("blockId", $this->blockHit->getId());
            $this->namedtag->setInt("blockData", $this->blockHit->getDamage());
        }
    }

	protected function applyDragBeforeGravity() : bool{
		return true;
	}

	public function onNearbyBlockChange() : void{
		if(
			$this->blockHit !== null and
			$this->level->isChunkLoaded($this->getFloorX() >> 4, $this->getFloorZ() >> 4) and
			(
				$this->blockHit->getId() !== ($block = $this->level->getBlockAt($this->blockHit->x, $this->blockHit->y, $this->blockHit->z))->getId() or
				$this->blockHit->getDamage() !== $block->getDamage()
			)
		){
			$this->blockHit = null;
		}

		parent::onNearbyBlockChange();
	}

    public function hasMovementUpdate() : bool{
        return $this->blockHit === null and parent::hasMovementUpdate();
    }

	public function move($dx, $dy, $dz): void
    {
		$this->blocksAround = null;

		Timings::$entityMoveTimer->startTiming();

		$start = $this->asVector3();
		$end = $start->add($dx, $dy, $dz);

		$blockHit = null;
		$entityHit = null;
		$hitResult = null;

		foreach(VoxelRayTrace::betweenPoints($start, $end) as $vector3){
			$block = $this->level->getBlockAt($vector3->x, $vector3->y, $vector3->z);

			$blockHitResult = $this->calculateInterceptWithBlock($block, $start, $end);
			if($blockHitResult !== null){
				$end = $blockHitResult->hitVector;
				$blockHit = $block;
				$hitResult = $blockHitResult;
				break;
			}
		}

		$entityDistance = PHP_INT_MAX;

		$newDiff = $end->subtract($start);
		foreach($this->level->getCollidingEntities($this->boundingBox->addCoord($newDiff->x, $newDiff->y, $newDiff->z)->expand(1, 1, 1), $this) as $entity){
			if(($entity instanceof Player and $entity->isSpectator()) or ($entity->getId() === $this->getOwningEntityId() and $this->ticksLived < 5)){
				continue;
			}

			$entityBB = (clone $entity->boundingBox)->expand(0.3, 0.3, 0.3);
			$entityHitResult = $entityBB->calculateIntercept($start, $end);

			if($entityHitResult === null){
				continue;
			}

			$distance = $this->distanceSquared($entityHitResult->hitVector);

			if($distance < $entityDistance){
				$entityDistance = $distance;
				$entityHit = $entity;
				$hitResult = $entityHitResult;
				$end = $entityHitResult->hitVector;
			}
		}

		$this->x = $end->x;
		$this->y = $end->y;
		$this->z = $end->z;
		$this->recalculateBoundingBox();

		if($hitResult !== null){
            /** @var ProjectileHitEvent|null $ev */
            if($entityHit !== null){
                $ev = new ProjectileHitEntityEvent($this, $hitResult, $entityHit);
            }elseif($blockHit !== null){
                $ev = new ProjectileHitBlockEvent($this, $hitResult, $blockHit);
            }else{
                \assert(false, "unknown hit type");
            }

            $ev->call();
            $this->onHit($ev);

            if($ev instanceof ProjectileHitEntityEvent){
                $this->onHitEntity($ev->getEntityHit(), $ev->getRayTraceResult());
            }else{
                $this->onHitBlock($ev->getBlockHit(), $ev->getRayTraceResult());
            }

			$this->isCollided = $this->onGround = true;
			$this->motionX = $this->motionY = $this->motionZ = 0;
		}else{
			$this->isCollided = $this->onGround = false;
			$this->blockHit = null;

			//recompute angles...
			$f = sqrt(($dx ** 2) + ($dz ** 2));
			$this->yaw = (atan2($dx, $dz) * 180 / M_PI);
			$this->pitch = (atan2($dy, $f) * 180 / M_PI);
		}

		$this->checkChunks();
		$this->checkBlockCollision();

		Timings::$entityMoveTimer->stopTiming();
	}

	/**
	 * Called by move() when raytracing blocks to discover whether the block should be considered as a point of impact.
	 * This can be overridden by other projectiles to allow altering the blocks which are collided with (for example
	 * some projectiles collide with any non-air block).
	 *
	 * @param Block   $block
	 * @param Vector3 $start
	 * @param Vector3 $end
	 *
	 * @return RayTraceResult|null the result of the ray trace if successful, or null if no interception is found.
	 */
	protected function calculateInterceptWithBlock(Block $block, Vector3 $start, Vector3 $end) : ?RayTraceResult{
		return $block->calculateIntercept($start, $end);
	}

	/**
	 * Called when the projectile hits something. Override this to perform non-target-specific effects when the
	 * projectile hits something.
	 *
	 * @param ProjectileHitEvent $event
	 */
	protected function onHit(ProjectileHitEvent $event) : void{

	}

	/**
	 * Called when the projectile collides with an Entity.
	 *
	 * @param Entity         $entityHit
	 * @param RayTraceResult $hitResult
	 *
	 * @return bool
	 */
	protected function onHitEntity(Entity $entityHit, RayTraceResult $hitResult) : bool
    {
        $damage = $this->getResultDamage();

        $damageEv = null;
        if($damage >= 0){
            if($this->getOwningEntity() === null){
                $ev = new EntityDamageByEntityEvent($this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
            }else{
                $ev = new EntityDamageByChildEntityEvent($this->getOwningEntity(), $this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
            }

            $damageEv = $ev;

            $entityHit->attack($ev);

            if($this->isOnFire()){
                $ev = new EntityCombustByEntityEvent($this, $entityHit, 5);
                $ev->call();
                if(!$ev->isCancelled()){
                    $entityHit->setOnFire($ev->getDuration());
                }
            }
        }

        $this->flagForDespawn();

        return ($damageEv !== null && !$damageEv->isCancelled());
    }

	/**
	 * Called when the projectile collides with a Block.
	 *
	 * @param Block          $blockHit
	 * @param RayTraceResult $hitResult
	 */
	protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult) : void{
		$this->blockHit = clone $blockHit;
	}
}


