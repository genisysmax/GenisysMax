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

/**
 * Class representing a ray trace collision with an AxisAlignedBB
 */
class RayTraceResult{

    /**
     * @param int           $hitFace one of the Facing::* constants
     */
    public function __construct(
        public AxisAlignedBB $bb,
        public int $hitFace,
        public Vector3 $hitVector
    ){}

    public function getBoundingBox() : AxisAlignedBB{
        return $this->bb;
    }

    public function getHitFace() : int{
        return $this->hitFace;
    }

    public function getHitVector() : Vector3{
        return $this->hitVector;
    }
}

