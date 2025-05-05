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

namespace pocketmine\utils;

/**
 * @internal
 * @see TextFormat::toJSON()
 */
final class TextFormatJsonObject implements \JsonSerializable{
	/** @var string|null */
	public $text = null;
	/** @var string|null */
	public $color = null;
	/** @var bool|null */
	public $bold = null;
	/** @var bool|null */
	public $italic = null;
	/** @var bool|null */
	public $underlined = null;
	/** @var bool|null */
	public $strikethrough = null;
	/** @var bool|null */
	public $obfuscated = null;
	/**
	 * @var TextFormatJsonObject[]|null
	 * @phpstan-var array<int, TextFormatJsonObject>|null
	 */
	public $extra = null;

	public function jsonSerialize(){
		$result = (array) $this;
		foreach($result as $k => $v){
			if($v === null){
				unset($result[$k]);
			}
		}
		return $result;
	}
}


