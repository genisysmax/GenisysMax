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



/**
 * @phpstan-type LoggerAttachment \Closure(mixed $level, string $message) : void
 */
interface AttachableLogger extends \Logger{

	/**
	 * @phpstan-param LoggerAttachment $attachment
	 *
	 * @return void
	 */
	public function addAttachment(\Closure $attachment);

	/**
	 * @phpstan-param LoggerAttachment $attachment
	 *
	 * @return void
	 */
	public function removeAttachment(\Closure $attachment);

	/**
	 * @return void
	 */
	public function removeAttachments();

	/**
	 * @return \Closure[]
	 * @phpstan-return LoggerAttachment[]
	 */
	public function getAttachments();
}


