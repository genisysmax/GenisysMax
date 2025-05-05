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

namespace pocketmine\network\bedrock\adapter\v390\protocol;

use pocketmine\network\bedrock\adapter\v390\protocol\types\MismatchTransactionData;
use pocketmine\network\bedrock\adapter\v390\protocol\types\NormalTransactionData;
use pocketmine\network\bedrock\adapter\v390\protocol\types\ReleaseItemTransactionData;
use pocketmine\network\bedrock\adapter\v390\protocol\types\UseItemOnActorTransactionData;
use pocketmine\network\bedrock\adapter\v390\protocol\types\UseItemTransactionData;

class InventoryTransactionPacket extends \pocketmine\network\bedrock\adapter\v407\protocol\InventoryTransactionPacket{
	use PacketTrait;

    public function decodePayload(){
        $transactionType = $this->getUnsignedVarInt();
        $this->trData = match ($transactionType) {
            self::TYPE_NORMAL => new NormalTransactionData(),
            self::TYPE_MISMATCH => new MismatchTransactionData(),
            self::TYPE_USE_ITEM => new UseItemTransactionData(),
            self::TYPE_USE_ITEM_ON_ACTOR => new UseItemOnActorTransactionData(),
            self::TYPE_RELEASE_ITEM => new ReleaseItemTransactionData(),
            default => throw new \UnexpectedValueException("Unknown transaction type $transactionType"),
        };

        $this->trData->decode($this);
    }

    public function encodePayload(){
        $this->putUnsignedVarInt($this->trData->getTypeId());
        $this->trData->encode($this);
    }

}

