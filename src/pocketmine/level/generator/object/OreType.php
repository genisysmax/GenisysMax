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

namespace pocketmine\level\generator\object;

use pocketmine\block\Block;

class OreType{
	/** @var Block */
	public $material;
	/** @var int */
	public $clusterCount;
	/** @var int */
	public $clusterSize;
	/** @var int */
	public $maxHeight;
	/** @var int */
	public $minHeight;

	public function __construct(Block $material, int $clusterCount, int $clusterSize, int $minHeight, int $maxHeight){
		$this->material = $material;
		$this->clusterCount = $clusterCount;
		$this->clusterSize = $clusterSize;
		$this->maxHeight = $maxHeight;
		$this->minHeight = $minHeight;
	}
}

