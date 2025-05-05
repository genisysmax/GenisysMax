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

namespace pocketmine\level\particle;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\Skin;
use pocketmine\utils\UUID;
use function str_repeat;

class FloatingTextParticle extends Particle{
    //TODO: HACK!

    protected $text;
    protected $title;
    protected $entityId;
    protected $invisible = false;

    /**
     * @param Vector3 $pos
     * @param int     $text
     * @param string  $title
     */
    public function __construct(Vector3 $pos, $text, $title = ""){
        parent::__construct($pos->x, $pos->y, $pos->z);
        $this->text = $text;
        $this->title = $title;
    }

    public function setText($text){
        $this->text = $text;
    }

    public function setTitle($title){
        $this->title = $title;
    }

    public function isInvisible(){
        return $this->invisible;
    }

    public function setInvisible($value = true){
        $this->invisible = (bool) $value;
    }

    public function encode(){
        $p = [];

        if($this->entityId === null){
            $this->entityId = Entity::$entityCount++;
        }else{
            $pk0 = new RemoveEntityPacket();
            $pk0->entityUniqueId = $this->entityId;

            $p[] = $pk0;
        }

        if(!$this->invisible){
            $uuid = UUID::fromRandom();
            $nameTag = $this->title . ($this->text !== "" ? "\n" . $this->text : "");

            $pk = new PlayerListPacket();
            $pk->type = PlayerListPacket::TYPE_ADD;
            $pk->entries[] = PlayerListEntry::createAdditionEntry($uuid, $this->entityId, $nameTag, new Skin("Standard_Custom", str_repeat("\x00", 8192)));
            $p[] = $pk;

            $pk = new AddPlayerPacket();
            $pk->uuid = $uuid;
            $pk->username = $this->title;
            $pk->entityRuntimeId = $this->entityId;
            $pk->x = $this->x;
            $pk->y = $this->y - 0.50;
            $pk->z = $this->z;
            $pk->item = Item::get(Item::AIR);
            $flags = (
                (1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG) |
                (1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG) |
                (1 << Entity::DATA_FLAG_IMMOBILE)
            );
            $pk->metadata = [
                Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
                Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $nameTag],
                Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0.01],
            ];
            $p[] = $pk;

            $pk = new PlayerListPacket();
            $pk->type = PlayerListPacket::TYPE_REMOVE;
            $pk->entries[] = PlayerListEntry::createRemovalEntry($uuid);
            $p[] = $pk;
        }

        return $p;
    }
}

