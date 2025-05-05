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

namespace pocketmine\network\bedrock;

use pocketmine\network\bedrock\adapter\ProtocolAdapterFactory;
use pocketmine\network\bedrock\palette\ActorMapping;
use pocketmine\network\bedrock\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\bedrock\protocol\BiomeDefinitionListPacket;
use pocketmine\network\bedrock\protocol\ProtocolInfo;
use function file_get_contents;

final class StaticPacketCache{
	/** @var string[] */
	private static $biomeDefs = [];
	/** @var string[] */
	private static $availableActorIdentifiers = [];
	/** @var BiomeDefinitionListPacket */
	private static BiomeDefinitionListPacket $biomeDefsPkt;
	/** @var AvailableActorIdentifiersPacket */
	private static AvailableActorIdentifiersPacket $actorIdentifiersPkt;

	public static function init() : void
    {
        $biomeDefs = new BiomeDefinitionListPacket();
        $biomeDefs->namedtag = file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/biome_definitions.nbt");
        self::$biomeDefsPkt = clone $biomeDefs;

        $actorIdentifiers = new AvailableActorIdentifiersPacket();
        $actorIdentifiers->namedtag = ActorMapping::getEncodedActorIdentifiers();
        self::$actorIdentifiersPkt = clone $actorIdentifiers;

        $protocols = [ProtocolInfo::CURRENT_PROTOCOL];
        foreach (array_keys(ProtocolAdapterFactory::PROTOCOL_ADAPTERS) as $protocol) {
            $protocols[] = $protocol;
        }

        foreach ($protocols as $protocol) {
            self::initProtocol($protocol);
        }
    }

    public static function initProtocol(int $protocol) : string{
        $adapter = ProtocolAdapterFactory::get($protocol);

        $stream = new BedrockPacketBatch();
        if($adapter === null){
            $stream->putPacket(self::$biomeDefsPkt);
        }else{
            $stream->putPacket($adapter->processServerToClient(self::$biomeDefsPkt));
        }

        self::$biomeDefs[$protocol] = NetworkCompression::compress($stream->buffer);

        $stream->reset();
        if($adapter === null){
            $stream->putPacket(self::$actorIdentifiersPkt);
        }else{
            $stream->putPacket($adapter->processServerToClient(self::$actorIdentifiersPkt));
        }

        return self::$availableActorIdentifiers[$protocol] = NetworkCompression::compress($stream->buffer);
    }

	/**
	 * @param int $protocol
	 *
	 * @return string
	 */
	public static function getBiomeDefs(int $protocol) : string{
		return self::$biomeDefs[$protocol];
	}

	/**
	 * @param int $protocol
	 *
	 * @return string
	 */
	public static function getAvailableActorIdentifiers(int $protocol) : string{
		$actorIdentifiers = self::$availableActorIdentifiers[$protocol] ?? null;
		if ($actorIdentifiers === NULL && isset(ProtocolAdapterFactory::PROTOCOL_ADAPTERS[$protocol])) {
			$actorIdentifiers = self::initProtocol($protocol);
		}
		return $actorIdentifiers;
	}
}

