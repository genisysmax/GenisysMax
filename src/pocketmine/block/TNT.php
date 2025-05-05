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

namespace pocketmine\block;

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Arrow;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\FlintSteel;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\Random;
use function cos;
use function sin;
use const M_PI;

class TNT extends Solid{

	protected $id = self::TNT;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

    public function getName() : string{
        return "TNT";
    }

    public function getHardness() : float{
        return 0;
    }

    public function onActivate(Item $item, Player $player = null) : bool{
        if($item instanceof FlintSteel or $item->hasEnchantment(Enchantment::FIRE_ASPECT)){
            if($item instanceof Durable){
                $item->applyDamage(1);
            }
            $this->ignite();
            return true;
        }

        return false;
    }

    public function hasEntityCollision() : bool{
        return true;
    }

    public function onEntityCollide(Entity $entity) : void{
        if($entity instanceof Arrow and $entity->isOnFire()){
            $this->ignite();
        }
    }

    public function ignite(int $fuse = 80): void{
        $this->getLevel()->setBlock($this, Block::get(Block::AIR), true);

        $mot = (new Random())->nextSignedFloat() * M_PI * 2;
        $nbt = Entity::createBaseNBT($this->add(0.5, 0, 0.5), new Vector3(-sin($mot) * 0.02, 0.2, -cos($mot) * 0.02));
        $nbt->setShort("Fuse", $fuse);

        $tnt = Entity::createEntity("PrimedTNT", $this->getLevel(), $nbt);

        if($tnt !== null){
            $tnt->spawnToAll();
        }
    }

    public function getFlameEncouragement() : int{
        return 15;
    }

    public function getFlammability() : int{
        return 100;
    }

    public function onIncinerate() : void{
        $this->ignite();
    }
}

