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

namespace pocketmine\level\format\io\region;

use pocketmine\level\format\Chunk;
use pocketmine\level\format\ChunkException;
use pocketmine\utils\Binary;
use pocketmine\utils\Zlib;
use function array_fill;
use function ceil;
use function chr;
use function count;
use function fclose;
use function fgetc;
use function file_exists;
use function filesize;
use function fopen;
use function fread;
use function fseek;
use function ftruncate;
use function fwrite;
use function is_resource;
use function ksort;
use function ord;
use function pack;
use function str_pad;
use function stream_set_read_buffer;
use function stream_set_write_buffer;
use function strlen;
use function substr;
use function time;
use function touch;
use function unpack;
use const STR_PAD_RIGHT;
use const ZLIB_ENCODING_DEFLATE;

class RegionLoader{
	public const VERSION = 1;
	public const COMPRESSION_GZIP = 1;
	public const COMPRESSION_ZLIB = 2;

	public const MAX_SECTOR_LENGTH = 256 << 12; //256 sectors, (1 MiB)
	public const REGION_HEADER_LENGTH = 8192; //4096 location table + 4096 timestamps
	public const MAX_REGION_FILE_SIZE = 32 * 32 * self::MAX_SECTOR_LENGTH + self::REGION_HEADER_LENGTH; //32 * 32 1MiB chunks + header size

	public static $COMPRESSION_LEVEL = 7;

	protected $x;
	protected $z;
	protected $filePath;
	protected $filePointer;
	protected $lastSector;
	/** @var McRegion */
	protected $levelProvider;
	protected $locationTable = [];

	public $lastUsed;

	public function __construct(McRegion $level, int $regionX, int $regionZ, string $fileExtension = McRegion::REGION_FILE_EXTENSION){
		$this->x = $regionX;
		$this->z = $regionZ;
		$this->levelProvider = $level;
		$this->filePath = $this->levelProvider->getPath() . "region/r.$regionX.$regionZ.$fileExtension";
	}

	public function open(){
		$exists = file_exists($this->filePath);
		if(!$exists){
			touch($this->filePath);
		}else{
			$fileSize = filesize($this->filePath);
			if($fileSize > self::MAX_REGION_FILE_SIZE){
				throw new CorruptedRegionException("Corrupted oversized region file found, should be a maximum of " . self::MAX_REGION_FILE_SIZE . " bytes, got " . $fileSize . " bytes");
			}elseif($fileSize % 4096 !== 0){
				throw new CorruptedRegionException("Region file should be padded to a multiple of 4KiB");
			}
		}

		$this->filePointer = fopen($this->filePath, "r+b");
		stream_set_read_buffer($this->filePointer, 1024 * 16); //16KB
		stream_set_write_buffer($this->filePointer, 1024 * 16); //16KB
		if(!$exists){
			$this->createBlank();
		}else{
			$this->loadLocationTable();
		}

		$this->lastUsed = time();
	}

	public function __destruct(){
		if(is_resource($this->filePointer)){
			$this->writeLocationTable();
			fclose($this->filePointer);
		}
	}

	protected function isChunkGenerated(int $index) : bool{
		return !($this->locationTable[$index][0] === 0 or $this->locationTable[$index][1] === 0);
	}

	public function readChunk(int $x, int $z){
		$index = self::getChunkOffset($x, $z);
		if($index < 0 or $index >= 4096){
			return null;
		}

		$this->lastUsed = time();

		if(!$this->isChunkGenerated($index)){
			return null;
		}

		fseek($this->filePointer, $this->locationTable[$index][0] << 12);
		$length = Binary::readInt(fread($this->filePointer, 4));
		$compression = ord(fgetc($this->filePointer));

		if($length <= 0 or $length > self::MAX_SECTOR_LENGTH){ //Not yet generated / corrupted
			if($length >= self::MAX_SECTOR_LENGTH){
				$this->locationTable[$index][0] = ++$this->lastSector;
				$this->locationTable[$index][1] = 1;
				\GlobalLogger::get()->error("Corrupted chunk header detected");
			}
			return null;
		}

		if($length > ($this->locationTable[$index][1] << 12)){ //Invalid chunk, bigger than defined number of sectors
			\GlobalLogger::get()->error("Corrupted bigger chunk detected");
			$this->locationTable[$index][1] = $length >> 12;
			$this->writeLocationIndex($index);
		}elseif($compression !== self::COMPRESSION_ZLIB and $compression !== self::COMPRESSION_GZIP){
			\GlobalLogger::get()->error("Invalid compression type");
			return null;
		}

		$chunk = $this->levelProvider->nbtDeserialize(fread($this->filePointer, $length - 1));
		if($chunk instanceof Chunk){
			return $chunk;
		}else{
			\GlobalLogger::get()->error("Corrupted chunk detected");
			return null;
		}
	}

