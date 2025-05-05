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

use function str_repeat;
use function strlen;
use function trim;

final class Git{

	private function __construct(){
		//NOOP
	}

    /**
     * Returns the git hash of the currently checked out head of the given repository, or null on failure.
     *
     * @param bool   $dirty reference parameter, set to whether the repo has local changes
     */
    public static function getRepositoryState(string $dir, bool &$dirty) : ?string{
        if(Utils::execute("git -C \"$dir\" rev-parse HEAD", $out) === 0 and $out !== false and strlen($out = trim($out)) === 40){
            if(Utils::execute("git -C \"$dir\" diff --quiet") === 1 or Utils::execute("git -C \"$dir\" diff --cached --quiet") === 1){ //Locally-modified
                $dirty = true;
            }
            return $out;
        }
        return null;
    }

    /**
     * Infallible, returns a string representing git state, or a string of zeros on failure.
     * If the repo is dirty, a "-dirty" suffix is added.
     */
    public static function getRepositoryStatePretty(string $dir) : string{
        $dirty = false;
        $detectedHash = self::getRepositoryState($dir, $dirty);
        if($detectedHash !== null){
            return $detectedHash . ($dirty ? "-dirty" : "");
        }
        return str_repeat("00", 20);
    }
}


