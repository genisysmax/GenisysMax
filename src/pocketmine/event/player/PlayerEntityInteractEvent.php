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

namespace pocketmine\event\player;

use pocketmine\entity\Entity;
use pocketmine\event\Cancellable;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;

/**
 * Called when a player interacts an entity.
 */
class PlayerEntityInteractEvent extends PlayerEvent implements Cancellable{
	public static $handlerList = null;

	/** @var Entity */
	protected $entity;
	/** @var Item */
	protected $item;
    /** @var Vector3 */
    protected $clickPos;

	public function __construct(Player $player, Entity $entity, Item $item, Vector3 $clickPos){
		$this->player = $player;
		$this->entity = $entity;
        $this->item = $item;
        $this->clickPos = $clickPos;
	}

	/**
	 * @return Entity
	 */
	public function getEntity() : Entity{
		return $this->entity;
	}

	/**
	 * @return Item
	 */
    public function getItem() : Item{
        return $this->item;
    }

    public function getClickPos() : Vector3{
        return $this->clickPos;
    }
}


