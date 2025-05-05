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

class SkinAnimation{
	public const TYPE_FACE = 1;
	public const TYPE_BODY_32x32 = 2;
	public const TYPE_BODY_64x64 = 3;

	public const EXPRESSION_LINEAR = 0; //???
	public const EXPRESSION_BLINKING = 1;

	/** @var SerializedSkinImage */
	private $image;
	/** @var int */
	private $type; // TODO
	/** @var float */
	private $frames;
	/** @var int */
	private $expressionType;

	public function __construct(SerializedSkinImage $image, int $type, float $frames, int $expressionType){
		$this->image = $image;
		$this->type = $type;
		$this->frames = $frames;
		$this->expressionType = $expressionType;
	}

	/**
	 * @return SerializedSkinImage
	 */
	public function getImage() : SerializedSkinImage{
		return $this->image;
	}

	/**
	 * @return int
	 */
	public function getType() : int{
		return $this->type;
	}

	/**
	 * @return float
	 */
	public function getFrames() : float{
		return $this->frames;
	}

	/**
	 * @return int
	 */
	public function getExpressionType() : int{
		return $this->expressionType;
	}
}

