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

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>

use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\network\NetworkSession;
use pocketmine\utils\Utils;
use function bin2hex;
use function chr;
use function is_object;
use function is_string;
use function method_exists;


abstract class DataPacket extends NetworkBinaryStream{

	public const NETWORK_ID = 0;

	public $isEncoded = false;
	public $wasDecoded = false;

	public function pid(){
		return $this::NETWORK_ID;
	}

	public function getName() : string{
		return (new \ReflectionClass($this))->getShortName();
	}

	public function canBeBatched() : bool{
		return true;
	}

	public function canBeSentBeforeLogin() : bool{
		return false;
	}

	public function mayHaveUnreadBytes() : bool{
		return true;
	}

	public function mustBeDecoded() : bool{
		return true;
	}

	public function decode(){
		$this->offset = 1;
		$this->decodePayload();
		$this->wasDecoded = true;
	}

	/**
	 * Note for plugin developers: If you're adding your own packets, you should perform decoding in here.
	 */
	public function decodePayload(){

	}

	public function encode(){
		$this->reset();
		$this->encodePayload();
		$this->isEncoded = true;
	}

	/**
	 * Note for plugin developers: If you're adding your own packets, you should perform encoding in here.
	 */
	public function encodePayload(){

	}

	/**
	 * Performs handling for this packet. Usually you'll want an appropriately named method in the NetworkSession for this.
	 *
	 * This method returns a bool to indicate whether the packet was handled or not. If the packet was unhandled, a debug message will be logged with a hexdump of the packet.
	 * Typically this method returns the return value of the handler in the supplied NetworkSession. See other packets for examples how to implement this.
	 *
	 * @param NetworkSession $session
	 *
	 * @return bool true if the packet was handled successfully, false if not.
	 */
	abstract public function handle(NetworkSession $session) : bool;

	public function reset() : void{
		$this->buffer = chr($this::NETWORK_ID);
		$this->offset = 0;
	}

	public function clean(){
		$this->buffer = null;
		$this->isEncoded = false;
		$this->wasDecoded = false;
		$this->offset = 0;
		return $this;
	}

	public function __debugInfo(){
		$data = [];
		foreach($this as $k => $v){
			if($k === "buffer" and is_string($v)){
				$data[$k] = bin2hex($v);
			}elseif(is_string($v) or (is_object($v) and method_exists($v, "__toString"))){
				$data[$k] = Utils::printable((string) $v);
			}else{
				$data[$k] = $v;
			}
		}

		return $data;
	}
}


