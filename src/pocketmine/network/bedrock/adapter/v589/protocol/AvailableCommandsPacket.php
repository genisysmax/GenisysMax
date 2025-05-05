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

namespace pocketmine\network\bedrock\adapter\v589\protocol;

use pocketmine\network\bedrock\protocol\types\command\CommandData;
use pocketmine\network\bedrock\protocol\types\command\CommandEnum;
use pocketmine\network\bedrock\protocol\types\command\CommandEnumConstraint;
use pocketmine\network\bedrock\protocol\types\command\CommandOverload;
use pocketmine\network\bedrock\protocol\types\command\CommandParameter;
use function count;

class AvailableCommandsPacket extends \pocketmine\network\bedrock\adapter\v649\protocol\AvailableCommandsPacket {

    /**
     * @var string[]
     * A list of every single enum value for every single command in the packet, including alias names.
     */
    public $enumValues = [];
    /** @var int */
    public $enumValuesCount = 0;

    /**
     * @var string[]
     * A list of argument postfixes. Used for the /xp command's <int>L.
     */
    public $postfixes = [];

    /**
     * @var CommandEnum[]
     * List of command enums, from command aliases to argument enums.
     */
    public $enums = [];
    /**
     * @var int[] string => int map of enum name to index
     */
    protected $enumMap = [];

    /**
     * @var CommandData[]
     * List of command data, including name, description, alias indexes and parameters.
     */
    public $commandData = [];

    /**
     * @var CommandEnum[]
     * List of enums which aren't directly referenced by any vanilla command.
     * This is used for the `CommandName` enum, which is a magic enum used by the `command` argument type.
     */
    public $hardcodedEnums = [];

    /**
     * @var CommandEnum[]
     * List of dynamic command enums, also referred to as "soft" enums. These can by dynamically updated mid-game
     * without resending this packet.
     */
    public $softEnums = [];

    /**
     * @var CommandEnumConstraint[]
     * List of constraints for enum members. Used to constrain gamerules that can bechanged in nocheats mode and more.
     */
    public $enumConstraints = [];

    /**
     * Returns argument type enum from string id.
     *
     * @param string $str
     *
     * @return int
     */
    public static function argTypeFromString(string $str) : int{
        return match ($str) {
            "int" => AvailableCommandsPacket::ARG_TYPE_INT,
            "float" => AvailableCommandsPacket::ARG_TYPE_FLOAT,
            "mixed" => AvailableCommandsPacket::ARG_TYPE_VALUE,
            "target" => AvailableCommandsPacket::ARG_TYPE_TARGET,
            "string", "stringenum" => AvailableCommandsPacket::ARG_TYPE_STRING,
            "pos", "xyz" => AvailableCommandsPacket::ARG_TYPE_POSITION,
            "message" => AvailableCommandsPacket::ARG_TYPE_MESSAGE,
            "rawtext", "text" => AvailableCommandsPacket::ARG_TYPE_RAWTEXT,
            "json" => AvailableCommandsPacket::ARG_TYPE_JSON,
            "command" => AvailableCommandsPacket::ARG_TYPE_COMMAND,
            default => AvailableCommandsPacket::ARG_TYPE_RAWTEXT,
        };
    }

    public function decodePayload(){
        for($i = 0, $this->enumValuesCount = $this->getUnsignedVarInt(); $i < $this->enumValuesCount; ++$i){
            $this->enumValues[] = $this->getString();
        }

        for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
            $this->postfixes[] = $this->getString();
        }

