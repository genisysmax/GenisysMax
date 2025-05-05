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

/**
 * Implementation of the UT3 Query Protocol (GameSpot)
 * Source: http://wiki.unrealadmin.org/UT3_query_protocol
 */
namespace pocketmine\network\query;

use pocketmine\network\AdvancedNetworkInterface;
use pocketmine\Server;
use pocketmine\utils\Binary;
use function chr;
use function hash;
use function random_bytes;
use function strlen;
use function substr;

class QueryHandler{
    private $server, $lastToken, $token;

    const HANDSHAKE = 9;
    const STATISTICS = 0;

    /**
     * QueryHandler constructor.
     */
    public function __construct(){
        $this->server = Server::getInstance();
        $this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.server.query.start"));
        $addr = ($ip = $this->server->getIp()) != "" ? $ip : "0.0.0.0";
        $port = $this->server->getPort();
        $this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.server.query.info", [$port]));
        /*
        The Query protocol is built on top of the existing Minecraft PE UDP network stack.
        Because the 0xFE packet does not exist in the MCPE protocol,
        we can identify	Query packets and remove them from the packet queue.

        Then, the Query class handles itself sending the packets in raw form, because
        packets can conflict with the MCPE ones.
        */

        $this->regenerateToken();
        $this->lastToken = $this->token;
        $this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.server.query.running", [$addr, $port]));
    }

    private function debug(string $message) : void{
        //TODO: replace this with a proper prefixed logger
        $this->server->getLogger()->debug("[Query] $message");
    }

	public function regenerateInfo(){

	}

	public function regenerateToken(){
		$this->lastToken = $this->token;
		$this->token = random_bytes(16);
	}

    public static function getTokenString($token, $salt){
        return Binary::readInt(substr(hash("sha512", $salt . ":" . $token, true), 7, 4));
    }

    public function handle(AdvancedNetworkInterface $interface, string $address, int $port, string $packet){
        $offset = 2;
        $packetType = ord($packet[$offset++]);
        $sessionID = Binary::readInt(substr($packet, $offset, 4));
        $offset += 4;
        $payload = substr($packet, $offset);

        switch($packetType){
            case self::HANDSHAKE: //Handshake
                $reply = chr(self::HANDSHAKE);
                $reply .= Binary::writeInt($sessionID);
                $reply .= self::getTokenString($this->token, $address) . "\x00";

                $interface->sendRawPacket($address, $port, $reply);
                break;
            case self::STATISTICS: //Stat
                $token = Binary::readInt(substr($payload, 0, 4));
                if($token !== ($t1 = self::getTokenString($this->token, $address)) and $token !== ($t2 = self::getTokenString($this->lastToken, $address))){
                    $this->debug("Bad token $token from $address $port, expected $t1 or $t2");
                    break;
                }
                $reply = chr(self::STATISTICS);
                $reply .= Binary::writeInt($sessionID);

                if(strlen($payload) === 8){
                    $reply .= $this->server->getQueryInformation()->getLongQuery();
                }else{
                    $reply .= $this->server->getQueryInformation()->getShortQuery();
                }
                $interface->sendRawPacket($address, $port, $reply);
                break;
            default:
                $this->debug("Unhandled packet from $address $port: " . base64_encode($packet));
                break;
        }
    }
}


