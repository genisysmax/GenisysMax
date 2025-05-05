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

namespace pocketmine\plugin;

use function spl_object_id;

class PluginLogger extends \PrefixedLogger implements \AttachableLogger{

	/** @var \Closure[] */
	private $attachments = [];

	public function addAttachment(\Closure $attachment){
		$this->attachments[spl_object_id($attachment)] = $attachment;
	}

	public function removeAttachment(\Closure $attachment){
		unset($this->attachments[spl_object_id($attachment)]);
	}

	public function removeAttachments(){
		$this->attachments = [];
	}

	public function getAttachments(){
		return $this->attachments;
	}

	public function log(mixed $level, mixed $message) : void{
		parent::log($level, $message);
		foreach($this->attachments as $attachment){
			$attachment($level, $message);
		}
	}
}

