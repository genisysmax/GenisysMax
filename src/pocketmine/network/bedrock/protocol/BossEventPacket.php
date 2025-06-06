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

class BossEventPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::BOSS_EVENT_PACKET;

	/* S2C: Shows the boss-bar to the player. */
	public const TYPE_SHOW = 0;
	/* C2S: Registers a player to a boss fight. */
	public const TYPE_REGISTER_PLAYER = 1;
	/* S2C: Removes the boss-bar from the client. */
	public const TYPE_HIDE = 2;
	/* C2S: Unregisters a player from a boss fight. */
	public const TYPE_UNREGISTER_PLAYER = 3;
	/* S2C: Appears not to be implemented. Currently bar percentage only appears to change in response to the target entity's health. */
	public const TYPE_HEALTH_PERCENT = 4;
	/* S2C: Also appears to not be implemented. Title client-side sticks as the target entity's nametag, or their entity type name if not set. */
	public const TYPE_TITLE = 5;
	/* S2C: Not sure on this. Includes color and overlay fields, plus an unknown short. TODO: check this */
	public const TYPE_UNKNOWN_6 = 6;
	/* S2C: Not implemented :( Intended to alter bar appearance, but these currently produce no effect on client-side whatsoever. */
	public const TYPE_TEXTURE = 7;
	/* C2S: Client asking the server to resend all boss data. */
	public const TYPE_QUERY = 8;

	/** @var int */
	public $bossUniqueId;
	/** @var int */
	public $eventType;

	/** @var int (long) */
	public $playerUniqueId;
	/** @var float */
	public $healthPercent;
	/** @var string */
	public $title;
	/** @var int */
	public $unknownShort;
	/** @var int */
	public $color;
	/** @var int */
	public $overlay;

	public function decodePayload(){
		$this->bossUniqueId = $this->getActorUniqueId();
		$this->eventType = $this->getUnsignedVarInt();
		switch($this->eventType){
			case self::TYPE_REGISTER_PLAYER:
			case self::TYPE_UNREGISTER_PLAYER:
			case self::TYPE_QUERY:
				$this->playerUniqueId = $this->getActorUniqueId();
				break;
			/** @noinspection PhpMissingBreakStatementInspection */
			case self::TYPE_SHOW:
				$this->title = $this->getString();
				$this->healthPercent = $this->getLFloat();
			/** @noinspection PhpMissingBreakStatementInspection */
			case self::TYPE_UNKNOWN_6:
				$this->unknownShort = $this->getLShort();
			case self::TYPE_TEXTURE:
				$this->color = $this->getUnsignedVarInt();
				$this->overlay = $this->getUnsignedVarInt();
				break;
			case self::TYPE_HEALTH_PERCENT:
				$this->healthPercent = $this->getLFloat();
				break;
			case self::TYPE_TITLE:
				$this->title = $this->getString();
				break;
			default:
				break;
		}
	}

	public function encodePayload(){
		$this->putActorUniqueId($this->bossUniqueId);
		$this->putUnsignedVarInt($this->eventType);
		switch($this->eventType){
			case self::TYPE_REGISTER_PLAYER:
			case self::TYPE_UNREGISTER_PLAYER:
			case self::TYPE_QUERY:
				$this->putActorUniqueId($this->playerUniqueId);
				break;
			/** @noinspection PhpMissingBreakStatementInspection */
			case self::TYPE_SHOW:
				$this->putString($this->title);
				$this->putLFloat($this->healthPercent);
			/** @noinspection PhpMissingBreakStatementInspection */
			case self::TYPE_UNKNOWN_6:
				$this->putLShort($this->unknownShort);
			case self::TYPE_TEXTURE:
				$this->putUnsignedVarInt($this->color);
				$this->putUnsignedVarInt($this->overlay);
				break;
			case self::TYPE_HEALTH_PERCENT:
				$this->putLFloat($this->healthPercent);
				break;
			case self::TYPE_TITLE:
				$this->putString($this->title);
				break;
			default:
				break;
		}
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleBossEvent($this);
	}
}


