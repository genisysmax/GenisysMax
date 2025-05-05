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

namespace pocketmine\network\bedrock\adapter\v545\protocol;

use pocketmine\item\Item;
use pocketmine\network\bedrock\adapter\v557\palette\ItemPalette as ItemPalette557;

trait PacketTrait{

    public function putRecipeIngredient(Item $item) : void{
        if($item->isNull()){
            $this->putVarInt(0);
        }else{
            if($item->hasAnyDamageValue()){
                [$netId, ] = ItemPalette557::getRuntimeFromLegacyId($item->getId(), 0);
                $netData = 0x7fff;
            }else{
                [$netId, $netData] = ItemPalette557::getRuntimeFromLegacyId($item->getId(), $item->getDamage());
            }
            $this->putVarInt($netId);
            $this->putVarInt($netData);
            $this->putVarInt($item->getCount());
        }
    }

    public function getRecipeIngredient() : Item{
        $netId = $this->getVarInt();
        if($netId === 0){
            return Item::air();
        }

        $netData = $this->getVarInt();
        [$id, $meta] = ItemPalette557::getLegacyFromRuntimeId($netId, $netData);
        $cnt = $this->getVarInt();
        return Item::get($id, $meta, $cnt);
    }
}

