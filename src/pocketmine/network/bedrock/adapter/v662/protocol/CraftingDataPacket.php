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

namespace pocketmine\network\bedrock\adapter\v662\protocol;

use pocketmine\inventory\ShapedRecipe;

class CraftingDataPacket extends \pocketmine\network\bedrock\adapter\v671\protocol\CraftingDataPacket {
	use PacketTrait;

    protected function writeShapedRecipe(ShapedRecipe $recipe, int $networkId) : void{
        $this->putString($recipe->getId()->toString());
        $this->putVarInt($recipe->getWidth());
        $this->putVarInt($recipe->getHeight());

        for($z = 0; $z < $recipe->getHeight(); ++$z){
            for($x = 0; $x < $recipe->getWidth(); ++$x){
                $this->putRecipeIngredient($recipe->getIngredient($x, $z));
            }
        }

        $this->putUnsignedVarInt(1);
        $this->putItemStackWithoutStackId($recipe->getResult());

        $this->putUUID($recipe->getId());
        $this->putString(self::CRAFTING_TAG_CRAFTING_TABLE);
        $this->putVarInt(50); //priority (???)
        $this->putVarInt($networkId);
    }

}

