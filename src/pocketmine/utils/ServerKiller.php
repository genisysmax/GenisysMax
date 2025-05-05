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

namespace pocketmine\utils;

use pocketmine\thread\Thread;

class ServerKiller extends Thread{

	public function __construct(public int $time = 15){}

	public function onRun() : void{
		$start = time();
		$this->synchronized(function(){
			$this->wait($this->time * 1000000);
		});
		if(time() - $start >= $this->time){
            echo "\nTook too long to stop, server was killed forcefully!\n";
            @Process::kill(Process::pid());
		}
	}

	public function getThreadName() : string{
		return "Server Killer";
	}
}

