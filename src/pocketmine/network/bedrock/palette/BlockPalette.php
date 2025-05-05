<?php

namespace pocketmine\network\bedrock\palette;

interface BlockPalette extends Palette
{

    public static function setEncodedPalette(string $buffer) : void;

    public static function getRuntimeToLegacy() : array;

    public static function setRuntimeToLegacy(array $list) : void;

    public static function getLegacyToRuntime() : array;

    public static function setLegacyToRuntime(array $list) : void;

}