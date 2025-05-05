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

namespace pocketmine\errorhandler;

final class ErrorRecord{

	/** @var int */
	private $severity;
	/** @var string */
	private $message;
	/** @var string */
	private $file;
	/** @var int */
	private $line;

	public function __construct(int $severity, string $message, string $file, int $line){
		$this->severity = $severity;
		$this->message = $message;
		$this->file = $file;
		$this->line = $line;
	}

	public function getSeverity() : int{
		return $this->severity;
	}

	public function getMessage() : string{
		return $this->message;
	}

	public function getFile() : string{
		return $this->file;
	}

	public function getLine() : int{
		return $this->line;
	}
}



