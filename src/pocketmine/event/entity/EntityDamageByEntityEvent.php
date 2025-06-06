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

namespace pocketmine\event\entity;

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;

/**
 * Called when an entity takes damage from another entity.
 */
class EntityDamageByEntityEvent extends EntityDamageEvent{
    /** @var int */
    private $damagerEntityId;
    /** @var float */
    private $knockBack;

    /**
     * @param Entity  $damager
     * @param Entity  $entity
     * @param int     $cause
     * @param float   $damage
     * @param float[] $modifiers
     * @param float   $knockBack
     */
    public function __construct(Entity $damager, Entity $entity, int $cause, float $damage, array $modifiers = [], float $knockBack = 0.4){
        $this->damagerEntityId = $damager->getId();
        $this->knockBack = $knockBack;
        parent::__construct($entity, $cause, $damage, $modifiers);
        $this->addAttackerModifiers($damager);
    }

    protected function addAttackerModifiers(Entity $damager) : void{
        if($damager instanceof Living){ //TODO: move this to entity classes
            if($damager->hasEffect(Effect::STRENGTH)){
                $this->setModifier($this->getBaseDamage() * 0.3 * $damager->getEffect(Effect::STRENGTH)->getEffectLevel(), self::MODIFIER_STRENGTH);
            }

            if($damager->hasEffect(Effect::WEAKNESS)){
                $this->setModifier(-($this->getBaseDamage() * 0.2 * $damager->getEffect(Effect::WEAKNESS)->getEffectLevel()), self::MODIFIER_WEAKNESS);
            }
        }
    }

    /**
     * Returns the attacking entity, or null if the attacker has been killed or closed.
     *
     * @return Entity|null
     */
    public function getDamager() : ?Entity{
        return $this->getEntity()->getLevel()->getServer()->findEntity($this->damagerEntityId);
    }

    /**
     * @return float
     */
    public function getKnockBack() : float{
        return $this->knockBack;
    }

    /**
     * @param float $knockBack
     */
    public function setKnockBack(float $knockBack) : void{
        $this->knockBack = $knockBack;
    }
}

