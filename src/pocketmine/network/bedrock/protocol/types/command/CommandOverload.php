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

final class CommandOverload{

	private $parameters = [];
    /** @var bool */
    private $chaining;

	/**
	 * @param CommandParameter[] $parameters
	 */
	public function __construct(
		bool $chaining,
		array $parameters
	){
		$this->parameters = $parameters;
        $this->chaining = $chaining;
		//(function(CommandParameter ...$parameters) : void{})(...$parameters);
	}

	public function isChaining() : bool{ return $this->chaining; }

	/**
	 * @return CommandParameter[]
	 */
	public function getParameters() : array{ return $this->parameters; }
}

