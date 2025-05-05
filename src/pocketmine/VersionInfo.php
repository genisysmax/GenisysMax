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

namespace pocketmine;

use function defined;

// composer autoload doesn't use require_once and also pthreads can inherit things
// TODO: drop this file and use a final class with constants
if(defined('pocketmine\_VERSION_INFO_INCLUDED')){
	return;
}
const _VERSION_INFO_INCLUDED = true;

const NAME = "GenisysMax";
const BASE_VERSION = "0.0.1-STABLE";
const API_VERSION = "3.28.0";
const DEVELOPERS = ["LINUXOV"];
const IS_DEVELOPMENT_BUILD = false;
const BUILD_CHANNEL = "main";


