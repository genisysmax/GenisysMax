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

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>


use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\mcpe\NetworkCompression;
use pocketmine\network\NetworkSession;
use pocketmine\Server;
use pocketmine\utils\Binary;
use function get_class;
use function ord;
use function strlen;

#ifndef COMPILE
#endif

//TODO: get rid of this
class BatchPacket extends DataPacket
{
    public const NETWORK_ID = 0xfe;

    /** @var string */
    public $payload = "";
    /** @var int */
    protected $compressionLevel = 7;

    public function canBeBatched(): bool
    {
        return false;
    }

    public function canBeSentBeforeLogin(): bool
    {
        return true;
    }

    public function decodePayload()
    {
        $data = $this->getRemaining();
        try {
            $this->payload = NetworkCompression::decompress($data);
        } catch (\ErrorException $e) { //zlib decode error
            $this->payload = "";
        }
    }

    public function encodePayload()
    {
        $this->put(NetworkCompression::compress($this->payload, $this->compressionLevel));
    }

    /**
     * @param DataPacket $packet
     */
    public function addPacket(DataPacket $packet)
    {
        if (!$packet->canBeBatched()) {
            throw new \InvalidArgumentException(get_class($packet) . " cannot be put inside a BatchPacket");
        }
        if (!$packet->isEncoded) {
            $packet->encode();
        }

        $this->payload .= Binary::writeUnsignedVarInt(strlen($packet->buffer)) . $packet->buffer;
    }

    /**
     * @return \Generator
     */
    public function getPackets()
    {
        $stream = new NetworkBinaryStream($this->payload);
        while (!$stream->feof()) {
            yield $stream->getString();
        }
    }

    public function getCompressionLevel(): int
    {
        return $this->compressionLevel;
    }

    public function setCompressionLevel(int $level)
    {
        $this->compressionLevel = $level;
    }

    public function handle(NetworkSession $session): bool
    {
        if ($this->payload === "") {
            return false;
        }

        $count = 0;
        foreach ($this->getPackets() as $buf) {
            if (++$count > 2048) {
                Server::getInstance()->getLogger()->critical("Too many packets in a single batch!");
                return false;
            }
            if (isset($buf[0])) {
                $pk = PacketPool::getPacketById(ord($buf[0]));

                if (!$pk->canBeBatched()) {
                    Server::getInstance()->getLogger()->critical("Received invalid " . get_class($pk) . " inside BatchPacket");
                    return false;
                }

                $pk->setBuffer($buf, 1);

                if ($pk->buffer === "\x21\x04\x00") {
                    --$count;
                }
                $session->handleDataPacket($pk);
            }

        }
        return true;
    }
}


