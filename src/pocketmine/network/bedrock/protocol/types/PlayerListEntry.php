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

use pocketmine\network\bedrock\protocol\types\skin\Skin;
use pocketmine\utils\UUID;

class PlayerListEntry{

	/** @var UUID */
	public $uuid;
	/** @var int */
	public $actorUniqueId;
	/** @var string */
	public $username;
	/** @var string */
	public $xboxUserId;
	/** @var string */
	public $platformChatId = "";
	/** @var int */
	public $buildPlatform = -1;
	/** @var Skin */
	public $skin;
	/** @var bool */
	public $isTeacher = false;
	/** @var bool */
	public $isHost = false;
    /** @var bool */
    public $isSubClient = false;

	public static function createRemovalEntry(UUID $uuid) : PlayerListEntry{
		$entry = new PlayerListEntry();
		$entry->uuid = $uuid;

		return $entry;
	}

	public static function createAdditionEntry(UUID $uuid, int $actorUniqueId, string $username, Skin $skin, string $xboxUserId = "", string $platformChatId = "", int $buildPlatform = OS::UNKNOWN, bool $isTeacher = false, bool $isHost = false, bool $isSubClient = false) : PlayerListEntry{
		$entry = new PlayerListEntry();
		$entry->uuid = $uuid;
		$entry->actorUniqueId = $actorUniqueId;
		$entry->username = $username;
		$entry->xboxUserId = $xboxUserId;
		$entry->platformChatId = $platformChatId;
		$entry->buildPlatform = $buildPlatform;
		$entry->skin = $skin;
		$entry->isTeacher = $isTeacher;
		$entry->isHost = $isHost;
        $entry->isSubClient = $isSubClient;

		return $entry;
	}
}


