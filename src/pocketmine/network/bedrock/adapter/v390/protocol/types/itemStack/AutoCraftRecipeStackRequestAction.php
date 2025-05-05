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

namespace pocketmine\network\bedrock\adapter\v390\protocol\types\itemStack;

use pocketmine\network\bedrock\protocol\DataPacket;

class AutoCraftRecipeStackRequestAction extends \pocketmine\network\bedrock\adapter\v545\protocol\types\itemStack\AutoCraftRecipeStackRequestAction{

	public function decode(DataPacket $stream) : void{
		$this->recipeNetworkId = $stream->getUnsignedVarInt();
	}

	public function encode(DataPacket $stream) : void{
		$stream->putUnsignedVarInt($this->recipeNetworkId);
	}
}

