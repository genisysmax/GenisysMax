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

use function abs;
use function date_default_timezone_set;
use function date_parse;
use function exec;
use function file_get_contents;
use function implode;
use function ini_get;
use function ini_set;
use function is_array;
use function is_string;
use function json_decode;
use function parse_ini_file;
use function preg_match;
use function readlink;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function substr;
use function timezone_abbreviations_list;
use function timezone_name_from_abbr;
use function trim;

abstract class Timezone{

    public static function get() : string{
        $tz = ini_get('date.timezone');
        if($tz === false){
            throw new AssumptionFailedError('date.timezone INI entry should always exist');
        }
        return $tz;
    }

    /**
     * @return string[]
     */
    public static function init() : array{
        $messages = [];
        $timezone = self::get();
        if ($timezone !== "") {
            /*
             * This is here so that people don't come to us complaining and fill up the issue tracker when they put
             * an incorrect timezone abbreviation in php.ini apparently.
             */
            if (strpos($timezone, "/") === false) {
                $default_timezone = timezone_name_from_abbr($timezone);
                if ($default_timezone !== false) {
                    ini_set("date.timezone", $default_timezone);
                    date_default_timezone_set($default_timezone);
                    return $messages;
                }
                //Bad php.ini value, try another method to detect timezone
                $messages[] = "Timezone \"$timezone\" could not be parsed as a valid timezone from php.ini, falling back to auto-detection";
            } else {
                date_default_timezone_set($timezone);
                return $messages;
            }
        }

        if (($timezone = self::detectSystemTimezone()) !== false and date_default_timezone_set($timezone)) {
            //Success! Timezone has already been set and validated in the if statement.
            //This here is just for redundancy just in case some program wants to read timezone data from the ini.
            ini_set("date.timezone", $timezone);
            return $messages;
        }

        if (($response = Internet::getURL("http://ip-api.com/json")) !== false //If system timezone detection fails or timezone is an invalid value.
            and is_array($ip_geolocation_data = json_decode($response, true))
            and isset($ip_geolocation_data['status'])
            and $ip_geolocation_data['status'] !== 'fail'
            and is_string($ip_geolocation_data['timezone'])
            and date_default_timezone_set($ip_geolocation_data['timezone'])
        ) {
            //Again, for redundancy.
            ini_set("date.timezone", $ip_geolocation_data['timezone']);
            return $messages;
        }

        ini_set("date.timezone", "UTC");
        date_default_timezone_set("UTC");
        $messages[] = "Timezone could not be automatically determined or was set to an invalid value. An incorrect timezone will result in incorrect timestamps on console logs. It has been set to \"UTC\" by default. You can change it on the php.ini file.";

        return $messages;
    }

	public static function detectSystemTimezone() : string|false{
		switch(Utils::getOS()){
			case OS::WINDOWS:
                $regex = '/(UTC)(\+*\-*\d*\d*\:*\d*\d*)/';

				/*
				 * wmic timezone get Caption
				 * Get the timezone offset
				 *
				 * Sample Output var_dump
				 * array(3) {
				 *	  [0] =>
				 *	  string(7) "Caption"
				 *	  [1] =>
				 *	  string(20) "(UTC+09:30) Adelaide"
				 *	  [2] =>
				 *	  string(0) ""
				 *	}
				 */
                exec("wmic timezone get Caption", $output);

                $string = trim(implode("\n", $output));

                //Detect the Time Zone string
                preg_match($regex, $string, $matches);

                if(!isset($matches[2])){
                    return false;
                }

                $offset = $matches[2];

                if($offset == ""){
                    return "UTC";
                }

                return self::parseOffset($offset);
			case OS::LINUX:
                // Ubuntu / Debian.
                $data = @file_get_contents('/etc/timezone');
                if($data !== false){
                    return trim($data);
                }

                // RHEL / CentOS
                $data = @parse_ini_file('/etc/sysconfig/clock');
                if($data !== false and isset($data['ZONE']) and is_string($data['ZONE'])){
                    return trim($data['ZONE']);
                }

                //Portable method for incompatible linux distributions.

                $offset = trim(exec('date +%:z'));

                if($offset == "+00:00"){
                    return "UTC";
                }

                return self::parseOffset($offset);
            case OS::MACOS:
                $filename = @readlink('/etc/localtime');
                if($filename !== false and strpos($filename, '/usr/share/zoneinfo/') === 0){
                    $timezone = substr($filename, 20);
                    return trim($timezone);
                }

                return false;
			default:
				return false;
		}
	}

	/**
	 * @param string $offset In the format of +09:00, +02:00, -04:00 etc.
	 */
	private static function parseOffset(string $offset) : string|false{
        //Make signed offsets unsigned for date_parse
        if(strpos($offset, '-') !== false){
            $negative_offset = true;
            $offset = str_replace('-', '', $offset);
        }else{
            if(strpos($offset, '+') !== false){
                $negative_offset = false;
                $offset = str_replace('+', '', $offset);
            }else{
                return false;
            }
        }

        $parsed = date_parse($offset);
        $offset = $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];

        //After date_parse is done, put the sign back
        if($negative_offset == true){
            $offset = -abs($offset);
        }

        //And then, look the offset up.
        //timezone_name_from_abbr is not used because it returns false on some(most) offsets because it's mapping function is weird.
        //That's been a bug in PHP since 2008!
        foreach(timezone_abbreviations_list() as $zones){
            foreach($zones as $timezone){
                if($timezone['offset'] == $offset){
                    return $timezone['timezone_id'];
                }
            }
        }

        return false;
    }
}


