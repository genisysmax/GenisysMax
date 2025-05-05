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

class BookEditPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::BOOK_EDIT_PACKET;

	public const TYPE_REPLACE_PAGE = 0;
	public const TYPE_ADD_PAGE = 1;
	public const TYPE_DELETE_PAGE = 2;
	public const TYPE_SWAP_PAGES = 3;
	public const TYPE_SIGN_BOOK = 4;

	/** @var int */
	public $type;
	/** @var int */
	public $inventorySlot;
	/** @var int */
	public $pageNumber;
	/** @var int */
	public $secondaryPageNumber;

	/** @var string */
	public $text;
	/** @var string */
	public $photoName;

	/** @var string */
	public $title;
	/** @var string */
	public $author;
	/** @var string */
	public $xuid;

	public function decodePayload(){
		$this->type = $this->getByte();
		$this->inventorySlot = $this->getByte();

		switch($this->type){
			case self::TYPE_REPLACE_PAGE:
			case self::TYPE_ADD_PAGE:
				$this->pageNumber = $this->getByte();
				$this->text = $this->getString();
				$this->photoName = $this->getString();
				break;
			case self::TYPE_DELETE_PAGE:
				$this->pageNumber = $this->getByte();
				break;
			case self::TYPE_SWAP_PAGES:
				$this->pageNumber = $this->getByte();
				$this->secondaryPageNumber = $this->getByte();
				break;
			case self::TYPE_SIGN_BOOK:
				$this->title = $this->getString();
				$this->author = $this->getString();
				$this->xuid = $this->getString();
				break;
			default:
				throw new \UnexpectedValueException("Unknown book edit type $this->type!");
		}
	}

	public function encodePayload(){
		$this->putByte($this->type);
		$this->putByte($this->inventorySlot);

		switch($this->type){
			case self::TYPE_REPLACE_PAGE:
			case self::TYPE_ADD_PAGE:
				$this->putByte($this->pageNumber);
				$this->putString($this->text);
				$this->putString($this->photoName);
				break;
			case self::TYPE_DELETE_PAGE:
				$this->putByte($this->pageNumber);
				break;
			case self::TYPE_SWAP_PAGES:
				$this->putByte($this->pageNumber);
				$this->putByte($this->secondaryPageNumber);
				break;
			case self::TYPE_SIGN_BOOK:
				$this->putString($this->title);
				$this->putString($this->author);
				$this->putString($this->xuid);
				break;
			default:
				throw new \InvalidArgumentException("Unknown book edit type $this->type!");
		}
	}

	public function mustBeDecoded() : bool{
		return false;
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleBookEdit($this);
	}
}


