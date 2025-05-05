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

class UUID{

    /** @var int[] */
	private array $parts;
	private int $version;

	public function __construct(int $part1 = 0, int $part2 = 0, int $part3 = 0, int $part4 = 0, int $version = null){
		$this->parts = [$part1, $part2, $part3, $part4];

		$this->version = $version ?? ($this->parts[1] & 0xf000) >> 12;
	}

	public function getVersion() : int{
		return $this->version;
	}

	public function equals(UUID $uuid) : bool{
		return $uuid->parts[0] === $this->parts[0] and $uuid->parts[1] === $this->parts[1] and $uuid->parts[2] === $this->parts[2] and $uuid->parts[3] === $this->parts[3];
	}

    /**
     * Creates an UUID from an hexadecimal representation
     */
    public static function fromString(string $uuid, int $version = null) : UUID{
        //TODO: should we be stricter about the notation (8-4-4-4-12)?
        $binary = @hex2bin(str_replace("-", "", trim($uuid)));
        if($binary === false){
            throw new \InvalidArgumentException("Invalid hex string UUID representation");
        }
        return self::fromBinary($binary, $version);
    }

    /**
     * Creates an UUID from a binary representation
     *
     * @throws \InvalidArgumentException
     */
    public static function fromBinary(string $uuid, int $version = null) : UUID{
        if(strlen($uuid) !== 16){
            throw new \InvalidArgumentException("Must have exactly 16 bytes");
        }

        return new UUID(Binary::readInt(substr($uuid, 0, 4)), Binary::readInt(substr($uuid, 4, 4)), Binary::readInt(substr($uuid, 8, 4)), Binary::readInt(substr($uuid, 12, 4)), $version);
    }

    /**
     * Creates an UUIDv3 from binary data or list of binary data
     */
    public static function fromData(string ...$data) : UUID{
        $hash = hash("md5", implode($data), true);

        return self::fromBinary($hash, 3);
    }

	public static function fromRandom() : UUID{
		return self::fromData(Binary::writeInt(time()), Binary::writeShort(getmypid()), Binary::writeShort(getmyuid()), Binary::writeInt(mt_rand(-0x7fffffff, 0x7fffffff)), Binary::writeInt(mt_rand(-0x7fffffff, 0x7fffffff)));
	}

	public function toBinary() : string{
		return Binary::writeInt($this->parts[0]) . Binary::writeInt($this->parts[1]) . Binary::writeInt($this->parts[2]) . Binary::writeInt($this->parts[3]);
	}

	public function toString(){
		$hex = bin2hex($this->toBinary());

		//xxxxxxxx-xxxx-Mxxx-Nxxx-xxxxxxxxxxxx 8-4-4-12
		return substr($hex, 0, 8) . "-" . substr($hex, 8, 4) . "-" . substr($hex, 12, 4) . "-" . substr($hex, 16, 4) . "-" . substr($hex, 20, 12);
	}

	public function __toString() : string{
		return $this->toString();
	}

    /**
     * @throws \InvalidArgumentException
     */
    public function getPart(int $partNumber){
        if($partNumber < 0 or $partNumber > 3){
            throw new \InvalidArgumentException("Invalid UUID part index $partNumber");
        }
        return $this->parts[$partNumber];
    }

    /**
     * @return int[]
     */
	public function getParts() : array{
		return $this->parts;
	}
}

