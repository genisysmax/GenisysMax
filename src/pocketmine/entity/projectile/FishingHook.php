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

use pocketmine\block\Water;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\fish\FishingRodCaughtEntityEvent;
use pocketmine\event\player\fish\FishingRodCaughtFishEvent;
use pocketmine\event\player\fish\FishingRodHookedEntityEvent;
use pocketmine\item\FishingRod;
use pocketmine\item\Item as ItemItem;
use pocketmine\level\particle\GenericParticle;
use pocketmine\level\particle\Particle;
use pocketmine\math\Math;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\network\mcpe\protocol\SetEntityLinkPacket;
use pocketmine\Player;
use function abs;
use function cos;
use function floor;
use function lcg_value;
use function mt_rand;
use function sin;
use const M_PI;

class FishingHook extends Projectile{
	public const NETWORK_ID = self::FISHING_HOOK;

	private const STATE_FLYING = 0;
	private const STATE_HOOKED_ENTITY = 1;
	private const STATE_BOBBING = 2;

    public float $width = 0.25;
    public float $height = 0.25;
    public float $gravity = 0.1;
    public float $drag = 0.05;

	/** @var Entity|null */
	protected $hookedEntity;

	/** @var int */
	protected $ticksCatchable = 0;
	/** @var int */
	protected $ticksCaughtDelay = 0;
	/** @var int */
	protected $ticksCatchableDelay = 0;
	/** @var float */
	protected $fishApproachAngle = 0;
	/** @var int */
	protected $ticksInGround = 0;
	/** @var int */
	protected $state = self::STATE_FLYING;

	/**
	 * @param float $x
	 * @param float $y
	 * @param float $z
	 * @param float $f1
	 * @param float $f2
	 */
	public function handleHookCasting(float $x, float $y, float $z, float $f1, float $f2) : void{
		$f = sqrt($x * $x + $y * $y + $z * $z);
		if($f == 0){
			return;
		}

		$x /= $f;
		$y /= $f;
		$z /= $f;
		$x += Math::randomGaussian() * 0.0075 * $f2;
		$y += Math::randomGaussian() * 0.0075 * $f2;
		$z += Math::randomGaussian() * 0.0075 * $f2;
		$x *= $f1;
		$y *= $f1;
		$z *= $f1;

		$this->setMotion($this->getMotion()->add($x, $y, $z));
	}

	public function attack(EntityDamageEvent $source): void
    {
		if(!$source instanceof EntityDamageByEntityEvent){
			parent::attack($source);
		}
	}

	/**
	 * @param Entity $entityHit
	 * @param RayTraceResult $hitResult
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult) : bool{
		$entityHit->attack(new EntityDamageByChildEntityEvent($this->shootingEntity, $this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, 0));

		if($this->shootingEntity instanceof Player){
			$ev = new FishingRodHookedEntityEvent($this->shootingEntity, $this, $entityHit);
			$ev->call();
			if(!$ev->isCancelled()){
				$this->setHookedEntity($entityHit);
			}
		}
		return true;
	}

	public function setHookedEntity(Entity $entity) : void{
        $this->propertyManager->setVector3(self::DATA_RIDER_SEAT_POSITION, new Vector3(0, $entity->height * 0.15, 0));

		$pk = new SetEntityLinkPacket();
		$pk->fromEntityUniqueId = $entity->getId();
		$pk->toEntityUniqueId = $this->getId();
		$pk->type = SetEntityLinkPacket::TYPE_PASSENGER;
		$this->server->broadcastPacket($this->getViewers(), $pk);

		$this->hookedEntity = $entity;
	}

	/**
	 * @return Entity|null
	 */
	public function getHookedEntity() : ?Entity{
		return $this->hookedEntity;
	}

	public function releaseHookedEntity() : void{
		if($this->hookedEntity !== null){
			$pk = new SetEntityLinkPacket();
			$pk->fromEntityUniqueId = $this->hookedEntity->getId();
			$pk->toEntityUniqueId = $this->getId();
			$pk->type = SetEntityLinkPacket::TYPE_REMOVE;
			$this->server->broadcastPacket($this->getViewers(), $pk);
		}
		$this->hookedEntity = null;
	}

