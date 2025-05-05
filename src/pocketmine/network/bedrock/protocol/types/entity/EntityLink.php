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

namespace pocketmine\network\bedrock\protocol\types\entity;

class EntityLink{

	public const TYPE_REMOVE = 0;
	public const TYPE_RIDER = 1;
	public const TYPE_PASSENGER = 2;

	/** @var int */
	public $fromActorUniqueId;
	/** @var int */
	public $toActorUniqueId;
	/** @var int */
	public $type;
	/** @var bool */
	public $immediate; //for dismounting on mount death
	/** @var bool */
	public $riderInitiated;

	public function __construct(?int $fromActorUniqueId = null, ?int $toActorUniqueId = null, ?int $type = null, bool $immediate = false, bool $riderInitiated = false){
		$this->fromActorUniqueId = $fromActorUniqueId;
		$this->toActorUniqueId = $toActorUniqueId;
		$this->type = $type;
		$this->immediate = $immediate;
		$this->riderInitiated = $riderInitiated;
	}
}


