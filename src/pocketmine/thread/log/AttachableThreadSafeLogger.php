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

namespace pocketmine\thread\log;

use pmmp\thread\ThreadSafeArray;

abstract class AttachableThreadSafeLogger extends ThreadSafeLogger{

	/**
	 * @var ThreadSafeArray|ThreadSafeLoggerAttachment[]
	 * @phpstan-var ThreadSafeArray<int, ThreadSafeLoggerAttachment>
	 */
	protected ?ThreadSafeArray $attachments = null;

	public function __construct(){
		$this->attachments = new ThreadSafeArray();
	}

	public function addAttachment(ThreadSafeLoggerAttachment $attachment) : void{
		$this->attachments[] = $attachment;
	}

	public function removeAttachment(ThreadSafeLoggerAttachment $attachment) : void{
		foreach($this->attachments as $i => $a){
			if($attachment === $a){
				unset($this->attachments[$i]);
			}
		}
	}

	public function removeAttachments() : void{
		foreach($this->attachments as $i => $a){
			unset($this->attachments[$i]);
		}
	}

	/**
	 * @return ThreadSafeLoggerAttachment[]
	 */
	public function getAttachments() : array{
		return (array) $this->attachments;
	}
}

