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

class CommandEnumConstraint{
	/** @var CommandEnum */
	private $enum;
	/** @var int */
	private $valueOffset;
	/** @var int[] */
	private $constraints; //TODO: find constants

	/**
	 * @param CommandEnum $enum
	 * @param int         $valueOffset
	 * @param int[]       $constraints
	 */
	public function __construct(CommandEnum $enum, int $valueOffset, array $constraints){
		(static function(int ...$_){})(...$constraints);
		if(!isset($enum->enumValues[$valueOffset])){
			throw new \InvalidArgumentException("Invalid enum value offset $valueOffset");
		}
		$this->enum = $enum;
		$this->valueOffset = $valueOffset;
		$this->constraints = $constraints;
	}

	public function getEnum() : CommandEnum{
		return $this->enum;
	}

	public function getValueOffset() : int{
		return $this->valueOffset;
	}

	public function getAffectedValue() : string{
		return $this->enum->enumValues[$this->valueOffset];
	}

	/**
	 * @return int[]
	 */
	public function getConstraints() : array{
		return $this->constraints;
	}
}

