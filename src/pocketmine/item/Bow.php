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

namespace pocketmine\item;

use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\projectile\Arrow as ArrowEntity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use function cos;
use function min;
use function sin;
use const M_PI;

class Bow extends Tool{
	public function __construct($meta = 0, $count = 1){
		parent::__construct(self::BOW, $meta, $count, "Bow");
	}

    public function getFuelTime() : int{
        return 200;
    }

	public function getMaxDurability() : int{
		return 385;
	}

	public function onReleaseUsing(Player $player) : void{
		$arrow = null;
		if($player->getOffHandInventory()->getItemInOffhand()->getId() === Item::ARROW){
			$arrow = $player->getOffHandInventory()->getItemInOffhand();
		}else{
			foreach($player->getInventory()->getContents() as $item){
				if($item->getId() === Item::ARROW){
					$arrow = $item;
					break;
				}
			}
		}
		if($arrow === null){
			if($player->isSurvival()){
				$player->getInventory()->sendContents($player);
				$player->getOffHandInventory()->sendContents($player);
				return;
			}else{
				$arrow = Item::get(Item::ARROW, 0, 1);
			}
		}

		$motion = new Vector3(
			-sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI),
			-sin($player->pitch / 180 * M_PI),
			cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)
		);
		$yaw = ($player->yaw > 180 ? 360 : 0) - $player->yaw;
		$pitch = -$player->pitch;
		$nbt = EntityDataHelper::createBaseNBT($player->add(0, $player->getEyeHeight(), 0), $motion, $yaw, $pitch)
			->setInt("Fire", $player->isOnFire() ? 45 * 60 : 0)
			->setShort("Potion", $arrow->getDamage() - 1);

		$diff = $player->getItemUseDuration();
		$p = $diff / 20;
		$baseForce = min((($p ** 2) + $p * 2) / 3, 1);

		$entity = new ArrowEntity($player->level, $nbt, $player, $baseForce >= 1);
		if($this->hasEnchantment(Enchantment::FLAME)){
			$entity->setOnFire(intdiv($entity->getFireTicks(), 20) + 100);
		}

		$ev = new EntityShootBowEvent($player, $this, $entity, $baseForce * 3.0);

		if($baseForce < 0.1 or $diff < 5){
			$ev->setCancelled();
		}

		$ev->call();

		if($ev->isCancelled()){
			$ev->getProjectile()->kill();
			$player->getInventory()->sendContents($player);
			$player->getOffHandInventory()->sendContents($player);
		}else{
			$ev->getProjectile()->setBow(clone $this);
			$ev->getProjectile()->setMotion($ev->getProjectile()->getMotion()->multiply($ev->getForce()));
			if($player->isSurvival()){
				if(!$this->hasEnchantment(Enchantment::INFINITY)){
					$offhand = $player->getOffHandInventory()->getItemInOffhand();
					if($offhand->getId() === Item::ARROW){ //Priority
						--$offhand->count;
						if($offhand->count === 0){
							$offhand = Item::get(Item::AIR, 0, 1);
						}
						$player->getOffHandInventory()->setItemInOffhand($offhand);
					}else{
						$player->getInventory()->removeItem(Item::get(Item::ARROW, $arrow->getDamage(), 1));
					}
				}
				$this->applyDamage(1);
				$player->getInventory()->setItemInHand($this);
			}
			if($ev->getProjectile() instanceof Projectile){
				$projectileEv = new ProjectileLaunchEvent($ev->getProjectile());
				$projectileEv->call();
				if($projectileEv->isCancelled()){
					$ev->getProjectile()->kill();
				}else{
					$ev->getProjectile()->spawnToAll();
					$player->level->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_BOW);
				}
			}else{
				$ev->getProjectile()->spawnToAll();
			}
		}
	}
}

