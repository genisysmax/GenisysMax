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

use pocketmine\network\bedrock\protocol\types\command\CommandOutputMessage;
use pocketmine\network\NetworkSession;
use function count;

class CommandOutputPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::COMMAND_OUTPUT_PACKET;

	/** @var \pocketmine\network\bedrock\protocol\types\command\CommandOriginData */
	public $originData;
	/** @var int */
	public $outputType;
	/** @var int */
	public $successCount;
	/** @var \pocketmine\network\bedrock\protocol\types\command\CommandOutputMessage[] */
	public $messages = [];
	/** @var string */
	public $unknownString;

	public function decodePayload(){
		$this->originData = $this->getCommandOriginData();
		$this->outputType = $this->getByte();
		$this->successCount = $this->getUnsignedVarInt();

		for($i = 0, $size = $this->getUnsignedVarInt(); $i < $size; ++$i){
			$this->messages[] = $this->getCommandMessage();
		}

		if($this->outputType === 4){
			$this->unknownString = $this->getString();
		}
	}

	protected function getCommandMessage() : CommandOutputMessage{
		$message = new CommandOutputMessage();

		$message->isInternal = $this->getBool();
		$message->messageId = $this->getString();

		for($i = 0, $size = $this->getUnsignedVarInt(); $i < $size; ++$i){
			$message->parameters[] = $this->getString();
		}

		return $message;
	}

	public function encodePayload(){
		$this->putCommandOriginData($this->originData);
		$this->putByte($this->outputType);
		$this->putUnsignedVarInt($this->successCount);

		$this->putUnsignedVarInt(count($this->messages));
		foreach($this->messages as $message){
			$this->putCommandMessage($message);
		}

		if($this->outputType === 4){
			$this->putString($this->unknownString);
		}
	}

	protected function putCommandMessage(CommandOutputMessage $message) : void{
		$this->putBool($message->isInternal);
		$this->putString($message->messageId);

		$this->putUnsignedVarInt(count($message->parameters));
		foreach($message->parameters as $parameter){
			$this->putString($parameter);
		}
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCommandOutput($this);
	}
}