	public function entityBaseTick($tickDiff = 1): bool
    {
		if($this->closed){
			return false;
		}

		$hasUpdate = parent::entityBaseTick($tickDiff);

		$owner = $this->getOwningEntity();
		if($owner instanceof Player){
			if($owner->closed or !$owner->isAlive() or !$owner->getInventory()->getItemInHand() instanceof FishingRod or $owner->distanceSquared($this) > 1024){ // 32 ** 2
				$this->flagForDespawn();
				return true;
			}

			if($this->isInGround()){
				$this->ticksInGround += $tickDiff;

				if($this->ticksInGround >= 1200){
					$this->flagForDespawn();
					return true;
				}
			}

			$waterHeight = 0.0;
			$block = $this->level->getBlock($this);

			if($block instanceof Water){
				$meta = $block->getDamage();
				if($meta & 0x07 === 0 and $block->getSide(Vector3::SIDE_UP) instanceof Water){
					$waterHeight = 1.0;
				}else{
					$waterHeight = 1.0 - $block->getFluidHeightPercent();
				}
			}

			if($this->state === self::STATE_FLYING){
				if($this->hookedEntity !== null){
					$this->motionX = 0;
					$this->motionY = 0;
					$this->motionZ = 0;
					$this->state = self::STATE_HOOKED_ENTITY;

					$hasUpdate = true;
				}elseif($waterHeight > 0.0){
					$this->motionX *= 0.3;
					$this->motionY *= 0.2;
					$this->motionZ *= 0.3;
					$this->state = self::STATE_BOBBING;

					$hasUpdate = true;
				}
			}elseif($this->state === self::STATE_HOOKED_ENTITY){
				if($this->hookedEntity !== null){
					if($this->hookedEntity->closed or $this->hookedEntity->isFlaggedForDespawn()){
						$this->releaseHookedEntity();
						$this->state = self::STATE_FLYING;
						$hasUpdate = true;
					}else{
						$this->x = $this->hookedEntity->x;
						$this->y = $this->hookedEntity->y + $this->hookedEntity->height * 0.8;
						$this->z = $this->hookedEntity->z;
					}
				}
			}elseif($this->state === self::STATE_BOBBING){
				$this->motionX *= 0.9;
				$this->motionZ *= 0.9;

				$diff = $this->y - floor($this->y) + $this->motionY - $waterHeight;
				if(abs($diff) < 0.01){
					$diff += Math::signum($diff) * 0.1;
				}

				$this->motionY -= $diff * lcg_value() * 0.2;

				if($waterHeight > 0.0){
					$this->fishLogic();
				}
			}
		}else{
			$this->flagForDespawn();
		}

		return $hasUpdate;
	}

	protected function applyGravity() : void{
		if(!($this->level->getBlock($this) instanceof Water)){
			parent::applyGravity();
		}
	}

