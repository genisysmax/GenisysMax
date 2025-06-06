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

abstract class TieredTool extends Tool {

	/** @var int */
    protected $tier;

    public function __construct(int $id, int $meta, int $count, string $name, int $tier){
        parent::__construct($id, $meta, $count, $name);
        $this->tier = $tier;
    }

    public function getMaxDurability() : int{
        return self::getDurabilityFromTier($this->tier);
    }

    public function getTier() : int{
        return $this->tier;
    }

    public static function getDurabilityFromTier(int $tier) : int{
        static $levels = [
            self::TIER_GOLD => 33,
            self::TIER_WOODEN => 60,
            self::TIER_STONE => 132,
            self::TIER_IRON => 251,
            self::TIER_DIAMOND => 1562
        ];

        if(!isset($levels[$tier])){
            throw new \InvalidArgumentException("Unknown tier '$tier'");
        }

        return $levels[$tier];
    }

    protected static function getBaseDamageFromTier(int $tier) : int{
        static $levels = [
            self::TIER_WOODEN => 5,
            self::TIER_GOLD => 5,
            self::TIER_STONE => 6,
            self::TIER_IRON => 7,
            self::TIER_DIAMOND => 8
        ];

        if(!isset($levels[$tier])){
            throw new \InvalidArgumentException("Unknown tier '$tier'");
        }

        return $levels[$tier];
    }

    public static function getBaseMiningEfficiencyFromTier(int $tier) : float{
        static $levels = [
            self::TIER_WOODEN => 2,
            self::TIER_STONE => 4,
            self::TIER_IRON => 6,
            self::TIER_DIAMOND => 8,
            self::TIER_GOLD => 12
        ];

        if(!isset($levels[$tier])){
            throw new \InvalidArgumentException("Unknown tier '$tier'");
        }

        return $levels[$tier];
    }

    protected function getBaseMiningEfficiency() : float{
        return self::getBaseMiningEfficiencyFromTier($this->tier);
    }

    public function getFuelTime() : int{
        if($this->id === self::TIER_WOODEN){
            return 200;
        }

        return 0;
    }
}

