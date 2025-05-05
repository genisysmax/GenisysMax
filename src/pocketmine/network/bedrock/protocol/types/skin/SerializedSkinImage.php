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

use function strlen;

class SerializedSkinImage{

	/**
	 * @param string $imageData
	 *
	 * @return self
	 */
	public static function fromLegacyImageData(string $imageData) : self{
		switch(strlen($imageData)){
			case 0:
				return self::empty();
			case 64 * 32 * 4:
				return new self(64, 64, self::fix64x32Skin($imageData));
			case 64 * 64 * 4:
				return new self(64, 64, $imageData);
			case 128 * 128 * 4:
				return new self(128, 128, $imageData);
		}
		throw new \InvalidArgumentException("Unknown legacy image data size");
	}

	/**
	 * @return self
	 */
	public static function empty() : self{
		return new self(0, 0, "");
	}

	/** @var int */
	private $width;

	/** @var int */
	private $height;
	/** @var string */
	private $data;
	public function __construct(int $width, int $height, string $data){
		$this->width = $width;
		$this->height = $height;
		$this->data = $data;
	}

	public function isValid() : bool{
		return strlen($this->data) === $this->width * $this->height * 4;
	}

	/**
	 * @return int
	 */
	public function getWidth() : int{
		return $this->width;
	}

	/**
	 * @return int
	 */
	public function getHeight() : int{
		return $this->height;
	}

	/**
	 * @return string
	 */
	public function getData() : string{
		return $this->data;
	}

	private static function fix64x32Skin(string $imageData) : string{
		// process from: https://imgur.com/a/hfaqL
		$skinData = str_pad($imageData, 64 * 64 * 4, "\x00\x00\x00\x00"); // pad to 64x64

		// leg tops
		$skinData = self::mirroredCopy($skinData, 4, 16, 4, 4, 20, 48);
		$skinData = self::mirroredCopy($skinData, 8, 16, 4, 4, 24, 48);

		// arm tops
		$skinData = self::mirroredCopy($skinData, 44, 16, 4, 4, 36, 48);
		$skinData = self::mirroredCopy($skinData, 48, 16, 4, 4, 40, 48);

		// leg pieces
		$skinData = self::mirroredCopy($skinData, 8, 20, 4, 12, 16, 52);
		$skinData = self::mirroredCopy($skinData, 4, 20, 4, 12, 20, 52);
		$skinData = self::mirroredCopy($skinData, 0, 20, 4, 12, 24, 52);
		$skinData = self::mirroredCopy($skinData, 12, 20, 4, 12, 28, 52);

		// arm pieces
		$skinData = self::mirroredCopy($skinData, 48, 20, 4, 12, 32, 52);
		$skinData = self::mirroredCopy($skinData, 44, 20, 4, 12, 36, 52);
		$skinData = self::mirroredCopy($skinData, 40, 20, 4, 12, 40, 52);
		$skinData = self::mirroredCopy($skinData, 52, 20, 4, 12, 44, 52);

		return $skinData;
	}

	private static function mirroredCopy(string $bitmap, int $startX, int $startY, int $width, int $height, int $toX, int $toY) : string{
		for($x = 0; $x < $width; $x++) {
			for($y = 0; $y < $height; $y++) {
				$index = self::toIndex($startX + $x, $startY + $y);
				$toIndex = self::toIndex($toX + ($width - ($x + 1)), $toY + $y);
				for($bit = 0; $bit < 4; $bit++) {
					$bitmap[$toIndex + $bit] = $bitmap[$index + $bit];
				}
			}
		}
		return $bitmap;
	}

	private static function toIndex(int $x, int $y) : int{
		return (64 * $y + $x) * 4;
	}
}

