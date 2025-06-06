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

use InvalidArgumentException;
use pocketmine\network\bedrock\protocol\UpdateAbilitiesPacket;

final class UpdateAbilitiesPacketLayer{

	public const LAYER_CACHE = 0;
	public const LAYER_BASE = 1;
	public const LAYER_SPECTATOR = 2;
	public const LAYER_COMMANDS = 3;

	public const ABILITY_BUILD = 0;
	public const ABILITY_MINE = 1;
	public const ABILITY_DOORS_AND_SWITCHES = 2; //disabling this also disables dropping items (???)
	public const ABILITY_OPEN_CONTAINERS = 3;
	public const ABILITY_ATTACK_PLAYERS = 4;
	public const ABILITY_ATTACK_MOBS = 5;
	public const ABILITY_OPERATOR = 6;
	public const ABILITY_TELEPORT = 7;
	public const ABILITY_INVULNERABLE = 8;
	public const ABILITY_FLYING = 9;
	public const ABILITY_ALLOW_FLIGHT = 10;
	public const ABILITY_INFINITE_RESOURCES = 11; //in vanilla they call this "instabuild", which is a bad name
	public const ABILITY_LIGHTNING = 12; //???
	private const ABILITY_FLY_SPEED = 13;
	private const ABILITY_WALK_SPEED = 14;
	public const ABILITY_MUTED = 15;
	public const ABILITY_WORLD_BUILDER = 16;
	public const ABILITY_NO_CLIP = 17;
	public const ABILITY_PRIVILEGED_BUILDER = 17;

	public const NUMBER_OF_ABILITIES = 19;

	/** @var int */
	private $layerId;
	/** @var bool[] */
	private $boolAbilities;
	/** @var float|null */
	private $flySpeed;
	/** @var float|null */
	private $walkSpeed;

	/**
	 * @param int $layerId
	 * @param bool[] $boolAbilities
	 * @param float|null $flySpeed
	 * @param float|null $walkSpeed
	 */
	public function __construct(int $layerId, array $boolAbilities, ?float $flySpeed, ?float $walkSpeed){
		$this->layerId = $layerId;
		$this->boolAbilities = $boolAbilities;
		$this->flySpeed = $flySpeed;
		$this->walkSpeed = $walkSpeed;
	}

	/**
	 * @return int
	 */
	public function getLayerId() : int{
		return $this->layerId;
	}

	/**
	 * @return bool[]
	 */
	public function getBoolAbilities() : array{
		return $this->boolAbilities;
	}

	/**
	 * @return float|null
	 */
	public function getFlySpeed() : ?float{
		return $this->flySpeed;
	}

	/**
	 * @return float|null
	 */
	public function getWalkSpeed() : ?float{
		return $this->walkSpeed;
	}

	public static function decode(UpdateAbilitiesPacket $pk) : self{
		$layerId = $pk->getLShort();
		$setAbilities = $pk->getLInt();
		$setAbilityValues = $pk->getLInt();
		$flySpeed = $pk->getLFloat();
		$walkSpeed = $pk->getLFloat();

		$boolAbilities = [];
		for($i = 0; $i < self::NUMBER_OF_ABILITIES; $i++){
			if($i === self::ABILITY_FLY_SPEED || $i === self::ABILITY_WALK_SPEED){
				continue;
			}
			if(($setAbilities & (1 << $i)) !== 0){
				$boolAbilities[$i] = ($setAbilityValues & (1 << $i)) !== 0;
			}
		}
		if(($setAbilities & (1 << self::ABILITY_FLY_SPEED)) === 0){
			if($flySpeed !== 0.0){
				throw new InvalidArgumentException("Fly speed should be zero if the layer does not set it");
			}
			$flySpeed = null;
		}
		if(($setAbilities & (1 << self::ABILITY_WALK_SPEED)) === 0){
			if($walkSpeed !== 0.0){
				throw new InvalidArgumentException("Walk speed should be zero if the layer does not set it");
			}
			$walkSpeed = null;
		}

		return new self($layerId, $boolAbilities, $flySpeed, $walkSpeed);
	}

	public function encode(UpdateAbilitiesPacket $pk) : void{
		$pk->putLShort($this->layerId);

		$setAbilities = 0;
		$setAbilityValues = 0;
		foreach($this->boolAbilities as $ability => $value){
			$setAbilities |= (1 << $ability);
			$setAbilityValues |= ($value ? 1 << $ability : 0);
		}
		if($this->flySpeed !== null){
			$setAbilities |= (1 << self::ABILITY_FLY_SPEED);
		}
		if($this->walkSpeed !== null){
			$setAbilities |= (1 << self::ABILITY_WALK_SPEED);
		}

		$pk->putLInt($setAbilities);
		$pk->putLInt($setAbilityValues);
		$pk->putLFloat($this->flySpeed ?? 0);
		$pk->putLFloat($this->walkSpeed ?? 0);
	}
}

