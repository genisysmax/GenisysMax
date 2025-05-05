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

namespace pocketmine\network\bedrock\adapter\mapper;

use InvalidArgumentException;
use pocketmine\network\bedrock\protocol\AvailableCommandsPacket;
use pocketmine\network\bedrock\protocol\types\command\CommandOverload;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

class CommandArgTypeMapper{

	/** @var string */
	protected $targetClass;
	/** @var int[] */
	protected $mapping = [];
	/** @var int */
	protected $fallbackType;

	public function __construct(string $class){
		if(!is_subclass_of($class, AvailableCommandsPacket::class)){
			throw new InvalidArgumentException("Target AvailableCommandsPacket should inherit the base class");
		}

		try{
			$source = new ReflectionClass(AvailableCommandsPacket::class);
			$target = new ReflectionClass($class);
		}catch(ReflectionException $e){
			throw new RuntimeException($e->getMessage());
		}

		$this->targetClass = $class;

		$targetConstants = $target->getConstants();
		foreach($source->getConstants() as $name => $val){
			if(substr($name, 0, 4) === "ARG_" && array_key_exists($name, $targetConstants)){
				$this->mapping[$val] = $targetConstants[$name];
			}
		}

		$this->fallbackType = $targetConstants["ARG_TYPE_STRING"];
	}

	public function map(AvailableCommandsPacket $packet) : AvailableCommandsPacket{
		/** @var AvailableCommandsPacket $target */
		$target = new $this->targetClass;

		foreach($packet->commandData as $commandData){
			$newCmdData = clone $commandData;
			$newCmdData->overloads = [];

			foreach($commandData->overloads as $overload){
				$parameters = [];

				foreach($overload->getParameters() as $parameter){
					$rawParamType = $parameter->paramType & AvailableCommandsPacket::ARG_FLAG_VALID - 1;

					$newParamType = $this->mapping[$rawParamType] ?? $this->fallbackType;

					$newParameter = clone $parameter;
					$newParameter->paramType = AvailableCommandsPacket::ARG_FLAG_VALID | $newParamType;
					$parameters[] = $newParameter;
				}

				$newCmdData->overloads[] = new CommandOverload(false, $parameters);
			}

			$target->commandData[] = $newCmdData;
		}

		return $target;
	}
}

