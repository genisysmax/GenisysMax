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

use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;

/**
 * Called when an effect is added to an Entity.
 */
class EntityEffectAddEvent extends EntityEffectEvent{
    public static $handlerList = null;

    /** @var EffectInstance|null */
    private $oldEffect;

    /**
     * @param Entity         $entity
     * @param EffectInstance $effect
     * @param EffectInstance $oldEffect
     */
    public function __construct(Entity $entity, EffectInstance $effect, EffectInstance $oldEffect = null){
        parent::__construct($entity, $effect);
        $this->oldEffect = $oldEffect;
    }

    /**
     * Returns whether the effect addition will replace an existing effect already applied to the entity.
     *
     * @return bool
     */
    public function willModify() : bool{
        return $this->hasOldEffect();
    }

    /**
     * @return bool
     */
    public function hasOldEffect() : bool{
        return $this->oldEffect instanceof EffectInstance;
    }

    /**
     * @return EffectInstance|null
     */
    public function getOldEffect() : ?EffectInstance{
        return $this->oldEffect;
    }
}

