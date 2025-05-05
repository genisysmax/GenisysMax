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


    public $enum_values = [];

    public function __construct(
        string $name = "",
        bool $optional = false,
        array $enum_values = []
    ){
        $this->enum_values = $enum_values;
        parent::__construct($name, CommandParameterType::TYPE_STRING_ENUM, $optional); 
    }

    public function toArray() : array{
        return [
            "name" => $this->name,
            "type" => $this->type,
            "isOptional" => $this->optional,
            "enum_values" => $this->enum_values
        ];
    }
}

