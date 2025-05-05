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

use pocketmine\utils\UUID;

class ScorePacketEntry{
	public const TYPE_PLAYER = 1;
	public const TYPE_ACTOR = 2;
	public const TYPE_FAKE_PLAYER = 3;

	/** @var int */
	public $scoreboardId;
	/** @var string */
	public $objectiveName;
	/** @var int */
	public $score;

	/** @var int */
	public $type;

	/** @var int|null (if type entity or player) */
	public $actorUniqueId;
	/** @var string|null (if type fake player) */
	public $customName;

    /** @var UUID */
    public $uuid = ""; /* 1.6.0 :( */
}


