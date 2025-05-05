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

use pocketmine\entity\Entity;

/**
 * Called when an entity takes damage from an entity sourced from another entity, for example being hit by a snowball thrown by a Player.
 */
class EntityDamageByChildEntityEvent extends EntityDamageByEntityEvent{
    /** @var int */
    private $childEntityEid;

    /**
     * @param Entity  $damager
     * @param Entity  $childEntity
     * @param Entity  $entity
     * @param int     $cause
     * @param float   $damage
     * @param float[] $modifiers
     */
    public function __construct(Entity $damager, Entity $childEntity, Entity $entity, int $cause, float $damage, array $modifiers = []){
        $this->childEntityEid = $childEntity->getId();
        parent::__construct($damager, $entity, $cause, $damage, $modifiers);
    }

    /**
     * Returns the entity which caused the damage, or null if the entity has been killed or closed.
     *
     * @return Entity|null
     */
    public function getChild() : ?Entity{
        return $this->getEntity()->getLevel()->getServer()->findEntity($this->childEntityEid);
    }
}

