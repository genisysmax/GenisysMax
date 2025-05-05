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

namespace pocketmine\level\utils;

use function function_exists;
use function morton2d_decode;
use function morton2d_encode;
use function morton3d_decode;
use function morton3d_encode;

if(function_exists("morton3d_encode")){
	abstract class VectorHashUtils{

		public const Y_MAX = 0x100; //256

		public const HALF_Y_MAX = self::Y_MAX / 2;

		private const MORTON3D_BIT_SIZE = 21;
		private const BLOCKHASH_Y_BITS = 9;
		private const BLOCKHASH_Y_MASK = (1 << self::BLOCKHASH_Y_BITS) - 1;
		private const BLOCKHASH_XZ_MASK = (1 << self::MORTON3D_BIT_SIZE) - 1;
		private const BLOCKHASH_XZ_EXTRA_BITS = 6;
		private const BLOCKHASH_XZ_EXTRA_MASK = (1 << self::BLOCKHASH_XZ_EXTRA_BITS) - 1;
		private const BLOCKHASH_XZ_SIGN_SHIFT = 64 - self::MORTON3D_BIT_SIZE - self::BLOCKHASH_XZ_EXTRA_BITS;
		private const BLOCKHASH_X_SHIFT = self::BLOCKHASH_Y_BITS;
		private const BLOCKHASH_Z_SHIFT = self::BLOCKHASH_X_SHIFT + self::BLOCKHASH_XZ_EXTRA_BITS;

		public static function chunkHash(int $x, int $z){
			return morton2d_encode($x, $z);
		}

		public static function blockHash(int $x, int $y, int $z){
			$shiftedY = $y + self::HALF_Y_MAX;
			if(($shiftedY & (~0 << self::BLOCKHASH_Y_BITS)) !== 0){
				throw new \InvalidArgumentException("Y coordinate $y is out of range!");
			}

			//morton3d gives us 21 bits on each axis, but the Y axis only requires 9
			//so we use the extra space on Y (12 bits) and add 6 extra bits from X and Z instead.
			//if we ever need more space for Y (e.g. due to expansion), take bits from X/Z to compensate.
			return morton3d_encode(
				$x & self::BLOCKHASH_XZ_MASK,
				($shiftedY /* & self::BLOCKHASH_Y_MASK */) |
				((($x >> self::MORTON3D_BIT_SIZE) & self::BLOCKHASH_XZ_EXTRA_MASK) << self::BLOCKHASH_X_SHIFT) |
				((($z >> self::MORTON3D_BIT_SIZE) & self::BLOCKHASH_XZ_EXTRA_MASK) << self::BLOCKHASH_Z_SHIFT),
				$z & self::BLOCKHASH_XZ_MASK
			);
		}

		public static function getBlockXYZ($hash, &$x, &$y, &$z){
			[$baseX, $baseY, $baseZ] = morton3d_decode($hash);

			$extraX = ((($baseY >> self::BLOCKHASH_X_SHIFT) & self::BLOCKHASH_XZ_EXTRA_MASK) << self::MORTON3D_BIT_SIZE);
			$extraZ = ((($baseY >> self::BLOCKHASH_Z_SHIFT) & self::BLOCKHASH_XZ_EXTRA_MASK) << self::MORTON3D_BIT_SIZE);

			$x = (($baseX & self::BLOCKHASH_XZ_MASK) | $extraX) << self::BLOCKHASH_XZ_SIGN_SHIFT >> self::BLOCKHASH_XZ_SIGN_SHIFT;
			$y = ($baseY & self::BLOCKHASH_Y_MASK) - self::HALF_Y_MAX;
			$z = (($baseZ & self::BLOCKHASH_XZ_MASK) | $extraZ) << self::BLOCKHASH_XZ_SIGN_SHIFT >> self::BLOCKHASH_XZ_SIGN_SHIFT;
		}

		public static function getXZ($hash, &$x, &$z){
			[$x, $z] = morton2d_decode($hash);
		}
	}
}else{
	abstract class VectorHashUtils{

		public const Y_MASK = 0xFF;
		public const Y_MAX = 0x100; //256

		public static function chunkHash(int $x, int $z){
			return (($x & 0xFFFFFFFF) << 32) | ($z & 0xFFFFFFFF);
		}

		public static function blockHash(int $x, int $y, int $z){
			if($y < 0 or $y >= self::Y_MAX){
				throw new \InvalidArgumentException("Y coordinate $y is out of range!");
			}
			return (($x & 0xFFFFFFF) << 36) | (($y & self::Y_MASK) << 28) | ($z & 0xFFFFFFF);
		}

		public static function getBlockXYZ($hash, &$x, &$y, &$z){
			$x = $hash >> 36;
			$y = ($hash >> 28) & self::Y_MASK; //it's always positive
			$z = ($hash & 0xFFFFFFF) << 36 >> 36;
		}

		public static function getXZ($hash, &$x, &$z){
			$x = $hash >> 32;
			$z = ($hash & 0xFFFFFFFF) << 32 >> 32;
		}
	}
}