	private function fishLogic() : void{
		if($this->ticksCatchable > 0){
			--$this->ticksCatchable;

			if($this->ticksCatchable <= 0){
				$this->ticksCaughtDelay = 0;
				$this->ticksCatchableDelay = 0;
			}else{
				$this->motionY -= 0.2 * lcg_value() * lcg_value();
			}
		}elseif($this->ticksCatchableDelay > 0){
			--$this->ticksCatchableDelay;

			if($this->ticksCatchableDelay > 0){
				$this->fishApproachAngle += Math::randomGaussian() * 4.0;
				$f = $this->fishApproachAngle / 180 * M_PI;
				$f1 = sin($f);
				$f2 = cos($f);
				$d0 = $this->x + ($f1 * $this->ticksCatchableDelay * 0.1);
				$d1 = $this->y + 1;
				$d2 = $this->z + ($f2 * $this->ticksCatchableDelay * 0.1);
				$block = $this->level->getBlock(new Vector3($d0, $d1 - 1, $d2));

				if($block instanceof Water){
					if(lcg_value() < 0.15){
						$this->level->addParticle(new GenericParticle(new Vector3($d0, $d1 - 0.1, $d2), Particle::TYPE_BUBBLE));
					}

					$this->level->addParticle(new GenericParticle(new Vector3($d0, $d1, $d2), Particle::TYPE_WATER_WAKE));
				}
			}else{
				$this->motionY = -0.4 * (0.6 + lcg_value() * 0.4);
				$this->broadcastEntityEvent(EntityEventPacket::FISH_HOOK_HOOK);
				$this->ticksCatchable = mt_rand(20, 40);
			}
		}elseif($this->ticksCaughtDelay > 0){
			--$this->ticksCaughtDelay;
			$f5 = 0.15;

			if($this->ticksCaughtDelay < 20){
				$f5 = ($f5 + (20 - $this->ticksCaughtDelay) * 0.05);
			}elseif($this->ticksCaughtDelay < 40){
				$f5 = ($f5 + (40 - $this->ticksCaughtDelay) * 0.02);
			}elseif($this->ticksCaughtDelay < 60){
				$f5 = ($f5 + (60 - $this->ticksCaughtDelay) * 0.01);
			}

			if(lcg_value() < $f5){
				$f6 = lcg_value() * 2 * M_PI;
				$f7 = 25 + lcg_value() * 35;

				$d4 = $this->x + sin($f6) * $f7 * 0.1;
				$d5 = floor($this->y);
				$d6 = $this->z + cos($f6) * $f7 * 0.1;
				$block = $this->level->getBlock(new Vector3($d4, $d5, $d6));

				if($block instanceof Water){
					$this->level->addParticle(new GenericParticle(new Vector3($d4, $d5 + 1, $d6), Particle::TYPE_SPLASH));
				}
			}

			if($this->ticksCaughtDelay <= 0){
				$this->fishApproachAngle = lcg_value() * 360.0;
				$this->ticksCatchableDelay = mt_rand(20, 80);
			}
		}else{
			$this->ticksCaughtDelay = mt_rand(100, 600);
			$this->ticksCaughtDelay -= 20 * 5; // TODO: Lure
		}
	}

	public function close() : void{
		$owner = $this->getOwningEntity();
		if($owner instanceof Player and $owner->getFishingHook() === $this){
			$owner->setFishingHook(null);
		}

		$this->releaseHookedEntity();
		parent::close();
	}

	public function handleHookRetraction() : int{
		$damage = 0;
		if($this->shootingEntity instanceof Player){
			if($this->hookedEntity !== null){
				$ev = new FishingRodCaughtEntityEvent($this->shootingEntity, $this, $this->hookedEntity, 0.1);
				$ev->call();

				if(!$ev->isCancelled()){
					$eyePos = $this->shootingEntity->add(0, $this->shootingEntity->getEyeHeight(), 0);
					$this->hookedEntity->setMotion($eyePos->subtract($this)->multiply($ev->getForce()));
					$damage = 3;
				}
			}elseif($this->ticksCatchable > 0){
				// TODO: Random weighted items
				$items = [
					ItemItem::RAW_FISH, ItemItem::PUFFERFISH, ItemItem::RAW_SALMON, ItemItem::CLOWNFISH
				];
				$randomFish = $items[mt_rand(0, count($items) - 1)];
				$result = ItemItem::get($randomFish);

				$ev = new FishingRodCaughtFishEvent($this->shootingEntity, $this);
				$ev->call();

				if(!$ev->isCancelled()){
					$vector = $this->shootingEntity->subtract($this)->multiply(0.1);
					$this->level->dropItem($this, $result, $vector->add(0, $vector->length() * 0.08, 0));
					$damage = 1;
				}
			}
			if($this->isInGround()){
				$damage = 2;
			}

			$this->flagForDespawn();
		}

		return $damage;
	}

	public function isInGround() : bool{
		return $this->blockHit !== null;
	}

	protected function applyDragBeforeGravity() : bool{
		return false;
	}
}

