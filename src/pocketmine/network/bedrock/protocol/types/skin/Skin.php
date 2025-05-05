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

namespace pocketmine\network\bedrock\protocol\types\skin;

use Ahc\Json\Comment as CommentedJsonDecoder;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\utils\UUID;
use function file_get_contents;
use function str_repeat;

class Skin{

	/** @var self|null */
	protected static $cachedEmpty;
	
	public static function empty() : self{
		return self::$cachedEmpty = self::$cachedEmpty ?? (new Skin(
			UUID::fromRandom()->toString(),
			"",
			'{"geometry":{"default":"geometry.empty"}}',
			SerializedSkinImage::fromLegacyImageData(str_repeat("\x00", 8192)),
			[],
			SerializedSkinImage::empty(),
			file_get_contents(\pocketmine\RESOURCE_PATH . "bedrock/geometry_empty.json")
		));
	}

	/** @var string */
	protected $skinId;
	/** @var string */
	protected $playFabId;
	/** @var string */
	protected $skinResourcePatch;
	/** @var SerializedSkinImage */
	protected $skinImage;
	/** @var SkinAnimation[] */
	protected $animations;
	/** @var SerializedSkinImage */
	protected $capeImage;
	/** @var string */
	protected $geometryData;
    /** @var string */
    protected $geometryDataEngineVersion;
    /** @var string */
	protected $animationData;
	/** @var string */
	protected $capeId;
	/** @var string */
	protected $fullSkinId;
	/** @var string */
	protected $armSize;
	/** @var string */
	protected $skinColor;
	/** @var PersonaPiece[] */
	protected $personaPieces;
	/** @var PieceTintColor[] */
	protected $pieceTintColors;
	/** @var bool */
	protected $isVerified;
    /** @var bool */
    protected $isPremium;
    /** @var bool */
    protected $isPersona;
    /** @var bool */
    protected $isCapeOnClassic;
    /** @var bool */
    protected $isPrimaryUser;
	/** @var bool */
	protected $isOverride;

	/**
	 * @param string $skinId
	 * @param string $skinResourcePatch
	 * @param SerializedSkinImage $skinImage
	 * @param SkinAnimation[] $animations
	 * @param SerializedSkinImage|null $capeImage
	 * @param string $geometryData
	 * @param string $animationData
	 * @param bool $isPremium
	 * @param bool $isPersona
	 * @param bool $isCapeOnClassic
	 * @param string $capeId
	 * @param string|null $fullSkinId
	 * @param string $armSize
	 * @param string $skinColor
	 * @param PersonaPiece[] $personaPieces
	 * @param PieceTintColor[] $pieceTintColors
	 * @param bool $isVerified
	 * @param bool $isOverride
	 */
	public function __construct(string $skinId, string $playFabId, string $skinResourcePatch, SerializedSkinImage $skinImage, array $animations = [], ?SerializedSkinImage $capeImage = null, string $geometryData = "", string $animationData = "", bool $isPremium = false, bool $isPersona = false, bool $isCapeOnClassic = false, string $capeId = "", ?string $fullSkinId = null, string $armSize = "wide", string $skinColor = "#0", array $personaPieces = [], array $pieceTintColors = [], bool $isVerified = true, string $geometryDataEngineVersion = ProtocolInfo::MINECRAFT_VERSION_NETWORK, bool $isPrimaryUser = true, bool $isOverride = true){
		(static function(SkinAnimation ...$_) : void{})(...$animations); // Type check
		(static function(PersonaPiece ...$_) : void{})(...$personaPieces); // Type check
		(static function(PieceTintColor ...$_) : void{})(...$pieceTintColors); // Type check

		$this->skinId = $skinId;
		$this->playFabId = $playFabId;
		$this->skinResourcePatch = $skinResourcePatch;
		$this->skinImage = $skinImage;
		$this->animations = $animations;
		$this->capeImage = $capeImage ?? SerializedSkinImage::empty();
		$this->geometryData = $geometryData;
		$this->animationData = $animationData;
		$this->isPremium = $isPremium;
		$this->isPersona = $isPersona;
		$this->isCapeOnClassic = $isCapeOnClassic;
		$this->capeId = $capeId;
		$this->fullSkinId = $fullSkinId ?? ($this->skinId . "_" . $this->capeId);
		$this->armSize = $armSize;
		$this->skinColor = $skinColor;
		$this->personaPieces = $personaPieces;
		$this->pieceTintColors = $pieceTintColors;
		$this->isVerified = $isVerified;
        $this->geometryDataEngineVersion = $geometryDataEngineVersion;
        $this->isPrimaryUser = $isPrimaryUser;
		$this->isOverride = true; // TODO: $isOverride = false makes skins buggy

		$this->debloatGeometryData();
		$this->generateFullSkinId();
	}

