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
 * Set-up wizard used on the first run
 * Can be disabled with --no-wizard
 */
namespace pocketmine\wizard;

use pocketmine\lang\BaseLang;
use pocketmine\utils\Config;
use pocketmine\utils\Internet;
use pocketmine\utils\InternetException;
use function base64_encode;
use function fgets;
use function gethostbyname;
use function random_bytes;
use function sleep;
use function strtolower;
use function substr;
use function trim;
use const PHP_EOL;
use const STDIN;

class SetupWizard{
    public const string DEFAULT_NAME = \pocketmine\NAME . " Server";
    public const int DEFAULT_PORT = 19132;
    public const int DEFAULT_PLAYERS = 20;
    public const int DEFAULT_GAMEMODE = 0;

	private BaseLang $lang;

	public function __construct(){
        //NOOP
	}

	public function run() : bool{
        $this->message(\pocketmine\NAME . " set-up wizard");

		$langs = BaseLang::getLanguageList();
        if(count($langs) === 0){
            $this->error("No language files found, please use provided builds or clone the repository recursively.");
            return false;
        }

        $this->message("Please select a language");
        foreach($langs as $short => $native){
            $this->writeLine(" $native => $short");
        }

        do{
            $lang = strtolower($this->getInput("Language", "eng"));
            if(!isset($langs[$lang])){
                $this->error("Couldn't find the language");
                $lang = null;
            }
        }while($lang === null);

        $this->lang = new BaseLang($lang);

        $this->message($this->lang->get("language_has_been_selected"));

        if(!$this->showLicense()){
            return false;
        }

        //this has to happen here to prevent user avoiding agreeing to license
        $config = new Config(\pocketmine\DATA . "server.properties", Config::PROPERTIES);
        $config->set("language", $lang);
        $config->save();

        if(strtolower($this->getInput($this->lang->get("skip_installer"), "n", "y/N")) === "y"){
            $this->printIpDetails();
            return true;
        }

        $this->writeLine();
        $this->welcome();
        $this->generateBaseConfig();
        $this->generateUserFiles();

        $this->networkFunctions();
        $this->printIpDetails();

        $this->endWizard();

		return true;
	}

    private function showLicense() : bool{
        $this->message($this->lang->translateString("welcome_to_pocketmine", [\pocketmine\NAME]));
        echo <<<LICENSE

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Lesser General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

LICENSE;
        $this->writeLine();
        if(strtolower($this->getInput($this->lang->get("accept_license"), "n", "y/N")) !== "y"){
            $this->error($this->lang->translateString("you_have_to_accept_the_license", [\pocketmine\NAME]));
            sleep(5);

            return false;
        }

        return true;
    }

    private function welcome() : void{
        $this->message($this->lang->get("setting_up_server_now"));
        $this->message($this->lang->get("default_values_info"));
        $this->message($this->lang->get("server_properties"));
    }

	private function generateBaseConfig(){
		$config = new Config(\pocketmine\DATA . "server.properties", Config::PROPERTIES);

        $config->set("motd", ($name = $this->getInput($this->lang->get("name_your_server"), self::DEFAULT_NAME)));
        $config->set("server-name", $name);

		$this->message($this->lang->get("port_warning"));

        do{
            $port = (int) $this->getInput($this->lang->get("server_port"), (string) self::DEFAULT_PORT);
            if($port <= 0 or $port > 65535){
                $this->error($this->lang->get("invalid_port"));
                continue;
            }

            break;
        }while(true);
		$config->set("server-port", $port);

		$this->message($this->lang->get("online_mode_info"));

 		$config->set("online-mode", strtolower($this->getInput($this->lang->get("online_mode"), "n", "y/N")) === "y");
 
		$this->message($this->lang->get("gamemode_info"));

        do{
            $gamemode = (int) $this->getInput($this->lang->get("default_gamemode"), (string) self::DEFAULT_GAMEMODE);
        }while($gamemode < 0 or $gamemode > 3);
        $config->set("gamemode", $gamemode);

        $config->set("max-players", (int) $this->getInput($this->lang->get("max_players"), (string) self::DEFAULT_PLAYERS));

        $this->message($this->lang->get("spawn_protection_info"));

        if(strtolower($this->getInput($this->lang->get("spawn_protection"), "n", "y/N")) === "n"){
            $config->set("spawn-protection", -1);
        }else{
            $config->set("spawn-protection", 16);
        }

        $config->save();
	}

