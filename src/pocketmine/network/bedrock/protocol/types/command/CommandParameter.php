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

namespace pocketmine\network\bedrock\protocol\types\command;

use pocketmine\network\bedrock\protocol\AvailableCommandsPacket;

class CommandParameter{
	public const int FLAG_FORCE_COLLAPSE_ENUM = 0x1;
	public const int FLAG_HAS_ENUM_CONSTRAINT = 0x2;

	/** @var string */
	public $paramName;
	/** @var int */
	public $paramType;
	/** @var bool */
	public $isOptional;
	/** @var CommandEnum|null */
	public $enum;
	/** @var string|null */
	public $postfix;
	public $flags = 0;

	public function __construct(string $name = "args", int $type = AvailableCommandsPacket::ARG_TYPE_RAWTEXT, bool $optional = true, $extraData = null){
		$this->paramName = $name;
		$this->paramType = AvailableCommandsPacket::ARG_FLAG_VALID | $type;
		$this->isOptional = $optional;
		if($extraData instanceof CommandEnum){
			$this->enum = $extraData;
		}elseif(is_string($extraData)){
			$this->postfix = $extraData;
		}
	}

	public function toPw10Data() : array{
		return [
			"name" => $this->paramName,
			"optional" => $this->isOptional,
			"type" => AvailableCommandsPacket::argTypeToPw10Type($this->paramType),
		];
	}
}


