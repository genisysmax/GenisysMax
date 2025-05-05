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


class Color{

    /** @var int */
    protected $a;
    /** @var int */
    protected $r;
    /** @var int */
    protected $g;
    /** @var int */
    protected $b;

	public function __construct(int $r, int $g, int $b, int $a = 0xff){
		$this->r = $r & 0xff;
		$this->g = $g & 0xff;
		$this->b = $b & 0xff;
		$this->a = $a & 0xff;
	}

	/**
	 * Returns the alpha (transparency) value of this colour.
	 */
	public function getA() : int{
		return $this->a;
	}

	/**
	 * Sets the alpha (opacity) value of this colour, lower = more transparent
	 */
	public function setA(int $a) : void{
		$this->a = $a & 0xff;
	}

	/**
	 * Retuns the red value of this colour.
	 */
	public function getR() : int{
		return $this->r;
	}

	/**
	 * Sets the red value of this colour.
	 */
	public function setR(int $r) : void{
		$this->r = $r & 0xff;
	}

	/**
	 * Returns the green value of this colour.
	 */
	public function getG() : int{
		return $this->g;
	}

	/**
	 * Sets the green value of this colour.
	 */
	public function setG(int $g) : void{
		$this->g = $g & 0xff;
	}

	/**
	 * Returns the blue value of this colour.
	 */
	public function getB() : int{
		return $this->b;
	}

	/**
	 * Sets the blue value of this colour.
	 */
	public function setB(int $b) : void{
		$this->b = $b & 0xff;
	}

	/**
	 * Returns a Color from the supplied RGB colour code (24-bit)
	 */
	public static function fromRGB(int $code) : self{
		return new Color(($code >> 16) & 0xff, ($code >> 8) & 0xff, $code & 0xff);
	}

    public static function getRGB(int $r, int $g, int $b) : self{
        return new Color($r, $g, $b);
    }

    /**
	 * Returns a Color from the supplied ARGB colour code (32-bit)
	 */
	public static function fromARGB(int $code) : self{
		return new Color(($code >> 16) & 0xff, ($code >> 8) & 0xff, $code & 0xff, ($code >> 24) & 0xff);
	}

	/**
	 * Returns a Color from the supplied RGBA colour code (32-bit)
	 */
	public static function fromRGBA(int $code) : self{
		return new Color(($code >> 24) & 0xff, ($code >> 16) & 0xff, ($code >> 8) & 0xff, $code & 0xff);
	}

	/**
	 * Returns an ARGB 32-bit colour value.
	 */
	public function toARGB() : int{
		return ($this->a << 24) | ($this->r << 16) | ($this->g << 8) | $this->b;
	}


	/**
	 * Returns an RGB 24-bit colour value.
	 */
	public function toRGB() : int{
		return ($this->r << 16) | ($this->g << 8) | $this->b;
	}

	/**
	 * Returns a little-endian ARGB 32-bit colour value.
	 */
	public function toBGRA() : int{
		return ($this->b << 24) | ($this->g << 16) | ($this->r << 8) | $this->a;
	}

	/**
	 * Returns an RGBA 32-bit colour value.
	 */
	public function toRGBA() : int{
		return ($this->r << 24) | ($this->g << 16) | ($this->b << 8) | $this->a;
	}

	/**
	 * Returns a little-endian RGBA colour value.
	 */
	public function toABGR() : int{
		return ($this->a << 24) | ($this->b << 16) | ($this->g << 8) | $this->r;
	}

	public static function fromABGR(int $code) : self{
		return new Color($code & 0xff, ($code >> 8) & 0xff, ($code >> 16) & 0xff, ($code >> 24) & 0xff);
	}

    /**
     * Mixes the supplied list of colours together to produce a result colour.
     */
    public static function mix(Color $color1, Color ...$colors) : Color{
        $colors[] = $color1;
        $count = count($colors);

        $a = $r = $g = $b = 0;

        foreach($colors as $color){
            $a += $color->a;
            $r += $color->r;
            $g += $color->g;
            $b += $color->b;
        }

        return new Color(intdiv($r, $count), intdiv($g, $count), intdiv($b, $count), intdiv($a, $count));
    }

}