    private function generateUserFiles() : void{
        $this->message($this->lang->get("op_info"));

        $op = strtolower($this->getInput($this->lang->get("op_who"), ""));
        if($op === ""){
            $this->error($this->lang->get("op_warning"));
        }else{
            $ops = new Config(\pocketmine\DATA . "ops.txt", Config::ENUM);
            $ops->set($op, true);
            $ops->save();
        }

        $this->message($this->lang->get("whitelist_info"));

        $config = new Config(\pocketmine\DATA . "server.properties", Config::PROPERTIES);
        if(strtolower($this->getInput($this->lang->get("whitelist_enable"), "n", "y/N")) === "y"){
            $this->error($this->lang->get("whitelist_warning"));
            $config->set("white-list", true);
        }else{
            $config->set("white-list", false);
        }
        $config->save();
    }

    private function networkFunctions() : void{
        $config = new Config(\pocketmine\DATA . "server.properties", Config::PROPERTIES);
        $this->error($this->lang->get("query_warning1"));
        $this->error($this->lang->get("query_warning2"));
        if(strtolower($this->getInput($this->lang->get("query_disable"), "n", "y/N")) === "y"){
            $config->set("enable-query", false);
        }else{
            $config->set("enable-query", true);
        }

        $this->message($this->lang->get("rcon_info"));
        if(strtolower($this->getInput($this->lang->get("rcon_enable"), "n", "y/N")) === "y"){
            $config->set("enable-rcon", true);
            $password = substr(base64_encode(random_bytes(20)), 3, 10);
            $config->set("rcon.password", $password);
            $this->message($this->lang->get("rcon_password") . ": " . $password);
        }else{
            $config->set("enable-rcon", false);
        }

        $config->save();
	}

    private function printIpDetails() : void{
        $this->message($this->lang->get("ip_get"));

        $externalIP = Internet::getIP();
        if($externalIP === false){
            $externalIP = "unknown (server offline)";
        }
        try{
            $internalIP = Internet::getInternalIP();
        }catch(InternetException $e){
            $internalIP = "unknown (" . $e->getMessage() . ")";
        }

        $this->error($this->lang->translateString("ip_warning", ["EXTERNAL_IP" => $externalIP, "INTERNAL_IP" => $internalIP]));
        $this->error($this->lang->get("ip_confirm"));
        $this->readLine();
    }

    private function endWizard() : void{
        $this->message($this->lang->get("you_have_finished"));
        $this->message($this->lang->get("pocketmine_plugins"));
        $this->message($this->lang->translateString("pocketmine_will_start", [\pocketmine\NAME]));

        $this->writeLine();
        $this->writeLine();

        sleep(4);
    }

    private function writeLine(string $line = "") : void{
        echo $line . PHP_EOL;
    }

    private function readLine() : string{
        return trim((string) fgets(STDIN));
    }

    private function message(string $message) : void{
        $this->writeLine("[*] " . $message);
    }

    private function error(string $message) : void{
        $this->writeLine("[!] " . $message);
    }

    private function getInput(string $message, string $default = "", string $options = "") : string{
        $message = "[?] " . $message;

        if($options !== "" or $default !== ""){
            $message .= " (" . ($options === "" ? $default : $options) . ")";
        }
        $message .= ": ";

        echo $message;

        $input = $this->readLine();

        return $input === "" ? $default : $input;
    }
}