        for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
            $enum = $this->getEnum();
            if(isset(self::HARDCODED_ENUM_NAMES[$enum->enumName])){
                $this->hardcodedEnums[] = $enum;
            }else{
                $this->enums[] = $enum;
            }
        }

        for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
            $this->commandData[] = $this->getCommandData();
        }

        for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
            $this->softEnums[] = $this->getSoftEnum();
        }

        for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
            $this->enumConstraints[] = $this->getEnumConstraint($this->enums, $this->enumValues);
        }
    }

    protected function getEnum() : CommandEnum{
        $retval = new CommandEnum();
        $retval->enumName = $this->getString();

        for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
            $index = $this->getEnumValueIndex();
            if(!isset($this->enumValues[$index])){
                throw new \UnexpectedValueException("Invalid enum value index $index");
            }
            //Get the enum value from the initial pile of mess
            $retval->enumValues[] = $this->enumValues[$index];
        }

        return $retval;
    }

    protected function getSoftEnum() : CommandEnum{
        $retval = new CommandEnum();
        $retval->enumName = $this->getString();

        for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
            //Get the enum value from the initial pile of mess
            $retval->enumValues[] = $this->getString();
        }

        return $retval;
    }

    protected function putEnum(CommandEnum $enum) : void{
        $this->putString($enum->enumName);

        $this->putUnsignedVarInt(count($enum->enumValues));
        foreach($enum->enumValues as $value){
            //Dumb bruteforce search. I hate this packet.
            $index = array_search($value, $this->enumValues, true);
            if($index === false){
                throw new \InvalidStateException("Enum value '$value' not found");
            }
            $this->putEnumValueIndex($index);
        }
    }

    protected function putSoftEnum(CommandEnum $enum) : void{
        $this->putString($enum->enumName);

        $this->putUnsignedVarInt(count($enum->enumValues));
        foreach($enum->enumValues as $value){
            $this->putString($value);
        }
    }

    protected function getEnumValueIndex() : int{
        if($this->enumValuesCount < 256){
            return $this->getByte();
        }elseif($this->enumValuesCount < 65536){
            return $this->getLShort();
        }else{
            return $this->getLInt();
        }
    }

    protected function putEnumValueIndex(int $index) : void{
        if($this->enumValuesCount < 256){
            $this->putByte($index);
        }elseif($this->enumValuesCount < 65536){
            $this->putLShort($index);
        }else{
            $this->putLInt($index);
        }
    }

    /**
     * @param CommandEnum[] $enums
     * @param string[]      $enumValues
     *
     * @return CommandEnumConstraint
     */
    protected function getEnumConstraint(array $enums, array $enumValues) : CommandEnumConstraint{
        //wtf, what was wrong with an offset inside the enum? :(
        $valueIndex = $this->getLInt();
        if(!isset($enumValues[$valueIndex])){
            throw new \UnexpectedValueException("Enum constraint refers to unknown enum value index $valueIndex");
        }
        $enumIndex = $this->getLInt();
        if(!isset($enums[$enumIndex])){
            throw new \UnexpectedValueException("Enum constraint refers to unknown enum index $enumIndex");
        }
        $enum = $enums[$enumIndex];
        $valueOffset = array_search($enumValues[$valueIndex], $enum->enumValues, true);
        if($valueOffset === false){
            throw new \UnexpectedValueException("Value \"" . $enumValues[$valueIndex] . "\" does not belong to enum \"$enum->enumName\"");
        }

        $constraintIds = [];
        for($i = 0, $count = $this->getUnsignedVarInt(); $i < $count; ++$i){
            $constraintIds[] = $this->getByte();
        }

        return new CommandEnumConstraint($enum, $valueOffset, $constraintIds);
    }

    /**
     * @param CommandEnumConstraint $constraint
     * @param int[]                 $enumIndexes string enum name -> int index
     * @param int[]                 $enumValueIndexes string value -> int index
     */
    protected function putEnumConstraint(CommandEnumConstraint $constraint, array $enumIndexes, array $enumValueIndexes) : void{
        $this->putLInt($enumValueIndexes[$constraint->getAffectedValue()]);
        $this->putLInt($enumIndexes[$constraint->getEnum()->enumName]);
        $this->putUnsignedVarInt(count($constraint->getConstraints()));
        foreach($constraint->getConstraints() as $v){
            $this->putByte($v);
        }
    }


    protected function getCommandData() : CommandData{
        $retval = new CommandData();
        $retval->commandName = $this->getString();
        $retval->commandDescription = $this->getString();
        $retval->flags = $this->getLShort();
        $retval->permission = $this->getByte();
        $retval->aliases = $this->enums[$this->getLInt()] ?? null;

        for($overloadIndex = 0, $overloadCount = $this->getUnsignedVarInt(); $overloadIndex < $overloadCount; ++$overloadIndex){
            $parameters = [];
            for($paramIndex = 0, $paramCount = $this->getUnsignedVarInt(); $paramIndex < $paramCount; ++$paramIndex){
                $parameter = new CommandParameter();
                $parameter->paramName = $this->getString();
                $parameter->paramType = $this->getLInt();
                $parameter->isOptional = $this->getBool();

                if($parameter->paramType & self::ARG_FLAG_ENUM){
                    $index = ($parameter->paramType & 0xffff);
                    $parameter->enum = $this->enums[$index] ?? null;
                    if($parameter->enum === null){
                        throw new \UnexpectedValueException("expected enum at $index, but got none");
                    }
                }elseif($parameter->paramType & self::ARG_FLAG_POSTFIX){
                    $index = ($parameter->paramType & 0xffff);
                    $parameter->postfix = $this->postfixes[$index] ?? null;
                    if($parameter->postfix === null){
                        throw new \UnexpectedValueException("expected postfix at $index, but got none");
                    }
                }elseif(($parameter->paramType & self::ARG_FLAG_VALID) === 0){
                    throw new \UnexpectedValueException("Invalid parameter type 0x" . dechex($parameter->paramType));
                }

                $parameters[] = $parameter;
            }
            $retval->overloads[$overloadIndex] = new CommandOverload(false, $parameters);
        }

        return $retval;
    }

    protected function putCommandData(CommandData $data) : void{
        $this->putString($data->commandName);
        $this->putString($data->commandDescription);
        $this->putLShort($data->flags);
        $this->putByte($data->permission);

        if($data->aliases !== null){
            $this->putLInt($this->enumMap[$data->aliases->enumName] ?? -1);
        }else{
            $this->putLInt(-1);
        }

        $this->putUnsignedVarInt(count($data->overloads));
        foreach($data->overloads as $overload){
            /** @var CommandOverload $overload */
            $this->putUnsignedVarInt(count($overload->getParameters()));
            foreach($overload->getParameters() as $parameter){
                $this->putString($parameter->paramName);

                if($parameter->enum !== null){
                    $type = self::ARG_FLAG_ENUM | self::ARG_FLAG_VALID | ($this->enumMap[$parameter->enum->enumName] ?? -1);
                }elseif($parameter->postfix !== null){
                    $key = array_search($parameter->postfix, $this->postfixes, true);
                    if($key === false){
                        throw new \InvalidStateException("Postfix '$parameter->postfix' not in postfixes array");
                    }
                    $type = self::ARG_FLAG_POSTFIX | $key;
                }else{
                    $type = $parameter->paramType;
                }

                $this->putLInt($type);
                $this->putBool($parameter->isOptional);
                $this->putByte(0); // TODO: 19/03/2019 Bit flags. Only first bit is used for GameRules.
            }
        }
    }

    private function argTypeToString(int $argtype) : string{
        if($argtype & self::ARG_FLAG_VALID){
            if($argtype & self::ARG_FLAG_ENUM){
                return "stringenum (" . ($argtype & 0xffff) . ")";
            }

            switch($argtype & 0xffff){
                case self::ARG_TYPE_INT:
                    return "int";
                case self::ARG_TYPE_FLOAT:
                    return "float";
                case self::ARG_TYPE_VALUE:
                    return "mixed";
                case self::ARG_TYPE_TARGET:
                    return "target";
                case self::ARG_TYPE_STRING:
                    return "string";
                case self::ARG_TYPE_POSITION:
                    return "xyz";
                case self::ARG_TYPE_MESSAGE:
                    return "message";
                case self::ARG_TYPE_RAWTEXT:
                    return "text";
                case self::ARG_TYPE_JSON:
                    return "json";
                case self::ARG_TYPE_COMMAND:
                    return "command";
            }
        }elseif($argtype & self::ARG_FLAG_POSTFIX){
            $postfix = $this->postfixes[$argtype & 0xffff];

            return "int (postfix $postfix)";
        }else{
            throw new \UnexpectedValueException("Unknown arg type 0x" . dechex($argtype));
        }

        return "unknown ($argtype)";
    }

    public function encodePayload(){
        $enumValuesMap = [];
        $postfixesMap = [];
        $enumMap = [];

        $addEnumFn = static function(CommandEnum $enum) use (&$enumMap, &$enumValuesMap){
            $enumMap[$enum->enumName] = $enum;

            foreach($enum->enumValues as $str){
                $enumValuesMap[$str] = $enumValuesMap[$str] ?? count($enumValuesMap);
            }
        };

        foreach($this->hardcodedEnums as $enum){
            $addEnumFn($enum);
        }
        foreach($this->commandData as $commandData){
            if($commandData->aliases !== null){
                $addEnumFn($commandData->aliases);
            }

            foreach($commandData->overloads as $overload){
                /**
                 * @var CommandOverload $overload
                 * @var CommandParameter   $parameter
                 */
                foreach($overload->getParameters() as $parameter){
                    if($parameter->enum !== null){
                        $addEnumFn($parameter->enum);
                    }

                    if($parameter->postfix !== null){
                        $postfixesMap[$parameter->postfix] = true;
                    }
                }
            }
        }

        $this->enumValues = array_map('\strval', array_keys($enumValuesMap)); //stupid PHP key casting D:
        $this->putUnsignedVarInt($this->enumValuesCount = count($this->enumValues));
        foreach($this->enumValues as $enumValue){
            $this->putString($enumValue);
        }

        $this->postfixes = array_map('\strval', array_keys($postfixesMap));
        $this->putUnsignedVarInt(count($this->postfixes));
        foreach($this->postfixes as $postfix){
            $this->putString($postfix);
        }

        $this->enums = array_values($enumMap);
        $this->enumMap = array_flip(array_keys($enumMap));
        $this->putUnsignedVarInt(count($this->enums));
        foreach($this->enums as $enum){
            $this->putEnum($enum);
        }

        $this->putUnsignedVarInt(count($this->commandData));
        foreach($this->commandData as $data){
            $this->putCommandData($data);
        }

        $this->putUnsignedVarInt(count($this->softEnums));
        foreach($this->softEnums as $enum){
            $this->putSoftEnum($enum);
        }

        $this->putUnsignedVarInt(count($this->enumConstraints));
        foreach($this->enumConstraints as $constraint){
            $this->putEnumConstraint($constraint, $enumMap, $enumValuesMap);
        }
    }
}

