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

/**
 * @internal
 * This interface contains descriptions of ITC packets used to transmit data into RakLib from the main thread.
 */
interface ITCProtocol{

	/*
	 * These internal "packets" DO NOT exist in the RakNet protocol. They are used by the RakLib API to communicate
	 * messages between the RakLib thread and the implementation's thread.
	 *
	 * Internal Packet:
	 * byte (packet ID)
	 * byte[] (payload)
	 */

	/*
	 * ENCAPSULATED payload:
	 * int32 (internal session ID)
	 * byte (flags, last 3 bits, priority)
	 * payload (binary internal EncapsulatedPacket)
	 */
	public const PACKET_ENCAPSULATED = 0x01;

	/*
	 * OPEN_SESSION payload:
	 * int32 (internal session ID)
	 * byte (address length)
	 * byte[] (address)
	 * short (port)
	 * long (clientID)
	 */
	public const PACKET_OPEN_SESSION = 0x02;

	/*
	 * CLOSE_SESSION payload:
	 * int32 (internal session ID)
	 * string (reason)
	 */
	public const PACKET_CLOSE_SESSION = 0x03;

	/*
	 * INVALID_SESSION payload:
	 * int32 (internal session ID)
	 */
	public const PACKET_INVALID_SESSION = 0x04;

	/* TODO: implement this
	 * SEND_QUEUE payload:
	 * int32 (internal session ID)
	 */
	public const PACKET_SEND_QUEUE = 0x05;

	/*
	 * ACK_NOTIFICATION payload:
	 * int32 (internal session ID)
	 * int32 (identifierACK)
	 */
	public const PACKET_ACK_NOTIFICATION = 0x06;

	/*
	 * SET_OPTION payload:
	 * byte (option name length)
	 * byte[] (option name)
	 * byte[] (option value)
	 */
	public const PACKET_SET_OPTION = 0x07;

	/*
	 * RAW payload:
	 * byte (address length)
	 * byte[] (address from/to)
	 * short (port)
	 * byte[] (payload)
	 */
	public const PACKET_RAW = 0x08;

	/*
	 * BLOCK_ADDRESS payload:
	 * byte (address length)
	 * byte[] (address)
	 * int (timeout)
	 */
	public const PACKET_BLOCK_ADDRESS = 0x09;

	/*
	 * UNBLOCK_ADDRESS payload:
	 * byte (address length)
	 * byte[] (address)
	 */
	public const PACKET_UNBLOCK_ADDRESS = 0x10;

	/*
	 * REPORT_PING payload:
	 * int32 (internal session ID)
	 * int32 (measured latency in MS)
	 */
	public const PACKET_REPORT_PING = 0x11;

	/*
	 * RAW_FILTER payload:
	 * byte[] (pattern)
	 */
	public const PACKET_RAW_FILTER = 0x12;

	/*
	 * UNLIMIT_ADDRESS payload:
	 * byte (address length)
	 * byte[] (address)
	 */
	public const PACKET_UNLIMIT_ADDRESS = 0x13;

	/*
	 * No payload
	 *
	 * Sends the disconnect message, removes sessions correctly, closes sockets.
	 */
	public const PACKET_SHUTDOWN = 0x7e;

	/*
	 * No payload
	 *
	 * Leaves everything as-is and halts, other Threads can be in a post-crash condition.
	 */
	public const PACKET_EMERGENCY_SHUTDOWN = 0x7f;
}


