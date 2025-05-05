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

use pocketmine\entity\feature\Interactive;
use pocketmine\entity\Rideable;
use pocketmine\math\Vector3;
use pocketmine\Player;

class MinecartEmpty extends MinecartAbstract implements Interactive, Rideable
{

    public const NETWORK_ID = self::MINECART;

    public function getSeatPosition(): Vector3 { return new Vector3(0, 1, 0); }

    public function getInteractButtonText(Player $player): ?string { return "action.interact.ride.minecart"; }

    public function isRideable() :bool{
        return true;
    }

    public function getName(): string
	{
		return "Minecart";
	}

    public function getType(): int
	{
		return self::TYPE_NORMAL;
	}

}

