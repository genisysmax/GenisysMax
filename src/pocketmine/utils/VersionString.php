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

/**
 * Manages PocketMine-MP version strings, and compares them
 */

use InvalidArgumentException;

class VersionString{
    private string $baseVersion;
    private string $suffix;

    private int $major;
    private int $minor;
    private int $patch;

    private int $build;
    private bool $development;

    public function __construct(string $baseVersion, bool $isDevBuild = false, int $buildNumber = 0){
        $this->baseVersion = $baseVersion;
        $this->development = $isDevBuild;
        $this->build = $buildNumber;

        preg_match('/^(\d+)\.(\d+)\.(\d+)(?:-(.*))?$/', $this->baseVersion, $matches);
        if(count($matches) < 4){
            throw new InvalidArgumentException("Invalid base version \"$baseVersion\", should contain at least 3 version digits");
        }

        $this->major = (int) $matches[1];
        $this->minor = (int) $matches[2];
        $this->patch = (int) $matches[3];
        $this->suffix = $matches[4] ?? "";
    }

    public function getNumber() : int{
        return (($this->major << 9) | ($this->minor << 5) | $this->patch);
    }

    public function getBaseVersion() : string{
        return $this->baseVersion;
    }

    public function getFullVersion(bool $build = false) : string{
        $retval = $this->baseVersion;
        if($this->development){
            $retval .= "+dev";
            if($build and $this->build > 0){
                $retval .= "." . $this->build;
            }
        }

        return $retval;
    }

    public function getMajor() : int{
        return $this->major;
    }

    public function getMinor() : int{
        return $this->minor;
    }

    public function getPatch() : int{
        return $this->patch;
    }

    public function getSuffix() : string{
        return $this->suffix;
    }

    public function getBuild() : int{
        return $this->build;
    }

    public function isDev() : bool{
        return $this->development;
    }

    public function __toString() : string{
        return $this->getFullVersion();
    }

    public function compare(VersionString $target, bool $diff = false) : int{
        $number = $this->getNumber();
        $tNumber = $target->getNumber();
        if($diff){
            return $tNumber - $number;
        }
        if($number > $tNumber){
            return -1; //Target is older
        }elseif($number < $tNumber){
            return 1; //Target is newer
        }elseif($target->isDev() and !$this->isDev()){
            return -1; //Dev builds of the same version are always considered older than a release
        }elseif($target->getBuild() > $this->getBuild()){
            return 1;
        }elseif($target->getBuild() < $this->getBuild()){
            return -1;
        }else{
            return 0; //Same version
        }
    }
}

