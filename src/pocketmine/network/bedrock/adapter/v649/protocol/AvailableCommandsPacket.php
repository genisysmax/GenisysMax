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

namespace pocketmine\network\bedrock\adapter\v649\protocol;

class AvailableCommandsPacket extends \pocketmine\network\bedrock\protocol\AvailableCommandsPacket {

    public const ARG_TYPE_EQUIPMENT_SLOT = 43;
    public const ARG_TYPE_STRING = 44;

    public const ARG_TYPE_INT_POSITION = 52;
    public const ARG_TYPE_POSITION = 53;

    public const ARG_TYPE_MESSAGE = 55;

    public const ARG_TYPE_RAWTEXT = 58;

    public const ARG_TYPE_JSON = 62;

    public const ARG_TYPE_BLOCK_STATES = 71;

    public const ARG_TYPE_COMMAND = 74;

}


