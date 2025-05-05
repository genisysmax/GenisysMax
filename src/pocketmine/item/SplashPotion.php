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

use pocketmine\nbt\tag\CompoundTag;

class SplashPotion extends ProjectileItem{

	public function __construct($meta = 0, $count = 1){
		parent::__construct(self::SPLASH_POTION, $meta, $count, $this->getNameByMeta($meta));
	}

	public function getNameByMeta(int $meta) : string{
		return "Splash " . Potion::getNameByMeta($meta);
	}

	public function getMaxStackSize() : int{
		return 1;
	}

	public function getProjectileEntityType() : string{
		return "SplashPotion";
	}

	public function getThrowForce() : float{
		return 1.1;
	}

    public function getMaxDurability(): int
    {
        return 43;
    }

	protected function addExtraTags(CompoundTag $tag) : void{
		$tag->setShort("PotionId", $this->meta);
	}
}

