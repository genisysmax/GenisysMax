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

namespace pocketmine\entity;

use pocketmine\network\bedrock\protocol\types\skin\Skin as BedrockSkin;
use pocketmine\network\bedrock\skin\SkinConverter as BedrockSkinConverter;
use pocketmine\network\mcpe\protocol\types\Skin as McpeSkin;
use pocketmine\network\mcpe\skin\SkinConverter as McpeSkinConverter;

class Skin{

	/** @var McpeSkin */
	protected $mcpeSkin;
	/** @var BedrockSkin */
	protected $bedrockSkin;

    public static function fromSkinDataId(string $skinId, string $skinData) : self{
        $skin = new McpeSkin($skinId, $skinData);
        return self::fromMcpeSkin($skin);
    }

	public static function fromMcpeSkin(McpeSkin $mcpeSkin) : self{
		$bedrockSkin = BedrockSkinConverter::convert($mcpeSkin);
		return new self($mcpeSkin, $bedrockSkin);
	}

	public static function fromBedrockSkin(BedrockSkin $bedrockSkin) : self{
		$mcpeSkin = McpeSkinConverter::convert($bedrockSkin);
		return new self($mcpeSkin, $bedrockSkin);
	}

	public function __construct(McpeSkin $mcpeSkin, BedrockSkin $bedrockSkin){
		$this->mcpeSkin = $mcpeSkin;
		$this->bedrockSkin = $bedrockSkin;
	}

	/**
	 * @return bool
	 */
	public function isValid() : bool{
		return $this->mcpeSkin !== null and $this->bedrockSkin !== null and $this->mcpeSkin->isValid() and $this->bedrockSkin->isValid();
	}

	/**
	 * @return McpeSkin
	 */
	public function getMcpeSkin() : McpeSkin{
		return $this->mcpeSkin;
	}

	/**
	 * @return BedrockSkin
	 */
	public function getBedrockSkin() : BedrockSkin{
		return $this->bedrockSkin;
	}
}

