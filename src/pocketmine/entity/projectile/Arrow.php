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
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\event\entity\ArrowHitEntityEvent;
use pocketmine\item\Bow;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\Potion;
use pocketmine\level\Level;
use pocketmine\level\particle\MobSpellParticle;
use pocketmine\math\RayTraceResult;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\utils\Color;
use function mt_rand;
use function sqrt;

class Arrow extends Projectile{
	public const NETWORK_ID = self::ARROW;

	public float $width = 0.25;
	public float $height = 0.25;

	public float $gravity = 0.05;
	public float $drag = 0.01;

	protected $damage = 2.0;

	/** @var Bow */
	protected $bow;
	/** @var Color */
	protected $color;

	public function __construct(Level $level, CompoundTag $nbt, Entity $shootingEntity = null, bool $critical = false, ?Bow $bow = null){
		parent::__construct($level, $nbt, $shootingEntity);
		$this->setCritical($critical);
		$this->bow = $bow;
	}

	public function getBow() : ?Bow{
		return $this->bow;
	}

	public function setBow(?Item $bow){
		if($bow !== null and $bow->getId() === Item::BOW){
			$this->bow = $bow;
		}else{
			$this->bow = null;
		}
	}

    public function isCritical() : bool{
        return $this->getGenericFlag(self::DATA_FLAG_CRITICAL);
    }

    public function setCritical(bool $value = true) : void{
        $this->setGenericFlag(self::DATA_FLAG_CRITICAL, $value);
    }

	protected function initEntity() : void{
		parent::initEntity();

		if($this->namedtag->hasTag("Bow", CompoundTag::class)){
			$this->bow = Item::nbtDeserialize($this->namedtag->getCompoundTag("Bow"));
		}

		if($this->namedtag->hasTag("Critical", ByteTag::class)){
			$this->setCritical($this->namedtag->getByte("Critical") > 0);
		}

		if($this->namedtag->hasTag("Potion", ShortTag::class)){
			$this->setPotionId($this->namedtag->getShort("Potion"));
		}
	}

	/**
	 * @return int
	 */
	public function getPotionId() : int{
		return $this->propertyManager->getShort(self::DATA_POTION_AUX_VALUE) ?? 0;
	}

	public function getPotionEffect() : ?EffectInstance{
		return Potion::getEffectByMeta($this->getPotionId());
	}

	/**
	 * @param int $potionId
	 */
	public function setPotionId(int $potionId) : void{
        $this->propertyManager->setShort(self::DATA_POTION_AUX_VALUE, $potionId);

		$effect = $this->getPotionEffect();
		if($effect !== null){
			$color = $effect->getColor();
			$this->color = new Color($color[0], $color[1], $color[2]);
		}else{
			$this->color = null;
		}
	}

	public function getResultDamage() : int{
		$base = parent::getResultDamage();
		if($this->isCritical()){
			return ($base + mt_rand(0, (int) ($base / 2) + 1));
		}else{
			return $base;
		}
	}

	public function entityBaseTick($tickDiff = 1): bool
    {
		if($this->closed){
			return false;
		}

		$hasUpdate = parent::entityBaseTick($tickDiff);

		if($this->color !== null){
			$this->level->addParticle(new MobSpellParticle($this->asVector3(), $this->color->getR(), $this->color->getG(), $this->color->getB()));
		}
		if($this->blockHit !== null){
			if($this->age > 1200){
				$this->flagForDespawn();
				$hasUpdate = true;
			}
		}

		return $hasUpdate;
	}

	public function canBePickedUp() : bool{
		return $this->blockHit !== null;
	}

	public function saveNBT(): void
    {
		parent::saveNBT();
		if($this->bow !== null){
			$this->namedtag->setTag("Bow", $this->bow->nbtSerialize());
		}

		$this->namedtag->setByte("Critical", $this->isCritical() ? 1 : 0);
		$this->namedtag->setShort("Potion", $this->getPotionId());
	}

	protected function onHitEntity(Entity $entityHit, RayTraceResult $hitResult) : bool{
		if(parent::onHitEntity($entityHit, $hitResult)){
			if($entityHit instanceof Living){
                $effect = $this->getPotionEffect();

                ($ev = new ArrowHitEntityEvent($this, $entityHit, $effect, true))->call();

                $effect = $ev->getEffect();
				if($effect !== null){
					$entityHit->addEffect($effect->setDuration(intdiv($effect->getDuration(), 8)));
				}

				$horizontalSpeed = sqrt($this->motionX ** 2 + $this->motionZ ** 2);
				if($ev->isPunch() and $horizontalSpeed > 0 and $this->bow !== null and ($enchantment = $this->bow->getEnchantment(Enchantment::PUNCH)) !== null){
					$multiplier = $enchantment->getLevel() * 0.6 / $horizontalSpeed;
					$entityHit->setMotion($entityHit->getMotion()->add($this->motionX * $multiplier, 0.1, $this->motionZ * $multiplier));
				}
			}
			return true;
		}
		return false;
	}

	protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult) : void{
		$this->setCritical(false);
		if($this->level !== null){ //Prevents error of $this->level returning null
			$this->level->broadcastLevelSoundEvent($hitResult->getHitVector(), LevelSoundEventPacket::SOUND_BOW_HIT);
		}

		parent::onHitBlock($blockHit, $hitResult);
		$this->broadcastEntityEvent(EntityEventPacket::ARROW_SHAKE, 7); //7 ticks
	}
}


