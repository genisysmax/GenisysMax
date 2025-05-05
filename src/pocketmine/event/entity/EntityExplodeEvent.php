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

namespace pocketmine\event\entity;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\event\Cancellable;
use pocketmine\level\Position;

/**
 * Called when a entity explodes
 */
class EntityExplodeEvent extends EntityEvent implements Cancellable{
	public static $handlerList = null;

	/** @var Position */
	protected $position;

	/**
	 * @var Block[]
	 */
	protected $blocks;

	/** @var float */
	protected $yield;

	/**
	 * @param Entity   $entity
	 * @param Position $position
	 * @param Block[]  $blocks
	 * @param float    $yield
	 */
	public function __construct(Entity $entity, Position $position, array $blocks, $yield){
		$this->entity = $entity;
		$this->position = $position;
		$this->blocks = $blocks;
		$this->yield = $yield;
	}

	/**
	 * @return Position
	 */
	public function getPosition(){
		return $this->position;
	}

	/**
	 * @return Block[]
	 */
	public function getBlockList(){
		return $this->blocks;
	}

	/**
	 * @param Block[] $blocks
	 */
	public function setBlockList(array $blocks){
		$this->blocks = $blocks;
	}

	/**
	 * @return float
	 */
	public function getYield(){
		return $this->yield;
	}

	/**
	 * @param float $yield
	 */
	public function setYield($yield){
		$this->yield = $yield;
	}

}

