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

namespace pocketmine\thread;

use pmmp\thread\ThreadSafe;

final class ThreadCrashInfoFrame extends ThreadSafe{

	public function __construct(
		private string $printableFrame,
		private ?string $file,
		private int $line,
	){}

	public function getPrintableFrame() : string{ return $this->printableFrame; }

	public function getFile() : ?string{ return $this->file; }

	public function getLine() : int{ return $this->line; }
}

