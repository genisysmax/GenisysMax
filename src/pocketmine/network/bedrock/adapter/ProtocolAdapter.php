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

namespace pocketmine\network\bedrock\adapter;

use pocketmine\item\Item;
use pocketmine\network\bedrock\protocol\DataPacket;

interface ProtocolAdapter{

	/**
	 * @param string $buf
	 *
	 * @return DataPacket|null
	 */
	public function processClientToServer(string $buf) : ?DataPacket;

	/**
	 * @param DataPacket $packet
	 *
	 * @return DataPacket|null
	 */
	public function processServerToClient(DataPacket $packet) : ?DataPacket;

    /**
     * @return Item[]
     */
    public function getCreativeItems() : array;

	/**
	 * @param int $runtimeId
	 *
	 * @return int
	 */
	public function translateBlockId(int $runtimeId) : int;

	/**
	 * @return int
	 */
	public function getChunkProtocol() : int;

	/**
	 * @return int
	 */
	public function getProtocolVersion() : int;

}

