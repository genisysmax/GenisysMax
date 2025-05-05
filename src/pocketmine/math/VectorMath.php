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

namespace pocketmine\math;

use function cos;
use function sin;

abstract class VectorMath{

    private function __construct(){
        //NOOP
    }

    public static function getDirection2D(float $azimuth) : Vector2{
        return new Vector2(cos($azimuth), sin($azimuth));
    }

    /**
     * @param $azimuth
     * @param $inclination
     *
     * @return Vector3
     */
    public static function getDirection3D($azimuth, $inclination) : Vector3{
        $yFact = cos($inclination);
        return new Vector3($yFact * cos($azimuth), sin($inclination), $yFact * sin($azimuth));
    }
}

