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


namespace pocketmine\resourcepacks;


use function count;
use function fclose;
use function feof;
use function file_exists;
use function filesize;
use function fopen;
use function fread;
use function fseek;
use function hash_file;
use function implode;
use function json_decode;

class ZippedResourcePack implements ResourcePack{

	/**
	 * Performs basic validation checks on a resource pack's manifest.json.
	 * TODO: add more manifest validation
	 *
	 * @param \stdClass $manifest
	 * @return bool
	 */
	public static function verifyManifest(\stdClass $manifest) : bool{
		if(!isset($manifest->format_version) or !isset($manifest->header) or !isset($manifest->modules)){
			return false;
		}

		//Right now we don't care about anything else, only the stuff we're sending to clients.
		return
			isset($manifest->header->description) and
			isset($manifest->header->name) and
			isset($manifest->header->uuid) and
			isset($manifest->header->version) and
			count($manifest->header->version) === 3;
	}

	/** @var string */
	protected $path;

	/** @var \stdClass */
	protected $manifest;

	/** @var string */
	protected $sha256 = null;

	/** @var resource */
	protected $fileResource;

	/**
	 * @param string $zipPath Path to the resource pack zip
	 */
	public function __construct(string $zipPath){
		$this->path = $zipPath;

		if(!file_exists($zipPath)){
			throw new \InvalidArgumentException("Could not open resource pack $zipPath: file not found");
		}

		$archive = new \ZipArchive();
		if(($openResult = $archive->open($zipPath)) !== true){
			throw new \InvalidStateException("Encountered ZipArchive error code $openResult while trying to open $zipPath");
		}

		if(($manifestData = $archive->getFromName("manifest.json")) === false){
			if($archive->locateName("pack_manifest.json") !== false){
				throw new \InvalidStateException("Could not load resource pack from $zipPath: unsupported old pack format");
			}else{
				throw new \InvalidStateException("Could not load resource pack from $zipPath: manifest.json not found in the archive root");
			}
		}

		$archive->close();

		$manifest = json_decode($manifestData);
		if(!self::verifyManifest($manifest)){
			throw new \InvalidStateException("Could not load resource pack from $zipPath: manifest.json is invalid or incomplete");
		}

		$this->manifest = $manifest;

		$this->fileResource = fopen($zipPath, "rb");
	}

	public function __destruct(){
		fclose($this->fileResource);
	}

	public function getPackName() : string{
		return $this->manifest->header->name;
	}

	public function getPackVersion() : string{
		return implode(".", $this->manifest->header->version);
	}

	public function getPackId() : string{
		return $this->manifest->header->uuid;
	}

	public function getPackSize() : int{
		return filesize($this->path);
	}

	public function getSha256(bool $cached = true) : string{
		if($this->sha256 === null or !$cached){
			$this->sha256 = hash_file("sha256", $this->path, true);
		}
		return $this->sha256;
	}

	public function getPackChunk(int $start, int $length) : string{
		fseek($this->fileResource, $start);
		if(feof($this->fileResource)){
			throw new \RuntimeException("Requested a resource pack chunk with invalid start offset");
		}
		return fread($this->fileResource, $length);
	}
}

