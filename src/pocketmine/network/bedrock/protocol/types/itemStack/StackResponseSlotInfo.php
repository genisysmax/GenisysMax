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

namespace pocketmine\network\bedrock\protocol\types\itemStack;

class StackResponseSlotInfo{

	/** @var int */
	public $slot;
	/** @var int */
	public $hotbarSlot;
	/** @var int */
	public $count;
	/** @var int */
	public $stackNetworkId;
	/** @var string */
	public $customName;
	/** @var int */
	public $durabilityCorrection;

	public function __construct(int $slot = -1, int $hotbarSlot = -1, int $count = -1, int $stackNetworkId = -1, string $customName = "", int $durabilityCorrection = 0){
		$this->slot = $slot;
		$this->hotbarSlot = $hotbarSlot;
		$this->count = $count;
		$this->stackNetworkId = $stackNetworkId;
		$this->customName = $customName;
		$this->durabilityCorrection = $durabilityCorrection;
	}
}

