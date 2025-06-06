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

use pocketmine\utils\Random;
use function abs;
use function round;
use function sqrt;

class Vector2{
    public function __construct(
        public float $x,
        public float $y
    ){}

    public function getX() : float{
        return $this->x;
    }

    public function getY() : float{
        return $this->y;
    }

    public function getFloorX() : int{
        return (int) floor($this->x);
    }

    public function getFloorY() : int{
        return (int) floor($this->y);
    }

    /**
     * @param Vector2|float $x
     * @param float $y
     *
     * @return Vector2
     */
    public function add($x, float $y = 0){
        if($x instanceof Vector2){
            return $this->add($x->x, $x->y);
        }else{
            return new Vector2($this->x + $x, $this->y + $y);
        }
    }

    /**
     * @param Vector2|float $x
     * @param float $y
     *
     * @return Vector2
     */
    public function subtract($x, float $y = 0){
        if($x instanceof Vector2){
            return $this->add(-$x->x, -$x->y);
        }else{
            return $this->add(-$x, -$y);
        }
    }

    public function addVector(Vector2 $vector2) : Vector2{
        return $this->add($vector2->x, $vector2->y);
    }

    public function subtractVector(Vector2 $vector2) : Vector2{
        return $this->add(-$vector2->x, -$vector2->y);
    }

    public function ceil() : Vector2{
        return new Vector2((int) ceil($this->x), (int) ceil($this->y));
    }

    public function floor() : Vector2{
        return new Vector2((int) floor($this->x), (int) floor($this->y));
    }

    public function round() : Vector2{
        return new Vector2(round($this->x), round($this->y));
    }

    public function abs() : Vector2{
        return new Vector2(abs($this->x), abs($this->y));
    }

    public function multiply(float $number) : Vector2{
        return new Vector2($this->x * $number, $this->y * $number);
    }

    public function divide(float $number) : Vector2{
        return new Vector2($this->x / $number, $this->y / $number);
    }

    /**
     * @param     $x
     * @param float $y
     *
     * @return float
     */
    public function distance($x, float $y = 0) : float{
        if($x instanceof Vector2){
            return sqrt($this->distanceSquared($x->x, $x->y));
        }else{
            return sqrt($this->distanceSquared($x, $y));
        }
    }

    /**
     * @param     $x
     * @param float $y
     *
     * @return number
     */
    public function distanceSquared($x, float $y = 0) : float{
        if($x instanceof Vector2){
            return $this->distanceSquared($x->x, $x->y);
        }else{
            return (($this->x - $x) ** 2) + (($this->y - $y) ** 2);
        }
    }

    public function length() : float{
        return sqrt($this->lengthSquared());
    }

    public function lengthSquared() : float{
        return $this->x * $this->x + $this->y * $this->y;
    }

    public function normalize() : Vector2{
        $len = $this->lengthSquared();
        if($len > 0){
            return $this->divide(sqrt($len));
        }

        return new Vector2(0, 0);
    }

    public function dot(Vector2 $v) : float{
        return $this->x * $v->x + $this->y * $v->y;
    }

    /**
     * @param Random $random
     *
     * @return Vector2
     */
    public static function createRandomDirection(Random $random){
        return VectorMath::getDirection2D($random->nextFloat() * 2 * pi());
    }

    public function __toString(){
        return "Vector2(x=" . $this->x . ",y=" . $this->y . ")";
    }
}

