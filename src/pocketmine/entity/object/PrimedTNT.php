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
use pocketmine\entity\Explosive;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\level\Explosion;
use pocketmine\network\mcpe\protocol\LevelEventPacket;

class PrimedTNT extends Entity implements Explosive{
	public const NETWORK_ID = self::TNT;

	public float $width = 0.98;
	public float $height = 0.98;

	protected float $baseOffset = 0.49;

	public float $gravity = 0.04;
	public float $drag = 0.02;

	protected $fuse;

	public bool $canCollide = false;


	public function attack(EntityDamageEvent $source): void
    {
		if($source->getCause() === EntityDamageEvent::CAUSE_VOID){
			parent::attack($source);
		}
	}

	protected function initEntity() : void{
		parent::initEntity();

		$this->fuse = $this->namedtag->getShort("Fuse", 80);

        $this->setGenericFlag(self::DATA_FLAG_IGNITED, true);
        $this->propertyManager->setInt(self::DATA_FUSE_LENGTH, $this->fuse);

		$this->level->broadcastLevelEvent($this, LevelEventPacket::EVENT_SOUND_IGNITE);
	}


	public function canCollideWith(Entity $entity): bool
    {
		return false;
	}

	public function saveNBT(): void
    {
		parent::saveNBT();
		$this->namedtag->setShort("Fuse", $this->fuse);
	}

	public function entityBaseTick($tickDiff = 1): bool
    {
		if($this->closed){
			return false;
		}

		$hasUpdate = parent::entityBaseTick($tickDiff);

		if($this->fuse % 5 === 0){ //don't spam it every tick, it's not necessary
            $this->propertyManager->setInt(self::DATA_FUSE_LENGTH, $this->fuse);
		}

		if(!$this->isFlaggedForDespawn()){
			$this->fuse -= $tickDiff;

			if($this->fuse <= 0){
				$this->flagForDespawn();
				$this->explode();
			}
		}

		return $hasUpdate or $this->fuse >= 0;
	}

	public function explode(){
		$ev = new ExplosionPrimeEvent($this, 4);
		$ev->call();

		if(!$ev->isCancelled()){
			$explosion = new Explosion($this, $ev->getForce(), $this);
			if($ev->isBlockBreaking()){
				$explosion->explodeA();
			}
			$explosion->explodeB();
		}
	}
}


