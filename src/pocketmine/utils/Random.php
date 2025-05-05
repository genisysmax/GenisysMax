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
 * XorShift128Engine Random Number Noise, used for fast seeded values
 * Most of the code in this class was adapted from the XorShift128Engine in the php-random library.
 */

use function time;

class Random{
    public const int X = 123456789;
    public const int Y = 362436069;
    public const int Z = 521288629;
    public const int W = 88675123;

    private int $x;
    private int $y;
    private int $z;
    private int $w;
    protected int $seed;

    /**
	 * @param int $seed Integer to be used as seed.
	 */
	public function __construct(int $seed = -1){
		if($seed === -1){
			$seed = time();
		}

		$this->setSeed($seed);
	}

    /**
     * @param int $seed Integer to be used as seed.
     */
    public function setSeed(int $seed) : void{
        $this->seed = $seed;
        $this->x = self::X ^ $seed;
        $this->y = self::Y ^ ($seed << 17) | (($seed >> 15) & 0x7fffffff) & 0xffffffff;
        $this->z = self::Z ^ ($seed << 31) | (($seed >> 1) & 0x7fffffff) & 0xffffffff;
        $this->w = self::W ^ ($seed << 18) | (($seed >> 14) & 0x7fffffff) & 0xffffffff;
    }

	public function getSeed() : int{
		return $this->seed;
	}

    /**
     * Returns an 31-bit integer (not signed)
     */
    public function nextInt() : int{
        return $this->nextSignedInt() & 0x7fffffff;
    }

    /**
     * Returns a 32-bit integer (signed)
     */
    public function nextSignedInt() : int{
        $t = ($this->x ^ ($this->x << 11)) & 0xffffffff;

        $this->x = $this->y;
        $this->y = $this->z;
        $this->z = $this->w;
        $this->w = ($this->w ^ (($this->w >> 19) & 0x7fffffff) ^ ($t ^ (($t >> 8) & 0x7fffffff))) & 0xffffffff;

        return $this->w;
    }

    /**
     * Returns a float between 0.0 and 1.0 (inclusive)
     */
    public function nextFloat() : float{
        return $this->nextInt() / 0x7fffffff;
    }

    /**
     * Returns a float between -1.0 and 1.0 (inclusive)
     */
    public function nextSignedFloat() : float{
        return $this->nextSignedInt() / 0x7fffffff;
    }

    /**
     * Returns a random boolean
     */
    public function nextBoolean() : bool{
        return ($this->nextSignedInt() & 0x01) === 0;
    }

    /**
     * Returns a random integer between $start and $end
     *
     * @param int $start default 0
     * @param int $end default 0x7fffffff
     */
    public function nextRange(int $start = 0, int $end = 0x7fffffff) : int{
        return $start + ($this->nextInt() % ($end + 1 - $start));
    }

    public function nextBoundedInt(int $bound) : int{
        return $this->nextInt() % $bound;
    }
}

