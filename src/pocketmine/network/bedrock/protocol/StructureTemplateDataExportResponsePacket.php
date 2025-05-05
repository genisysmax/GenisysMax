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

use pocketmine\network\NetworkSession;

class StructureTemplateDataExportResponsePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::STRUCTURE_TEMPLATE_DATA_EXPORT_RESPONSE_PACKET;

	public string $string;
	public bool $response;
	public string $namedtag;

	public function decodePayload(){
		$this->string = $this->getString();
		$this->response = $this->getBool();
		if($this->response){
			$this->namedtag = $this->getRemaining();
		}
	}

	public function encodePayload(){
		$this->putString($this->string);
		$this->putBool($this->response);
		if($this->response){
			$this->put($this->namedtag);
		}
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleStructureTemplateDataExportResponse($this);
	}
}


