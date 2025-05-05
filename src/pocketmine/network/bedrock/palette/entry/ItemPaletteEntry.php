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

namespace pocketmine\network\bedrock\palette\entry;

use pocketmine\network\bedrock\palette\ItemPalette;

class ItemPaletteEntry extends PaletteEntry
{

    private string $encodePalette;
    private array $complexRuntimeToLegacyIdMap;
    private array $complexLegacyToRuntimeIdMap;
    private array $simpleRuntimeToLegacyIdMap;
    private array $simpleLegacyToRuntimeIdMap;
    private array $stringToRuntimeIdMap;
    private array $runtimeToStringIdMap;

    public function process(): void
    {
        $palette = $this->palette;
        /** @var ItemPalette $palette */
        $palette::init();

        $this->encodePalette = $palette->getEncodedPalette();
        $this->complexRuntimeToLegacyIdMap = $palette->getComplexRuntimeToLegacy();
        $this->complexLegacyToRuntimeIdMap = $palette->getComplexLegacyToRuntime();
        $this->simpleRuntimeToLegacyIdMap = $palette->getSimpleRuntimeToLegacy();
        $this->simpleLegacyToRuntimeIdMap = $palette->getSimpleLegacyToRuntime();
        $this->stringToRuntimeIdMap = $palette->getStringToRuntime();
        $this->runtimeToStringIdMap = $palette->getRuntimeToString();
    }

    public function result(): void
    {
        $palette = $this->palette;
        /** @var ItemPalette $palette */;
        ($palette)::setEncodedPalette($this->encodePalette);
        ($palette)::setComplexRuntimeToLegacy($this->complexRuntimeToLegacyIdMap);
        ($palette)::setComplexLegacyToRuntime($this->complexLegacyToRuntimeIdMap);
        ($palette)::setSimpleRuntimeToLegacy($this->simpleRuntimeToLegacyIdMap);
        ($palette)::setSimpleLegacyToRuntime($this->simpleLegacyToRuntimeIdMap);
        ($palette)::setStringToRuntime($this->stringToRuntimeIdMap);
        ($palette)::setRuntimeToString($this->runtimeToStringIdMap);
    }
}

