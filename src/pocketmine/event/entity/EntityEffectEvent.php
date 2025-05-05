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
use pocketmine\event\Cancellable;

class EntityEffectEvent extends EntityEvent implements Cancellable{
    /** @var EffectInstance */
    private $effect;

    public function __construct(Entity $entity, EffectInstance $effect){
        $this->entity = $entity;
        $this->effect = $effect;
    }

    public function getEffect() : EffectInstance{
        return $this->effect;
    }
}

