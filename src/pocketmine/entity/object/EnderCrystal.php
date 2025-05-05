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

namespace pocketmine\entity\object;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\level\Explosion;
use pocketmine\level\Position;

class EnderCrystal extends Entity{

	public const NETWORK_ID = self::ENDER_CRYSTAL;

	public float $height = 0.98;
	public float $width = 0.98;
	public float $gravity = 0.0;
	public float $drag = 0.0;

    public function updateMovement(bool $teleport = false) : void{
        // NOOP
    }

    public function isFireProof(): bool{
        return true;
    }

    public function attack(EntityDamageEvent $source): void
    {
		parent::attack($source);
        if(!$this->isFlaggedForDespawn() and !$source->isCancelled() and $source->getCause() !== EntityDamageEvent::CAUSE_FIRE and $source->getCause() !== EntityDamageEvent::CAUSE_FIRE_TICK) {
            $this->flagForDespawn();
            $ev = new ExplosionPrimeEvent($this, 6); //TODO: dropitem зависит от того, в креативе ли игрок
            $ev->call();
            if (!$ev->isCancelled()) {
                $explosion = new Explosion(Position::fromObject($this->add(0, $this->height / 2, 0), $this->level), $ev->getForce(), $this);

                if($ev->isBlockBreaking()){
                    $explosion->explodeA();
                }
                $explosion->explodeB();
            }
        }
	}

    public function setShowBase(bool $value) : void{
        $this->setGenericFlag(self::DATA_FLAG_SHOWBASE, $value);
    }

    public function showBase() : bool{
        return $this->getGenericFlag(self::DATA_FLAG_SHOWBASE);
    }
}

