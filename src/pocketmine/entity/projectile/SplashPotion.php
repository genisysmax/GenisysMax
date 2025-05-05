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
use pocketmine\block\BlockIds;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\item\Potion;
use pocketmine\level\particle\SpellParticle;
use pocketmine\math\RayTraceResult;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\utils\Color;
use function sqrt;

class SplashPotion extends Throwable {
	public const NETWORK_ID = self::SPLASH_POTION;

	public float $gravity = 0.1;
	public float $drag = 0.05;

    protected function initEntity() : void{
        parent::initEntity();

        $this->setPotionId($this->namedtag->getShort("PotionId", 0));
    }

    public function saveNBT() : void{
        parent::saveNBT();
        $this->namedtag->setShort("PotionId", $this->getPotionId());
    }

    protected function onHit(ProjectileHitEvent $event) : void{
        $effects = Potion::getEffectsById($this->getPotionId());
        $hasEffects = true;

        if(count($effects) === 0){
            $particle = new SpellParticle($this->add(0, 0.01), 0x38, 0x5d, 0xc6);
            $hasEffects = false;
        }else{
            $colors = [];
            foreach($effects as $effect){
                $level = $effect->getEffectLevel();
                for($j = 0; $j < $level; ++$j){
                    $colors[] = $effect->getColor();
                }
            }
            $color = Color::mix(...$colors);
            $particle = new SpellParticle($this->add(0, 0.01), $color->getR(), $color->getG(), $color->getB(), $color->getA());
        }

        $this->getLevel()->addParticle($particle);
        $this->level->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_GLASS);

        if($hasEffects){
            foreach($this->getLevel()->getCollidingEntities($this->boundingBox->expandedCopy(4.125, 2.125, 4.125), $this) as $entity){
                if($entity instanceof Living and $entity->isAlive()){
                    $distanceSquared = $entity->getPosition()->add(0, $entity->getEyeHeight())->distanceSquared($this);
                    if($distanceSquared > 16){ //4 blocks
                        continue;
                    }

                    $distanceMultiplier = 1 - (sqrt($distanceSquared) / 4);
                    if($event instanceof ProjectileHitEntityEvent && $entity === $event->getEntityHit()){
                        $distanceMultiplier = 1.0;
                    }

                    foreach($effects as $effect){
                        $effect->setDuration((int)($effect->getDuration() * 0.75 * $distanceMultiplier));
                        $entity->addEffect($effect);
                    }
                }
            }
        }elseif($event instanceof ProjectileHitBlockEvent && in_array($this->getPotionId(), [0, 1, 2, 3, 4])){ // no effects
            $blockIn = $event->getBlockHit()->getSide($event->getRayTraceResult()->getHitFace());

            if($blockIn->getId() === BlockIds::FIRE){
                $this->getLevel()->setBlock($blockIn->asVector3(), Block::get(BlockIds::AIR));
            }

            foreach($blockIn->getHorizontalSides() as $horizontalSide){
                if($horizontalSide->getId() === BlockIds::FIRE){
                    $this->getLevel()->setBlock($horizontalSide->asVector3(), Block::get(BlockIds::AIR));
                }
            }
        }
    }

    protected function onHitEntity(Entity $entityHit, RayTraceResult $hitResult) : bool{
        return false;
    }

    /**
     * Returns the meta value of the potion item that this splash potion corresponds to. This decides what effects will be applied to the entity when it collides with its target.
     */
    public function getPotionId() : int{
        return $this->propertyManager->getShort(self::DATA_POTION_AUX_VALUE) ?? 0;
    }

    public function setPotionId(int $id) : void{
        $this->propertyManager->setShort(self::DATA_POTION_AUX_VALUE, $id);
    }

    /**
     * Returns whether this splash potion will create an area-effect cloud when it lands.
     */
    public function willLinger() : bool{
        return $this->getGenericFlag(self::DATA_FLAG_LINGER);
    }

    /**
     * Sets whether this splash potion will create an area-effect-cloud when it lands.
     */
    public function setLinger(bool $value = true) : void{
        $this->setGenericFlag(self::DATA_FLAG_LINGER, $value);
    }
}


