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

namespace pocketmine\network\bedrock\protocol;

use pocketmine\network\bedrock\protocol\types\StructureSettings;
use pocketmine\network\NetworkSession;

class StructureTemplateDataExportRequestPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::STRUCTURE_TEMPLATE_DATA_EXPORT_REQUEST_PACKET;

	public string $string;
	public int $x;
	public int $y;
	public int $z;
	public StructureSettings $structureSettings;
	public int $byte;

	public function decodePayload(){
		$this->string = $this->getString();
		$this->getBlockPosition($this->x, $this->y, $this->z);
		$this->structureSettings = $this->getStructureSettings();
		$this->byte = $this->getByte();
	}

	public function encodePayload(){
		$this->putString($this->string);
		$this->putBlockPosition($this->x, $this->y, $this->z);
		$this->putStructureSettings($this->structureSettings);
		$this->putByte($this->byte);
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleStructureTemplateDataExportRequest($this);
	}
}


