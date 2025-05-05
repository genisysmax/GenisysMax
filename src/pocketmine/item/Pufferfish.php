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

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;

class Pufferfish extends Food{
	public function __construct(int $meta = 0){
		parent::__construct(self::PUFFERFISH, $meta, 1, "Pufferfish");
	}

	public function getFoodRestore() : int{
		return 1;
	}

	public function getSaturationRestore() : float{
		return 0.2;
	}

	public function getAdditionalEffects() : array{
		return [
            new EffectInstance(Effect::getEffect(Effect::HUNGER), 300, 2),
            new EffectInstance(Effect::getEffect(Effect::NAUSEA), 300, 1),
            new EffectInstance(Effect::getEffect(Effect::POISON), 1200, 3)
		];
	}
}


