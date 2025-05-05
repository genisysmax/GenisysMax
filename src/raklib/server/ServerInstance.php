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

namespace raklib\server;

use raklib\protocol\EncapsulatedPacket;

interface ServerInstance{

	/**
	 * @param int    $sessionId
	 * @param string $address
	 * @param int    $port
	 * @param int    $clientID
	 * @param int    $protocolVersion
	 * @param bool   $isValid
	 */
	public function openSession(int $sessionId, string $address, int $port, int $clientID, int $protocolVersion, bool $isValid) : void;

	/**
	 * @param int    $sessionId
	 * @param string $reason
	 */
	public function closeSession(int $sessionId, string $reason) : void;

	/**
	 * @param int                $sessionId
	 * @param EncapsulatedPacket $packet
	 * @param int                $flags
	 */
	public function handleEncapsulated(int $sessionId, EncapsulatedPacket $packet, int $flags) : void;

	/**
	 * @param string $address
	 * @param int    $port
	 * @param string $payload
	 */
	public function handleRaw(string $address, int $port, string $payload) : void;

	/**
	 * @param int $sessionId
	 * @param int $identifierACK
	 */
	public function notifyACK(int $sessionId, int $identifierACK) : void;

	/**
	 * @param string $option
	 * @param string $value
	 */
	public function handleOption(string $option, string $value) : void;

	/**
	 * @param int $sessionId
	 * @param int $pingMS
	 */
	public function updatePing(int $sessionId, int $pingMS) : void;
}


