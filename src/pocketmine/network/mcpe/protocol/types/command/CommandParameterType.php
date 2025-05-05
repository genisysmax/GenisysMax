<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types\command;

interface CommandParameterType {
    public const TYPE_STRING = "string";
    public const TYPE_STRING_ENUM = "stringenum";
    public const TYPE_BOOL = "bool";
    public const TYPE_TARGET = "target";
    public const TYPE_BLOCK_POS = "blockpos";
    public const TYPE_RAW_TEXT = "rawtext";
    public const TYPE_INT = "int";
}