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

namespace pocketmine\network\bedrock\protocol\types\skin;

class PieceTintColor{

	/** @var string */
	protected $pieceType;
	/** @var string[] */
	protected $colors;

	/**
	 * @param string $pieceType
	 * @param string[] $colors
	 */
	public function __construct(string $pieceType, array $colors){
		$this->pieceType = $pieceType;
		$this->colors = $colors;
	}

	/**
	 * @return string
	 */
	public function getPieceType() : string{
		return $this->pieceType;
	}

	/**
	 * @return string[]
	 */
	public function getColors() : array{
		return $this->colors;
	}
}