	public function chunkExists(int $x, int $z) : bool{
		return $this->isChunkGenerated(self::getChunkOffset($x, $z));
	}

	protected function saveChunk(int $x, int $z, string $chunkData){
		$length = strlen($chunkData) + 1;
		if($length + 4 > self::MAX_SECTOR_LENGTH){
			throw new ChunkException("Chunk is too big! " . ($length + 4) . " > " . self::MAX_SECTOR_LENGTH);
		}
		$sectors = (int) ceil(($length + 4) / 4096);
		$index = self::getChunkOffset($x, $z);
		$indexChanged = false;
		if($this->locationTable[$index][1] < $sectors){
			$this->locationTable[$index][0] = $this->lastSector + 1;
			$this->lastSector += $sectors; //The GC will clean this shift "later"
			$indexChanged = true;
		}elseif($this->locationTable[$index][1] != $sectors){
			$indexChanged = true;
		}

		$this->locationTable[$index][1] = $sectors;
		$this->locationTable[$index][2] = time();

		fseek($this->filePointer, $this->locationTable[$index][0] << 12);
		fwrite($this->filePointer, str_pad(Binary::writeInt($length) . chr(self::COMPRESSION_ZLIB) . $chunkData, $sectors << 12, "\x00", STR_PAD_RIGHT));

		if($indexChanged){
			$this->writeLocationIndex($index);
		}
	}

	public function removeChunk(int $x, int $z){
		$index = self::getChunkOffset($x, $z);
		$this->locationTable[$index][0] = 0;
		$this->locationTable[$index][1] = 0;
	}

	public function writeChunk(Chunk $chunk){
		$this->lastUsed = time();
		$chunkData = $this->levelProvider->nbtSerialize($chunk);
		if($chunkData !== false){
			$this->saveChunk($chunk->getX() - ($this->getX() * 32), $chunk->getZ() - ($this->getZ() * 32), $chunkData);
		}
	}

	protected static function getChunkOffset(int $x, int $z) : int{
		return $x + ($z << 5);
	}

	/**
	 * Writes the region header and closes the file
	 *
	 * @param bool $writeHeader
	 */
	public function close(bool $writeHeader = true){
		if(is_resource($this->filePointer)){
			if($writeHeader){
				$this->writeLocationTable();
			}

			fclose($this->filePointer);
		}

		$this->levelProvider = null;
	}

	public function doSlowCleanUp() : int{
		for($i = 0; $i < 1024; ++$i){
			if($this->locationTable[$i][0] === 0 or $this->locationTable[$i][1] === 0){
				continue;
			}
			fseek($this->filePointer, $this->locationTable[$i][0] << 12);
			$chunk = fread($this->filePointer, $this->locationTable[$i][1] << 12);
			$length = Binary::readInt(substr($chunk, 0, 4));
			if($length <= 1){
				$this->locationTable[$i] = [0, 0, 0]; //Non-generated chunk, remove it from index
			}

			try{
				$chunk = Zlib::decompress(substr($chunk, 5));
			}catch(\Throwable $e){
				$this->locationTable[$i] = [0, 0, 0]; //Corrupted chunk, remove it
				continue;
			}

			$chunk = chr(self::COMPRESSION_ZLIB) . Zlib::compress($chunk, ZLIB_ENCODING_DEFLATE, 9);
			$chunk = Binary::writeInt(strlen($chunk)) . $chunk;
			$sectors = (int) ceil(strlen($chunk) / 4096);
			if($sectors > $this->locationTable[$i][1]){
				$this->locationTable[$i][0] = $this->lastSector + 1;
				$this->lastSector += $sectors;
			}
			fseek($this->filePointer, $this->locationTable[$i][0] << 12);
			fwrite($this->filePointer, str_pad($chunk, $sectors << 12, "\x00", STR_PAD_RIGHT));
		}
		$this->writeLocationTable();
		$n = $this->cleanGarbage();
		$this->writeLocationTable();

		return $n;
	}

