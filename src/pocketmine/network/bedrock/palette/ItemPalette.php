<?php

namespace pocketmine\network\bedrock\palette;

interface ItemPalette extends Palette
{

    public static function getEncodedPalette() : ?string;

    public static function setEncodedPalette(string $buffer) : void;

    public static function getComplexRuntimeToLegacy() : array;

    public static function setComplexRuntimeToLegacy(array $complex) : void;

    public static function getComplexLegacyToRuntime() : array;

    public static function setComplexLegacyToRuntime(array $complex) : void;

    public static function getSimpleRuntimeToLegacy() : array;

    public static function setSimpleRuntimeToLegacy(array $simple) : void;

    public static function getSimpleLegacyToRuntime() : array;

    public static function setSimpleLegacyToRuntime(array $simple) : void;

    public static function getStringToRuntime() : array;

    public static function setStringToRuntime(array $string) : void;

    public static function getRuntimeToString() : array;

    public static function setRuntimeToString(array $string) : void;

}