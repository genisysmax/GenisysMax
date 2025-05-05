<?php

namespace pocketmine\network\bedrock\palette;

interface Palette
{

    public static function init() : void;

    public static function getEncodedPalette() : ?string;

}