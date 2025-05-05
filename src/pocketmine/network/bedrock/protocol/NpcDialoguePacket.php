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

namespace pocketmine\network\bedrock\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\NetworkSession;

class NpcDialoguePacket extends DataPacket{
    public const NETWORK_ID = ProtocolInfo::NPC_DIALOGUE_PACKET;

    public const ACTION_OPEN = 0;
    public const ACTION_CLOSE = 1;

    /** @var int */
    public $npcActorUniqueId;
    /** @var int */
    public $actionType;
    /** @var string */
    public $dialogue;
    /** @var string */
    public $sceneName;
    /** @var string */
    public $npcName;
    /** @var string */
    public $actionJson;

    public function decodePayload() : void{
        $this->npcActorUniqueId = $this->getEntityUniqueId();
        $this->actionType = $this->getVarInt();
        $this->dialogue = $this->getString();
        $this->sceneName = $this->getString();
        $this->npcName = $this->getString();
        $this->actionJson = $this->getString();
    }

    public function encodePayload() : void{
        $this->putEntityUniqueId($this->npcActorUniqueId);
        $this->putVarInt($this->actionType);
        $this->putString($this->dialogue);
        $this->putString($this->sceneName);
        $this->putString($this->npcName);
        $this->putString($this->actionJson);
    }

    public function handle(NetworkSession $session) : bool{
        return $session->handleNpcDialogue($this);
    }
}

