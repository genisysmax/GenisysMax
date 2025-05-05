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

namespace pocketmine\network\bedrock\adapter\v503\protocol;

class AvailableCommandsPacket extends \pocketmine\network\bedrock\adapter\v575\protocol\AvailableCommandsPacket{

	public const ARG_TYPE_INT = 0x01;
	public const ARG_TYPE_FLOAT = 0x03;
	public const ARG_TYPE_VALUE = 0x04;
	public const ARG_TYPE_WILDCARD_INT = 0x05;
	public const ARG_TYPE_OPERATOR = 0x06;
	public const ARG_TYPE_TARGET = 0x07;
	public const ARG_TYPE_WILDCARD_TARGET = 0x09;

	public const ARG_TYPE_FILEPATH = 0x10;

	public const ARG_TYPE_EQUIPMENT_SLOT = 0x25;

	public const ARG_TYPE_STRING = 0x26;

	public const ARG_TYPE_INT_POSITION = 0x2e;
	public const ARG_TYPE_POSITION = 0x2f;

	public const ARG_TYPE_MESSAGE = 0x32;

	public const ARG_TYPE_RAWTEXT = 0x34;

	public const ARG_TYPE_JSON = 0x38;

	public const ARG_TYPE_COMMAND = 0x45;
}

