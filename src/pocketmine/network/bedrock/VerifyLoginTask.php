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

namespace pocketmine\network\bedrock;

use pocketmine\network\bedrock\protocol\LoginPacket;
use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;

class VerifyLoginTask extends \pocketmine\network\mcpe\VerifyLoginTask{

	public function __construct(Player $player, LoginPacket $packet){
		$this->chainJwts = igbinary_serialize($packet->chainData["chain"]);
		$this->clientDataJwt = $packet->clientDataJwt;

		AsyncTask::__construct([$player, $packet]);
	}
}

