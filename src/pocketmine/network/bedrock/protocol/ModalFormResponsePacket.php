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

#include <rules/DataPacket.h>

use pocketmine\network\NetworkSession;

class ModalFormResponsePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::MODAL_FORM_RESPONSE_PACKET;

	public const CANCEL_REASON_CLOSED = 0;
	/** Sent if a form is sent when the player is on a loading screen */
	public const CANCEL_REASON_USER_BUSY = 1;

	/** @var int */
	public $formId;
	/** @var string|null */
	public $formData; //json
	/** @var int|null */
	public $cancelReason;

	public function decodePayload(){
		$this->formId = $this->getUnsignedVarInt();
		$this->formData = $this->getBool() ? $this->getString() : null;
		$this->cancelReason = $this->getBool() ? $this->getByte() : null;
	}

	public function encodePayload(){
		$this->putUnsignedVarInt($this->formId);
		if($this->formData !== null){
			$this->putBool(true);
			$this->putString($this->formData);
		}else{
			$this->putBool(false);
		}
		if($this->cancelReason !== null){
			$this->putBool(true);
			$this->putByteRotation($this->cancelReason);
		}else{
			$this->putBool(false);
		}
	}

	public function mustBeDecoded() : bool{
		return true;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleModalFormResponse($this);
	}
}


