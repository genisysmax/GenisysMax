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

class GoldenAppleEnchanted extends GoldenApple{

	public function __construct($meta = 0, $count = 1){
		Food::__construct(self::ENCHANTED_GOLDEN_APPLE, $meta, $count, "Enchanted Golden Apple"); //skip parent constructor
	}

	public function getAdditionalEffects() : array{
		return [
            new EffectInstance(Effect::getEffect(Effect::REGENERATION), 600, 4),
            new EffectInstance(Effect::getEffect(Effect::ABSORPTION), 600, 3),
            new EffectInstance(Effect::getEffect(Effect::DAMAGE_RESISTANCE), 6000),
            new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 6000)
		];
	}
}


