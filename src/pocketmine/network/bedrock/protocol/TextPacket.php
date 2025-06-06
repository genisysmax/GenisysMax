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
use function count;

class TextPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::TEXT_PACKET;

	public const COUNT_LIMIT = 10;

	public const TYPE_RAW = 0;
	public const TYPE_CHAT = 1;
	public const TYPE_TRANSLATION = 2;
	public const TYPE_POPUP = 3;
	public const TYPE_JUKEBOX_POPUP = 4;
	public const TYPE_TIP = 5;
	public const TYPE_SYSTEM = 6;
	public const TYPE_WHISPER = 7;
	public const TYPE_ANNOUNCEMENT = 8;
	public const TYPE_OBJECT = 9;
	public const TYPE_OBJECT_WHISPER = 10;

	/** @var int */
	public $type;
	/** @var bool */
	public $needsTranslation = false;
	/** @var string */
	public $sourceName;
	/** @var string */
	public $message;
	/** @var string[] */
	public $parameters = [];
	/** @var string */
	public $xboxUserId = "";
	/** @var string */
	public $platformChatId = "";
    public string $filteredMessage = "";

	public function decodePayload()
    {
        $this->type = $this->getByte();
        $this->needsTranslation = $this->getBool();
        switch ($this->type) {
            case self::TYPE_CHAT:
            case self::TYPE_WHISPER:
                /** @noinspection PhpMissingBreakStatementInspection */
            case self::TYPE_ANNOUNCEMENT:
                $this->sourceName = $this->getString();
            case self::TYPE_RAW:
            case self::TYPE_TIP:
            case self::TYPE_SYSTEM:
            case self::TYPE_OBJECT:
            case self::TYPE_OBJECT_WHISPER:
                $this->message = $this->getString();
                break;

            case self::TYPE_TRANSLATION:
            case self::TYPE_POPUP:
            case self::TYPE_JUKEBOX_POPUP:
                $this->message = $this->getString();
                $count = $this->getUnsignedVarInt();
                if ($count > self::COUNT_LIMIT) {
                    break;
                }
                for ($i = 0; $i < $count; ++$i) {
                    $this->parameters[] = $this->getString();
                }
                break;
        }

        $this->xboxUserId = $this->getString();
        $this->platformChatId = $this->getString();
        $this->filteredMessage = $this->getString();
    }

	public function encodePayload()
    {
        $this->putByte($this->type);
        $this->putBool($this->needsTranslation);
        switch ($this->type) {
            case self::TYPE_CHAT:
            case self::TYPE_WHISPER:
                /** @noinspection PhpMissingBreakStatementInspection */
            case self::TYPE_ANNOUNCEMENT:
                $this->putString($this->sourceName);
            case self::TYPE_RAW:
            case self::TYPE_TIP:
            case self::TYPE_SYSTEM:
            case self::TYPE_OBJECT:
            case self::TYPE_OBJECT_WHISPER:
                $this->putString($this->message);
                break;

            case self::TYPE_TRANSLATION:
            case self::TYPE_POPUP:
            case self::TYPE_JUKEBOX_POPUP:
                $this->putString($this->message);
                $this->putUnsignedVarInt(count($this->parameters));
                foreach ($this->parameters as $p) {
                    $this->putString($p);
                }
                break;
        }

        $this->putString($this->xboxUserId);
        $this->putString($this->platformChatId);
        $this->putString($this->filteredMessage);
    }

	public function handle(NetworkSession $session) : bool{
		return $session->handleText($this);
	}
}


