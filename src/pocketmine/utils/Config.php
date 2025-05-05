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

namespace pocketmine\utils;

use pocketmine\scheduler\FileWriteTask;
use pocketmine\Server;
use function array_change_key_case;
use function array_keys;
use function array_pop;
use function array_shift;
use function basename;
use function count;
use function date;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_array;
use function is_bool;
use function json_decode;
use function json_encode;
use function preg_match_all;
use function preg_replace;
use function serialize;
use function str_replace;
use function strlen;
use function strtolower;
use function substr;
use function trim;
use function unserialize;
use function yaml_emit;
use function yaml_parse;
use const CASE_LOWER;
use const JSON_BIGINT_AS_STRING;
use const JSON_PRETTY_PRINT;
use const YAML_UTF8_ENCODING;

/**
 * Config Class for simple config manipulation of multiple formats.
 */
class Config{
    public const int DETECT = -1; //Detect by file extension
    public const int PROPERTIES = 0; // .properties
    public const int CNF = Config::PROPERTIES; // .cnf
    public const int JSON = 1; // .js, .json
    public const int YAML = 2; // .yml, .yaml
    //public const int EXPORT = 3; // .export, .xport
    public const int SERIALIZED = 4; // .sl
    public const int ENUM = 5; // .txt, .list, .enum
    public const int ENUMERATION = Config::ENUM;

    /**
     * @var mixed[]
     * @phpstan-var array<string, mixed>
     */
	private array $config = [];

    /**
     * @var mixed[]
     * @phpstan-var array<string, mixed>
     */
	private array $nestedCache = [];

	private string $file;
	private bool $correct = false;
	private int $type = Config::DETECT;
	private int $jsonOptions = JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING;

    private bool $changed = false;

	public static array $formats = [
		"properties" => Config::PROPERTIES,
		"cnf" => Config::CNF,
		"conf" => Config::CNF,
		"config" => Config::CNF,
		"json" => Config::JSON,
		"js" => Config::JSON,
		"yml" => Config::YAML,
		"yaml" => Config::YAML,
		//"export" => Config::EXPORT,
		//"xport" => Config::EXPORT,
		"sl" => Config::SERIALIZED,
		"serialize" => Config::SERIALIZED,
		"txt" => Config::ENUM,
		"list" => Config::ENUM,
		"enum" => Config::ENUM
	];

    /**
     * @param string  $file    Path of the file to be loaded
     * @param int     $type    Config type to load, -1 by default (detect)
     * @param array $default Array with the default values that will be written to the file if it did not exist
     * @param null    $correct reference parameter, Sets correct to true if everything has been loaded correctly
     * @phpstan-param array<string, mixed> $default
     */
    public function __construct(string $file, int $type = Config::DETECT, array $default = [], &$correct = null){
        $this->load($file, $type, $default);
        $correct = $this->correct;
    }

    /**
     * Removes all the changes in memory and loads the file again
     */
    public function reload() : void{
        $this->config = [];
        $this->nestedCache = [];
        $this->correct = false;
        $this->load($this->file, $this->type);
    }

    public function hasChanged() : bool{
        return $this->changed;
    }

    public function setChanged(bool $changed = true) : void{
        $this->changed = $changed;
    }

    public static function fixYAMLIndexes(string $str) : string{
        return preg_replace("#^( *)(y|Y|yes|Yes|YES|n|N|no|No|NO|true|True|TRUE|false|False|FALSE|on|On|ON|off|Off|OFF)( *)\:#m", "$1\"$2\"$3:", $str);
    }

    /**
     * @param mixed[] $default
     * @phpstan-param array<string, mixed> $default
     */
    public function load(string $file, int $type = Config::DETECT, array $default = []) : bool{
        $this->correct = true;
        $this->file = $file;

        $this->type = $type;
        if($this->type === Config::DETECT){
            $extension = explode(".", basename($this->file));
            $extension = strtolower(trim(array_pop($extension)));
            if(isset(Config::$formats[$extension])){
                $this->type = Config::$formats[$extension];
            }else{
                $this->correct = false;
            }
        }

        if(!file_exists($file)){
            $this->config = $default;
            $this->save();
        }else{
            if($this->correct){
                $content = file_get_contents($this->file);
                if($content === false){
                    $this->correct = false;
                    return false;
                }
                $config = null;
                switch($this->type){
                    case Config::PROPERTIES:
                        $config = $this->parseProperties($content);
                        break;
                    case Config::JSON:
                        $config = json_decode($content, true);
                        break;
                    case Config::YAML:
                        $content = self::fixYAMLIndexes($content);
                        $config = yaml_parse($content);
                        break;
                    case Config::SERIALIZED:
                        $config = unserialize($content);
                        break;
                    case Config::ENUM:
                        $config = self::parseList($content);
                        break;
                    default:
                        $this->correct = false;

                        return false;
                }
                $this->config = is_array($config) ? $config : $default;
                if($this->fillDefaults($default, $this->config) > 0){
                    $this->save();
                }
            }else{
                return false;
            }
        }

        return true;
    }

