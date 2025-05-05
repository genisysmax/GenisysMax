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

namespace pocketmine\entity\weather;

use pocketmine\block\Liquid;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item as ItemItem;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\ExplodePacket;
use pocketmine\Player;

class Lightning extends Entity
{
    const NETWORK_ID = self::LIGHTNING_BOLT;

    public float $width = 0.3;
    public float $height = 1.8;

    /** @var int */
    protected $age = 0;

    public function initEntity(): void
    {
        parent::initEntity();
        $this->setMaxHealth(2);
        $this->setHealth(2);
    }

    /**
     * @param $tickDiff
     *
     * @return bool
     */
    public function entityBaseTick($tickDiff = 1): bool
    {
        if ($this->closed) {
            return false;
        }

        $hasUpdate = parent::entityBaseTick($tickDiff);

        $this->age += $tickDiff;
        if ($this->age > 20) {
            $this->flagForDespawn();
            return true;
        }

        return $hasUpdate;
    }

    /**
     * @param Player $player
     */
    public function sendSpawnPacket(Player $player): void
    {
        $pk = new AddEntityPacket();
        $pk->entityRuntimeId = $this->getId();
        $pk->type = self::NETWORK_ID;
        $pk->x = $this->x;
        $pk->y = $this->y;
        $pk->z = $this->z;
        $pk->speedX = $this->motionX;
        $pk->speedY = $this->motionY;
        $pk->speedZ = $this->motionZ;
        $pk->yaw = $this->yaw;
        $pk->pitch = $this->pitch;
        $pk->metadata = $this->propertyManager->getAll();
        $player->sendDataPacket($pk);

        $pk = new ExplodePacket();
        $pk->x = $this->x;
        $pk->y = $this->y;
        $pk->z = $this->z;
        $pk->radius = 10;
        $pk->records = [];
        $player->sendDataPacket($pk);
    }

    public function spawnToAll(): void
    {
        parent::spawnToAll();

        if ($this->server->getAdvancedProperty("level.lightning-fire", false)) {
            $fire = ItemItem::get(ItemItem::FIRE)->getBlock();
            $oldBlock = $this->getLevel()->getBlock($this);
            if ($oldBlock instanceof Liquid) {
                //TODO: ???
            } elseif ($oldBlock->isSolid()) {
                $v3 = new Vector3($this->x, $this->y + 1, $this->z);
            } else {
                $v3 = new Vector3($this->x, $this->y, $this->z);
            }
            if (isset($v3)) $this->getLevel()->setBlock($v3, $fire);

            foreach ($this->level->getNearbyEntities($this->boundingBox->expandedCopy(4, 3, 4), $this) as $entity) {
                if ($entity instanceof Player) {
                    $damage = mt_rand(8, 20);
                    $ev = new EntityDamageByEntityEvent($this, $entity, EntityDamageByEntityEvent::CAUSE_LIGHTNING, $damage);
                    if ($entity->attack($ev) === true) {
                        $armorPoints = 0;
                        foreach ($entity->getArmorInventory()->getContents() as $i) {
                            $armorPoints += $i->getArmorPoints();
                        }
                        $ev->setModifier(-$ev->getFinalDamage() * $armorPoints * 0.04, EntityDamageEvent::MODIFIER_ARMOR);
                    }
                    $entity->setOnFire(mt_rand(3, 8));
                }
            }
        }
    }
}

