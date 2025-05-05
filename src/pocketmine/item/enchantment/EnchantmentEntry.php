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

namespace pocketmine\item\enchantment;


class EnchantmentEntry{

    /** @var Enchantment[] */
    private $enchantments;
    /** @var int */
    private $cost;
    /** @var string */
    private $randomName;

    /**
     * @param Enchantment[] $enchantments
     * @param int           $cost
     * @param string        $randomName
     */
    public function __construct(array $enchantments, int $cost, string $randomName){
        $this->enchantments = $enchantments;
        $this->cost = $cost;
        $this->randomName = $randomName;
    }

    public function getEnchantments() : array{
        return $this->enchantments;
    }

    public function getCost() : int{
        return $this->cost;
    }

    public function getRandomName() : string{
        return $this->randomName;
    }

}

