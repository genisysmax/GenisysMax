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

use pocketmine\network\bedrock\protocol\types\ScorePacketEntry;
use pocketmine\network\NetworkSession;
use function count;

class SetScorePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::SET_SCORE_PACKET;

	public const TYPE_CHANGE = 0;
	public const TYPE_REMOVE = 1;

	/** @var int */
	public $type;
	/** @var ScorePacketEntry[] */
	public $entries = [];

	public function decodePayload(){
		$this->type = $this->getByte();
		for($i = 0, $i2 = $this->getUnsignedVarInt(); $i < $i2; ++$i){
			$entry = new ScorePacketEntry();
			$entry->scoreboardId = $this->getVarLong();
			$entry->objectiveName = $this->getString();
			$entry->score = $this->getLInt();
			if($this->type !== self::TYPE_REMOVE){
				$entry->type = $this->getByte();
				switch($entry->type){
					case ScorePacketEntry::TYPE_PLAYER:
					case ScorePacketEntry::TYPE_ACTOR:
						$entry->actorUniqueId = $this->getActorUniqueId();
						break;
					case ScorePacketEntry::TYPE_FAKE_PLAYER:
						$entry->customName = $this->getString();
						break;
					default:
						throw new \UnexpectedValueException("Unknown entry type $entry->type");
				}
			}
			$this->entries[] = $entry;
		}
	}

	public function encodePayload(){
		$this->putByte($this->type);
		$this->putUnsignedVarInt(count($this->entries));
		foreach($this->entries as $entry){
			$this->putVarLong($entry->scoreboardId);
			$this->putString($entry->objectiveName);
			$this->putLInt($entry->score);
			if($this->type !== self::TYPE_REMOVE){
				$this->putByte($entry->type);
				switch($entry->type){
					case ScorePacketEntry::TYPE_PLAYER:
					case ScorePacketEntry::TYPE_ACTOR:
						$this->putActorUniqueId($entry->actorUniqueId);
						break;
					case ScorePacketEntry::TYPE_FAKE_PLAYER:
						$this->putString($entry->customName);
						break;
					default:
						throw new \InvalidArgumentException("Unknown entry type $entry->type");
				}
			}
		}
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleSetScore($this);
	}
}