    public function check() : bool{
        return $this->correct;
    }

	public function save(bool $async = false) : bool{
		if($this->correct){
			try{
                $content = match ($this->type) {
                    Config::PROPERTIES, Config::CNF => $this->writeProperties(),
                    Config::JSON => json_encode($this->config, $this->jsonOptions),
                    Config::YAML => yaml_emit($this->config, YAML_UTF8_ENCODING),
                    Config::SERIALIZED => serialize($this->config),
                    Config::ENUM => implode("\r\n", array_keys($this->config)),
                    default => throw new \InvalidStateException("Config type is unknown, has not been set or not detected"),
                };

				if($async){
					Server::getInstance()->getScheduler()->scheduleAsyncTask(new FileWriteTask($this->file, $content));
				}else{
					file_put_contents($this->file, $content);
				}
			}catch(\Throwable $e){
				$logger = Server::getInstance()->getLogger();
				$logger->critical("Could not save Config " . $this->file . ": " . $e->getMessage());
				if(\pocketmine\DEBUG > 1){
					$logger->logException($e);
				}
			}

			return true;
		}else{
			return false;
		}
	}

    /**
     * Returns the path of the config.
     */
    public function getPath() : string{
        return $this->file;
    }

    /**
     * Sets the options for the JSON encoding when saving
     *
     * @return $this
     * @throws \RuntimeException if the Config is not in JSON
     * @see json_encode
     */
    public function setJsonOptions(int $options) : Config{
        if($this->type !== Config::JSON){
            throw new \RuntimeException("Attempt to set JSON options for non-JSON config");
        }
        $this->jsonOptions = $options;
        $this->changed = true;

        return $this;
    }

    /**
     * Enables the given option in addition to the currently set JSON options
     *
     * @return $this
     * @throws \RuntimeException if the Config is not in JSON
     * @see json_encode
     */
    public function enableJsonOption(int $option) : Config{
        if($this->type !== Config::JSON){
            throw new \RuntimeException("Attempt to enable JSON option for non-JSON config");
        }
        $this->jsonOptions |= $option;
        $this->changed = true;

        return $this;
    }

    /**
     * Disables the given option for the JSON encoding when saving
     *
     * @return $this
     * @throws \RuntimeException if the Config is not in JSON
     * @see json_encode
     */
    public function disableJsonOption(int $option) : Config{
        if($this->type !== Config::JSON){
            throw new \RuntimeException("Attempt to disable JSON option for non-JSON config");
        }
        $this->jsonOptions &= ~$option;
        $this->changed = true;

        return $this;
    }

    /**
     * Returns the options for the JSON encoding when saving
     *
     * @throws \RuntimeException if the Config is not in JSON
     * @see json_encode
     */
    public function getJsonOptions() : int{
        if($this->type !== Config::JSON){
            throw new \RuntimeException("Attempt to get JSON options for non-JSON config");
        }
        return $this->jsonOptions;
    }

    public function __get(string $k){
        return $this->get($k);
    }

    public function __set(string $k, mixed $v) : void{
        $this->set($k, $v);
    }

    public function __isset(string $k) : bool{
        return $this->exists($k);
    }

    public function __unset(string $k) : void{
        $this->remove($k);
    }

    public function setNested(string $key, mixed $value) : void{
        $vars = explode(".", $key);
        $base = array_shift($vars);

        if(!isset($this->config[$base])){
            $this->config[$base] = [];
        }

        $base = &$this->config[$base];

        while(count($vars) > 0){
            $baseKey = array_shift($vars);
            if(!isset($base[$baseKey])){
                $base[$baseKey] = [];
            }
            $base = &$base[$baseKey];
        }

        $base = $value;
        $this->nestedCache = [];
        $this->changed = true;
    }

    public function getNested(string $key, mixed $default = null) : mixed{
        if(isset($this->nestedCache[$key])){
            return $this->nestedCache[$key];
        }

        $vars = explode(".", $key);
        $base = array_shift($vars);
        if(isset($this->config[$base])){
            $base = $this->config[$base];
        }else{
            return $default;
        }

        while(count($vars) > 0){
            $baseKey = array_shift($vars);
            if(is_array($base) and isset($base[$baseKey])){
                $base = $base[$baseKey];
            }else{
                return $default;
            }
        }

        return $this->nestedCache[$key] = $base;
    }

