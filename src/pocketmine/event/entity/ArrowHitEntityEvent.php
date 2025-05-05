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



namespace pocketmine\event\entity;

use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;

class ArrowHitEntityEvent extends EntityEvent{
	public static $handlerList = null;

	/** @var Entity */
	private $entityHit;
    /** @var null|EffectInstance */
    private $effect;
    /** @var bool */
    private $isPunch;

	public function __construct(Entity $entity, Entity $entityHit, null|EffectInstance $effect, bool $punch){
        $this->entity = $entity;
		$this->entityHit = $entityHit;
        $this->effect = $effect;
        $this->isPunch = $punch;
	}

	/**
	 * Returns the Entity struck by the projectile.
	 *
	 * @return Entity
	 */
	public function getEntityHit() : Entity{
		return $this->entityHit;
	}

    public function getEffect() : null|EffectInstance{
        return $this->effect;
    }

    public function setEffect(null|EffectInstance $effect) : void{
        $this->effect = $effect;
    }

    public function isPunch() : bool{
        return $this->isPunch;
    }

    public function setPunch(bool $punch) : void{
        $this->isPunch = $punch;
    }
}

