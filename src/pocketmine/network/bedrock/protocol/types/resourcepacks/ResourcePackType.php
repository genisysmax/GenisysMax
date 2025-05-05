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

namespace pocketmine\network\bedrock\protocol\types\resourcepacks;

final class ResourcePackType{

	private function __construct(){
		//NOOP
	}

	public const INVALID = 0;
	public const ADDON = 1;
	public const CACHED = 2;
	public const COPY_PROTECTED = 3;
	public const BEHAVIORS = 4;
	public const PERSONA_PIECE = 5;
	public const RESOURCES = 6;
	public const SKINS = 7;
	public const WORLD_TEMPLATE = 8;
}


