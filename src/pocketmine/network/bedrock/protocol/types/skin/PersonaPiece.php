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

class PersonaPiece{

	/** @var string */
	protected $pieceId;
	/** @var string */
	protected $pieceType;
	/** @var string */
	protected $packId;
	/** @var bool */
	protected $isDefault;
	/** @var string */
	protected $productId;

	public function __construct(string $pieceId, string $pieceType, string $packId, bool $isDefaultPiece, string $productId){
		$this->pieceId = $pieceId;
		$this->pieceType = $pieceType;
		$this->packId = $packId;
		$this->isDefault = $isDefaultPiece;
		$this->productId = $productId;
	}

	/**
	 * @return string
	 */
	public function getPieceId() : string{
		return $this->pieceId;
	}

	/**
	 * @return string
	 */
	public function getPieceType() : string{
		return $this->pieceType;
	}

	/**
	 * @return string
	 */
	public function getPackId() : string{
		return $this->packId;
	}

	/**
	 * @return bool
	 */
	public function isDefault() : bool{
		return $this->isDefault;
	}

	/**
	 * @return string
	 */
	public function getProductId() : string{
		return $this->productId;
	}
}