    public function removeNested(string $key) : void{
        $this->nestedCache = [];
        $this->changed = true;

        $vars = explode(".", $key);

        $currentNode = &$this->config;
        while(count($vars) > 0){
            $nodeName = array_shift($vars);
            if(isset($currentNode[$nodeName])){
                if(count($vars) === 0){ //final node
                    unset($currentNode[$nodeName]);
                }elseif(is_array($currentNode[$nodeName])){
                    $currentNode = &$currentNode[$nodeName];
                }
            }else{
                break;
            }
        }
    }

    public function get(string $k, mixed $default = false){
        return ($this->correct and isset($this->config[$k])) ? $this->config[$k] : $default;
    }

    /**
     * @param string $k key to be set
     * @param mixed  $v value to set key
     */
    public function set(string $k, mixed $v = true) : void{
        $this->config[$k] = $v;
        $this->changed = true;
        foreach($this->nestedCache as $nestedKey => $nvalue){
            if(substr($nestedKey, 0, strlen($k) + 1) === ($k . ".")){
                unset($this->nestedCache[$nestedKey]);
            }
        }
    }

    /**
     * @param mixed[] $v
     * @phpstan-param array<string, mixed> $v
     *
     * @return void
     */
    public function setAll(array $v) : void{
        $this->config = $v;
        $this->changed = true;
    }

    /**
     * @param bool   $lowercase If set, searches Config in single-case / lowercase.
     */
    public function exists(string $k, bool $lowercase = false) : bool{
        if($lowercase){
            $k = strtolower($k); //Convert requested  key to lower
            $array = array_change_key_case($this->config, CASE_LOWER); //Change all keys in array to lower
            return isset($array[$k]); //Find $k in modified array
        }else{
            return isset($this->config[$k]);
        }
    }

    public function remove(string $k) : void{
        unset($this->config[$k]);
        $this->changed = true;
    }

    /**
     * @return mixed[]
     * @phpstan-return list<string>|array<string, mixed>
     */
    public function getAll(bool $keys = false) : array{
        return ($keys ? array_keys($this->config) : $this->config);
    }

    /**
     * @param mixed[] $defaults
     * @phpstan-param array<string, mixed> $defaults
     */
    public function setDefaults(array $defaults) : void{
        $this->fillDefaults($defaults, $this->config);
    }

    /**
     * @param mixed[] $default
     * @param mixed[] $data reference parameter
     * @phpstan-param array<string, mixed> $default
     * @phpstan-param array<string, mixed> $data
     */
    private function fillDefaults(array $default, &$data) : int{
        $changed = 0;
        foreach($default as $k => $v){
            if(is_array($v)){
                if(!isset($data[$k]) or !is_array($data[$k])){
                    $data[$k] = [];
                }
                $changed += $this->fillDefaults($v, $data[$k]);
            }elseif(!isset($data[$k])){
                $data[$k] = $v;
                ++$changed;
            }
        }

        if($changed > 0){
            $this->changed = true;
        }

        return $changed;
    }

    /**
     * @return true[]
     * @phpstan-return array<string, true>
     */
    private static function parseList(string $content) : array{
        $result = [];
        foreach(explode("\n", trim(str_replace("\r\n", "\n", $content))) as $v){
            $v = trim($v);
            if($v == ""){
                continue;
            }
            $result[$v] = true;
        }
        return $result;
    }

    private function writeProperties() : string{
        $content = "#Properties Config file\r\n#" . date("D M j H:i:s T Y") . "\r\n";
        foreach($this->config as $k => $v){
            if(is_bool($v)){
                $v = $v ? "on" : "off";
            }elseif(is_array($v)){
                $v = implode(";", $v);
            }
            $content .= $k . "=" . $v . "\r\n";
        }

        return $content;
    }

    private function parseProperties(string $content) : array{
        $result = [];
        if(preg_match_all('/^\s*([a-zA-Z0-9\-_\.]+)[ \t]*=([^\r\n]*)/um', $content, $matches) > 0){ //false or 0 matches
            foreach($matches[1] as $i => $k){
                $v = trim($matches[2][$i]);
                switch(strtolower($v)){
                    case "on":
                    case "true":
                    case "yes":
                        $v = true;
                        break;
                    case "off":
                    case "false":
                    case "no":
                        $v = false;
                        break;
                }
                if(isset($result[$k])){
                    \GlobalLogger::get()->debug("[Config] Repeated property " . $k . " on file " . $this->file);
                }
                $result[$k] = $v;
            }
        }

        return $result;
    }
}


