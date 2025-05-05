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

use pocketmine\network\bedrock\palette\BlockPalette;

class BlockPaletteEntry extends PaletteEntry
{

    private string $encodePalette;
    private array $runtimeToLegacy;
    private array $legacyToRuntime;

    public function process(): void
    {
        $palette = $this->palette;
        /** @var BlockPalette $palette */
        $palette::init();

        $this->encodePalette = $palette->getEncodedPalette();
        $this->runtimeToLegacy = $palette->getRuntimeToLegacy();
        $this->legacyToRuntime = $palette->getLegacyToRuntime();
    }

    public function result(): void
    {
        $palette = $this->palette;
        /** @var BlockPalette $palette */
        ($palette)::setEncodedPalette($this->encodePalette);
        ($palette)::setRuntimeToLegacy($this->runtimeToLegacy);
        ($palette)::setLegacyToRuntime($this->legacyToRuntime);
    }
}

