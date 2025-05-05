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

namespace pocketmine\network\bedrock\utils;

use pocketmine\BedrockPlayer;

final class BedrockUtils{

	public static function splitPlayers(array $players, &$pw10Players, &$bedrockPlayers) : void{
		$pw10Players = [];
		$bedrockPlayers = [];

		foreach($players as $player){
			if($player instanceof BedrockPlayer){
				$bedrockPlayers[] = $player;
			}else{
				$pw10Players[] = $player;
			}
		}
	}

	/**
	 * @param string $text
	 *
	 * @return string[]
	 */
	public static function convertSignTextToLines(string $text) : array{
		return array_slice(array_pad(explode("\n", $text), 4, ""), 0, 4);
	}

	/**
	 * @param string[] $lines
	 *
	 * @return string
	 */
	public static function convertSignLinesToText(array $lines) : string{
		return implode("\n", $lines);
	}

	private function __construct(){
		// oof
	}
}

