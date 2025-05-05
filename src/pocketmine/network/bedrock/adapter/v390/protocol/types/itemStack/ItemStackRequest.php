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

use pocketmine\network\bedrock\protocol\types\itemStack\StackRequestAction;

class ItemStackRequest extends \pocketmine\network\bedrock\adapter\v545\protocol\types\itemStack\ItemStackRequest{

	protected function getActionById(int $actionType) : ?StackRequestAction{
		if($actionType === self::ACTION_CRAFT_RECIPE_AUTO){
			return new AutoCraftRecipeStackRequestAction();
		}
		return parent::getActionById($actionType);
	}
}

