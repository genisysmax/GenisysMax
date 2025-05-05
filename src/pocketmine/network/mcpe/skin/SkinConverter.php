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

namespace pocketmine\network\mcpe\skin;

use pocketmine\network\bedrock\protocol\types\skin\Skin as BedrockSkin;
use pocketmine\network\mcpe\protocol\types\Skin as McpeSkin;
use function array_rand;
use function file_get_contents;
use function json_decode;
use function substr;

class SkinConverter{

    /** @var string[][] */
    private static $defaultSkins;

    public static function convert(BedrockSkin $skin) : McpeSkin{
        $skinImage = $skin->getSkinImage();
        if($skinImage->getWidth() === 64 and ($skinImage->getHeight() === 32 or $skinImage->getHeight() === 64)){
            $skinResourcePatch = json_decode($skin->getSkinResourcePatch(), true);
            if($skinResourcePatch !== null and isset($skinResourcePatch["geometry"]["default"]) and $skinResourcePatch["geometry"]["default"] === "geometry.humanoid.customSlim"){
                $skinId = "Standard_CustomSlim";
            }else{
                $skinId = "Standard_Custom";
            }
            $skinData = $skinImage->getData();
            return new McpeSkin($skinId, $skinData);
        }else{
            self::$defaultSkins = self::$defaultSkins ?? [
                "steve" => new McpeSkin("Standard_Steve", file_get_contents(\pocketmine\RESOURCE_PATH . "skins/steve.skindata")),
                "alex" => new McpeSkin("Standard_Alex", file_get_contents(\pocketmine\RESOURCE_PATH . "skins/alex.skindata")),
            ];

            if($skin->isPersona() and substr($skin->getSkinId(), -4, 3) === "_0_"){
                $num = substr($skin->getSkinId(), -1);
                if($num === "0"){
                    $mcpeSkin = self::$defaultSkins["steve"];
                }else{
                    $mcpeSkin = self::$defaultSkins["alex"];
                }
            }
            return $mcpeSkin ?? self::$defaultSkins[array_rand(self::$defaultSkins)];
        }
    }

    private function __construct(){
        // oof
    }
}

