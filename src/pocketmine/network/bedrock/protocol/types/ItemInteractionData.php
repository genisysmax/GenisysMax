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

namespace pocketmine\network\bedrock\protocol\types;

use pocketmine\network\bedrock\protocol\DataPacket;
use pocketmine\network\bedrock\protocol\types\inventory\LegacySetItemSlot;

final class ItemInteractionData{
	public function __construct(
        public LegacySetItemSlot $legacySetItemSlot
	){}

	public static function read(DataPacket $in) : self{
        $legacySetItemSlot = $in->getLegacySetItemSlot();
		return new ItemInteractionData($legacySetItemSlot);
	}

	public function write(DataPacket $out) : void{
		$out->putLegacySetItemSlot($this->legacySetItemSlot);
	}
}