	private function cleanGarbage() : int{
		$sectors = [];
		foreach($this->locationTable as $index => $data){ //Calculate file usage
			if($data[0] === 0 or $data[1] === 0){
				$this->locationTable[$index] = [0, 0, 0];
				continue;
			}
			for($i = 0; $i < $data[1]; ++$i){
				$sectors[$data[0]] = $index;
			}
		}

		if(count($sectors) === ($this->lastSector - 2)){ //No collection needed
			return 0;
		}

		ksort($sectors);
		$shift = 0;
		$lastSector = 1; //First chunk - 1

		fseek($this->filePointer, 8192);
		$sector = 2;
		foreach($sectors as $sector => $index){
			if(($sector - $lastSector) > 1){
				$shift += $sector - $lastSector - 1;
			}
			if($shift > 0){
				fseek($this->filePointer, $sector << 12);
				$old = fread($this->filePointer, 4096);
				fseek($this->filePointer, ($sector - $shift) << 12);
				fwrite($this->filePointer, $old, 4096);
			}
			$this->locationTable[$index][0] -= $shift;
			$lastSector = $sector;
		}
		ftruncate($this->filePointer, ($sector + 1) << 12); //Truncate to the end of file written
		return $shift;
	}

	protected function loadLocationTable(){
		fseek($this->filePointer, 0);
		$this->lastSector = 1;

		$headerRaw = fread($this->filePointer, self::REGION_HEADER_LENGTH);
		if(($len = strlen($headerRaw)) !== self::REGION_HEADER_LENGTH){
			throw new CorruptedRegionException("Invalid region file header, expected " . self::REGION_HEADER_LENGTH . " bytes, got " . $len . " bytes");
		}

		$data = unpack("N*", $headerRaw);
		$usedOffsets = [];
		for($i = 0; $i < 1024; ++$i){
			$index = $data[$i + 1];
			$offset = $index >> 8;
			if($offset !== 0){
				fseek($this->filePointer, $offset << 12);
				if(fgetc($this->filePointer) === false){ //Try and read from the location
					throw new CorruptedRegionException("Region file location offset points to invalid location");
				}elseif(isset($usedOffsets[$offset])){
					throw new CorruptedRegionException("Found two chunk offsets pointing to the same location");
				}else{
					$usedOffsets[$offset] = true;
				}
			}

			$this->locationTable[$i] = [$index >> 8, $index & 0xff, $data[1024 + $i + 1]];
			if(($this->locationTable[$i][0] + $this->locationTable[$i][1] - 1) > $this->lastSector){
				$this->lastSector = $this->locationTable[$i][0] + $this->locationTable[$i][1] - 1;
			}
		}

		fseek($this->filePointer, 0);
	}

	private function writeLocationTable(){
		$write = [];

		for($i = 0; $i < 1024; ++$i){
			$write[] = (($this->locationTable[$i][0] << 8) | $this->locationTable[$i][1]);
		}
		for($i = 0; $i < 1024; ++$i){
			$write[] = $this->locationTable[$i][2];
		}
		fseek($this->filePointer, 0);
		fwrite($this->filePointer, pack("N*", ...$write), 4096 * 2);
	}

	protected function writeLocationIndex($index){
		fseek($this->filePointer, $index << 2);
		fwrite($this->filePointer, Binary::writeInt(($this->locationTable[$index][0] << 8) | $this->locationTable[$index][1]), 4);
		fseek($this->filePointer, 4096 + ($index << 2));
		fwrite($this->filePointer, Binary::writeInt($this->locationTable[$index][2]), 4);
	}

	protected function createBlank(){
		fseek($this->filePointer, 0);
		ftruncate($this->filePointer, 8192); // this fills the file with the null byte
		$this->lastSector = 1;
		$this->locationTable = array_fill(0, 1024, [0, 0, 0]);
	}

	public function getX() : int{
		return $this->x;
	}

	public function getZ() : int{
		return $this->z;
	}

	public function getFilePath() : string{
		return $this->filePath;
	}
}


