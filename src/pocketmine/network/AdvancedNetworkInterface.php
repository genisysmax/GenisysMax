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

/**
 * Network-related classes
 */
namespace pocketmine\network;

interface AdvancedNetworkInterface extends NetworkInterface{

	/**
	 * @param string $address
	 * @param int    $timeout Seconds
	 */
	public function blockAddress(string $address, int $timeout = 300);

	/**
	 * @param Network $network
	 */
	public function setNetwork(Network $network);

	/**
	 * @param string $address
	 * @param int    $port
	 * @param string $payload
	 */
	public function sendRawPacket(string $address, int $port, string $payload);


}

