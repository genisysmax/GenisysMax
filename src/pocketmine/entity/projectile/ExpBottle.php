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

use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\level\particle\SpellParticle;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

class ExpBottle extends Throwable {
	public const NETWORK_ID = self::XP_BOTTLE;

    public function onHit(ProjectileHitEvent $event) : void{
        $this->getLevel()->addParticle(new SpellParticle($this->add(0, 0.01), 46, 82, 153));
        $this->level->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_GLASS);

        $this->getLevel()->dropExperience($this, mt_rand(3, 11));
    }
}