	/**
	 * @return bool
	 */
	public function isValid() : bool{
		if(!$this->skinImage->isValid() or !$this->capeImage->isValid()){
			return false;
		}

		foreach($this->animations as $animation){
			if(!$animation->getImage()->isValid()){
				return false;
			}
		}
		return true;
	}

	/**
	 * @return string
	 */
	public function getSkinId() : string{
		return $this->skinId;
	}

	/**
	 * @return string
	 */
	public function getPlayFabId() : string{
		return $this->playFabId;
	}

	/**
	 * @return string
	 */
	public function getSkinResourcePatch() : string{
		return $this->skinResourcePatch;
	}

	/**
	 * @return SerializedSkinImage
	 */
	public function getSkinImage() : SerializedSkinImage{
		return $this->skinImage;
	}

	/**
	 * @return SkinAnimation[]
	 */
	public function getAnimations() : array{
		return $this->animations;
	}

	/**
	 * @return SerializedSkinImage
	 */
	public function getCapeImage() : SerializedSkinImage{
		return $this->capeImage;
	}

	/**
	 * @return string
	 */
	public function getGeometryData() : string{
		return $this->geometryData;
	}

	/**
	 * @return string
	 */
	public function getAnimationData() : string{
		return $this->animationData;
	}

	/**
	 * @return bool
	 */
	public function isPremium() : bool{
		return $this->isPremium;
	}

	/**
	 * @return bool
	 */
	public function isPersona() : bool{
		return $this->isPersona;
	}

	/**
	 * @return bool
	 */
	public function isCapeOnClassic() : bool{
		return $this->isCapeOnClassic;
	}

	/**
	 * @return bool
	 */
	public function isPrimaryUser() : bool{
		return $this->isPrimaryUser;
	}

	/**
	 * @return string
	 */
	public function getCapeId() : string{
		return $this->capeId;
	}

	/**
	 * @return string
	 */
	public function getFullSkinId() : string{
		return $this->fullSkinId;
	}

	/**
	 * @return string
	 */
	public function getArmSize() : string{
		return $this->armSize;
	}

	/**
	 * @return string
	 */
	public function getSkinColor() : string{
		return $this->skinColor;
	}

	/**
	 * @return PersonaPiece[]
	 */
	public function getPersonaPieces() : array{
		return $this->personaPieces;
	}

	/**
	 * @return PieceTintColor[]
	 */
	public function getPieceTintColors() : array{
		return $this->pieceTintColors;
	}

	/**
	 * @return bool
	 */
	public function isVerified() : bool{
		return $this->isVerified;
	}

    /**
	 * @return bool
	 */
	public function isPremiumUser() : bool{
		return $this->isPrimaryUser;
	}

	/**
	 * @return bool
	 */
	public function isOverride() : bool{
		return $this->isOverride;
	}

    /**
     * @return string
     */
    public function getGeometryDataEngineVersion() {
        return $this->geometryDataEngineVersion;
    }

    /**
	 * @internal
	 *
	 * @param bool $isVerified
	 */
	public function setVerified(bool $isVerified) : void{
		$this->isVerified = $isVerified;
	}

	/**
	 * Hack to cut down on network overhead due to skins, by un-pretty-printing geometry JSON.
	 *
	 * Mojang, some stupid reason, send every single model for every single skin in the selected skin-pack.
	 * Not only that, they are pretty-printed.
	 * TODO: find out what model crap can be safely dropped from the packet (unless it gets fixed first)
	 */
	public function debloatGeometryData() : void{
		if($this->geometryData !== ""){
			$this->geometryData = (string) json_encode((new CommentedJsonDecoder())->decode($this->geometryData));
		}
	}

	/**
	 * Hack to fix skins conflict.
	 * Full skin ID must be unique for any set of data.
	 */
	public function generateFullSkinId() : void{
		$this->fullSkinId = UUID::fromRandom()->toString();

		/*UUID::fromData(
			$this->skinId,
			$this->skinResourcePatch,
			$this->skinImage->getData(),
			$this->capeImage->getData(),
			$this->geometryData,
			(string) $this->isPremium,
			(string) $this->isPersona,
			(string) $this->isCapeOnClassic,
			$this->capeId
		)->toString();*/
	}
}

