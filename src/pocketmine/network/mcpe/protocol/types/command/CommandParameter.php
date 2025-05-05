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


    public $name;
    /** @var string */
    public $type;
    /** @var bool */
    public $optional;

    public function __construct(
        string $name = "",
        string $type = "int",
        bool $optional = false
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->optional = $optional;
    }

    public function toData() : array{
        return [
            "name" => $this->name,
            "type" => $this->type,
            "isOptional" => $this->optional
        ];
    }

    public function getName() : string{
        return $this->name;
    }

    public function getType() : string{
        return $this->type;
    }
}

