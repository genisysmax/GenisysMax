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

namespace pocketmine\tile;

use pocketmine\inventory\Inventory;

interface Container{
    public const TAG_ITEMS = "Items";
    public const TAG_LOCK = "Lock";

    /**
     * @return Inventory|null
     */
    public function getInventory();

    /**
     * @return Inventory|null
     */
    public function getRealInventory();

    /**
     * Returns whether this container can be opened by an item with the given custom name.
     */
    public function canOpenWith(string $key) : bool;
}


