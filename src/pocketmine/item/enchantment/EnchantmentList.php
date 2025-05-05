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


use SplFixedArray;

class EnchantmentList{

    /** @var SplFixedArray|EnchantmentEntry[] */
    private $enchantments;

    public function __construct(int $size){
        $this->enchantments = new SplFixedArray($size);
    }

    /**
     * @param int              $slot
     * @param EnchantmentEntry $entry
     */
    public function setSlot(int $slot, EnchantmentEntry $entry) : void{
        $this->enchantments[$slot] = $entry;
    }

    /**
     * @param int $slot
     *
     * @return EnchantmentEntry
     */
    public function getSlot(int $slot) : EnchantmentEntry{
        return $this->enchantments[$slot];
    }

    public function getSize() : int{
        return $this->enchantments->getSize();
    }
}

