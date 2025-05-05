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

namespace pocketmine\network\bedrock\protocol\types\resourcepacks;

use pocketmine\network\mcpe\protocol\DataPacket;

class ResourcePackStackEntry{
	public function __construct(
		private string $packId,
		private string $version,
		private string $subPackName
	){}

	public function getPackId() : string{
		return $this->packId;
	}

	public function getVersion() : string{
		return $this->version;
	}

	public function getSubPackName() : string{
		return $this->subPackName;
	}

	public function write(DataPacket $out) : void{
		$out->putString($this->packId);
		$out->putString($this->version);
		$out->putString($this->subPackName);
	}

	public static function read(DataPacket $in) : self{
		$packId = $in->getString();
		$version = $in->getString();
		$subPackName = $in->getString();
		return new self($packId, $version, $subPackName);
	}
}


