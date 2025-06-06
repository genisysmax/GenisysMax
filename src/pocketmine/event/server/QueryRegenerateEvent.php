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

namespace pocketmine\event\server;

use pocketmine\Server;
use pocketmine\utils\Binary;
use function chr;
use function count;
use function str_replace;
use function substr;

class QueryRegenerateEvent extends ServerEvent{
    public static $handlerList = null;

    const GAME_ID = "MINECRAFTPE";

    private $serverName;
    private $listPlugins;
    /** @var \pocketmine\plugin\Plugin[] */
    private $plugins;
    /** @var \pocketmine\Player[] */
    private $players;

    private $gametype;
    private $version;
    private $server_engine;
    private $map;
    private $numPlayers;
    private $maxPlayers;
    private $whitelist;
    private $port;
    private $ip;

    private $extraData = [];

    /** @var string|null */
    private $longQueryCache = null;
    /** @var string|null */
    private $shortQueryCache = null;


    /**
     * QueryRegenerateEvent constructor.
     *
     * @param Server $server
     */
    public function __construct(Server $server){
        $this->serverName = $server->getMotd();
        $this->listPlugins = (bool) $server->getProperty("settings.query-plugins", true);
        $this->plugins = $server->getPluginManager()->getPlugins();
        $this->players = [];
        foreach($server->getOnlinePlayers() as $player){
            if($player->isOnline()){
                $this->players[] = $player;
            }
        }

        $this->gametype = ($server->getGamemode() & 0x01) === 0 ? "SMP" : "CMP";
        $this->version = $server->getVersion();
        $this->server_engine = $server->getName() . " " . $server->getPocketMineVersion();
        $this->map = $server->getDefaultLevel() === null ? "unknown" : $server->getDefaultLevel()->getName();
        $this->numPlayers = count($this->players);
        $this->maxPlayers = $server->getMaxPlayers();
        $this->whitelist = $server->hasWhitelist() ? "on" : "off";
        $this->port = $server->getPort();
        $this->ip = $server->getIp();

    }

    private function destroyCache() : void{
        $this->longQueryCache = null;
        $this->shortQueryCache = null;
    }

    /**
     * @return string
     */
    public function getServerName(){
        return $this->serverName;
    }

    /**
     * @param $serverName
     */
    public function setServerName($serverName){
        $this->serverName = $serverName;
        $this->destroyCache();
    }

    /**
     * @return mixed
     */
    public function canListPlugins(){
        return $this->listPlugins;
    }

    /**
     * @param $value
     */
    public function setListPlugins($value){
        $this->listPlugins = (bool) $value;
        $this->destroyCache();
    }

    /**
     * @return \pocketmine\plugin\Plugin[]
     */
    public function getPlugins(){
        return $this->plugins;
    }

    /**
     * @param \pocketmine\plugin\Plugin[] $plugins
     */
    public function setPlugins(array $plugins){
        $this->plugins = $plugins;
        $this->destroyCache();
    }

    /**
     * @return \pocketmine\Player[]
     */
    public function getPlayerList(){
        return $this->players;
    }

    /**
     * @param \pocketmine\Player[] $players
     */
    public function setPlayerList(array $players){
        $this->players = $players;
        $this->destroyCache();
    }

    /**
     * @return int
     */
    public function getPlayerCount(){
        return $this->numPlayers;
    }

    /**
     * @param $count
     */
    public function setPlayerCount($count){
        $this->numPlayers = (int) $count;
        $this->destroyCache();
    }

    /**
     * @return int
     */
    public function getMaxPlayerCount(){
        return $this->maxPlayers;
    }

    /**
     * @param $count
     */
    public function setMaxPlayerCount($count){
        $this->maxPlayers = (int) $count;
        $this->destroyCache();
    }

    /**
     * @return string
     */
    public function getWorld(){
        return $this->map;
    }

    /**
     * @param $world
     */
    public function setWorld($world){
        $this->map = (string) $world;
        $this->destroyCache();
    }

    /**
     * Returns the extra Query data in key => value form
     *
     * @return array
     */
    public function getExtraData(){
        return $this->extraData;
    }

    /**
     * @param array $extraData
     */
    public function setExtraData(array $extraData){
        $this->extraData = $extraData;
        $this->destroyCache();
    }

	/**
	 * @return string
	 */
    public function getLongQuery(){
        if($this->longQueryCache !== null){
            return $this->longQueryCache;
        }
        $query = "";

        $plist = $this->server_engine;
        if(count($this->plugins) > 0 and $this->listPlugins){
            $plist .= ":";
            foreach($this->plugins as $p){
                $d = $p->getDescription();
                $plist .= " " . str_replace([";", ":", " "], ["", "", "_"], $d->getName()) . " " . str_replace([";", ":", " "], ["", "", "_"], $d->getVersion()) . ";";
            }
            $plist = substr($plist, 0, -1);
        }

        $KVdata = [
            "splitnum" => chr(128),
            "hostname" => $this->serverName,
            "gametype" => $this->gametype,
            "game_id" => self::GAME_ID,
            "version" => $this->version,
            "server_engine" => $this->server_engine,
            "plugins" => $plist,
            "map" => $this->map,
            "numplayers" => $this->numPlayers,
            "maxplayers" => $this->maxPlayers,
            "whitelist" => $this->whitelist,
            "hostip" => $this->ip,
            "hostport" => $this->port
        ];

        foreach($KVdata as $key => $value){
            $query .= $key . "\x00" . $value . "\x00";
        }

        foreach($this->extraData as $key => $value){
            $query .= $key . "\x00" . $value . "\x00";
        }

        $query .= "\x00\x01player_\x00\x00";
        foreach($this->players as $player){
            $query .= $player->getName() . "\x00";
        }
        $query .= "\x00";

        return $this->longQueryCache = $query;
    }

	/**
	 * @return string
	 */
    public function getShortQuery(){
        return $this->shortQueryCache ?? ($this->shortQueryCache = $this->serverName . "\x00" . $this->gametype . "\x00" . $this->map . "\x00" . $this->numPlayers . "\x00" . $this->maxPlayers . "\x00" . Binary::writeLShort($this->port) . $this->ip . "\x00");
    }

}


