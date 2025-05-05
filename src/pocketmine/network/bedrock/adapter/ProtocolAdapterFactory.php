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

use InvalidArgumentException;
use pocketmine\network\bedrock\adapter\v388\Protocol388Adapter;
use pocketmine\network\bedrock\adapter\v389\Protocol389Adapter;
use pocketmine\network\bedrock\adapter\v390\Protocol390Adapter;
use pocketmine\network\bedrock\adapter\v407\Protocol407Adapter;
use pocketmine\network\bedrock\adapter\v408\Protocol408Adapter;
use pocketmine\network\bedrock\adapter\v419\Protocol419Adapter;
use pocketmine\network\bedrock\adapter\v422\Protocol422Adapter;
use pocketmine\network\bedrock\adapter\v428\Protocol428Adapter;
use pocketmine\network\bedrock\adapter\v431\Protocol431Adapter;
use pocketmine\network\bedrock\adapter\v440\Protocol440Adapter;
use pocketmine\network\bedrock\adapter\v448\Protocol448Adapter;
use pocketmine\network\bedrock\adapter\v465\Protocol465Adapter;
use pocketmine\network\bedrock\adapter\v471\Protocol471Adapter;
use pocketmine\network\bedrock\adapter\v475\Protocol475Adapter;
use pocketmine\network\bedrock\adapter\v486\Protocol486Adapter;
use pocketmine\network\bedrock\adapter\v503\Protocol503Adapter;
use pocketmine\network\bedrock\adapter\v527\Protocol527Adapter;
use pocketmine\network\bedrock\adapter\v534\Protocol534Adapter;
use pocketmine\network\bedrock\adapter\v544\Protocol544Adapter;
use pocketmine\network\bedrock\adapter\v545\Protocol545Adapter;
use pocketmine\network\bedrock\adapter\v554\Protocol554Adapter;
use pocketmine\network\bedrock\adapter\v557\Protocol557Adapter;
use pocketmine\network\bedrock\adapter\v560\Protocol560Adapter;
use pocketmine\network\bedrock\adapter\v567\Protocol567Adapter;
use pocketmine\network\bedrock\adapter\v568\Protocol568Adapter;
use pocketmine\network\bedrock\adapter\v575\Protocol575Adapter;
use pocketmine\network\bedrock\adapter\v582\Protocol582Adapter;
use pocketmine\network\bedrock\adapter\v589\Protocol589Adapter;
use pocketmine\network\bedrock\adapter\v594\Protocol594Adapter;
use pocketmine\network\bedrock\adapter\v618\Protocol618Adapter;
use pocketmine\network\bedrock\adapter\v622\Protocol622Adapter;
use pocketmine\network\bedrock\adapter\v630\Protocol630Adapter;
use pocketmine\network\bedrock\adapter\v649\Protocol649Adapter;
use pocketmine\network\bedrock\adapter\v662\Protocol662Adapter;
use pocketmine\network\bedrock\adapter\v671\Protocol671Adapter;
use pocketmine\network\bedrock\adapter\v685\Protocol685Adapter;
use pocketmine\network\bedrock\protocol\ProtocolInfo;

final class ProtocolAdapterFactory{

	public const PROTOCOL_ADAPTERS = [
        //пока 1.6 - 1.12 :)
        388 => Protocol388Adapter::class, //1.13.0
        389 => Protocol389Adapter::class, //1.14.0
        390 => Protocol390Adapter::class, //1.14.60
		407 => Protocol407Adapter::class, //1.16.0
		408 => Protocol408Adapter::class, //1.16.20-60
		419 => Protocol419Adapter::class, //1.16.100
		422 => Protocol422Adapter::class, //1.16.200
		428 => Protocol428Adapter::class, //1.16.210
		431 => Protocol431Adapter::class, //1.16.220
		440 => Protocol440Adapter::class, //1.17.0
		448 => Protocol448Adapter::class, //1.17.10
		465 => Protocol465Adapter::class, //1.17.30
		471 => Protocol471Adapter::class, //1.17.40
		475 => Protocol475Adapter::class, //1.18.0
		486 => Protocol486Adapter::class, //1.18.10
		503 => Protocol503Adapter::class, //1.18.30
		527 => Protocol527Adapter::class, //1.19.0
		534 => Protocol534Adapter::class, //1.19.10
		544 => Protocol544Adapter::class, //1.19.20
		545 => Protocol545Adapter::class, //1.19.21
		554 => Protocol554Adapter::class, //1.19.30
		557 => Protocol557Adapter::class, //1.19.40
		560 => Protocol560Adapter::class, //1.19.50
		567 => Protocol567Adapter::class, //1.19.61
		568 => Protocol568Adapter::class, //1.19.63
		575 => Protocol575Adapter::class, //1.19.70
		582 => Protocol582Adapter::class, //1.19.80
		589 => Protocol589Adapter::class, //1.20.0
		594 => Protocol594Adapter::class, //1.20.10
		618 => Protocol618Adapter::class, //1.20.30
		622 => Protocol622Adapter::class, //1.20.40
        630 => Protocol630Adapter::class, //1.20.50
        649 => Protocol649Adapter::class, //1.20.60
        662 => Protocol662Adapter::class, //1.20.70
        671 => Protocol671Adapter::class, //1.20.80
        685 => Protocol685Adapter::class, //1.21.0
	];

	/** @var ProtocolAdapter[] */
	protected static ?array $protocolAdapters = null;

	public static function lazyInit() : void{
		if(self::$protocolAdapters === null){
			self::init();
		}
	}

	public static function init() : void{
		self::$protocolAdapters = [];

        foreach (self::PROTOCOL_ADAPTERS as $_ => $adapter) {
            self::register(new $adapter());
        }
	}

	/**
	 * @param ProtocolAdapter $adapter
	 */
	public static function register(ProtocolAdapter $adapter) : void{
		if($adapter->getProtocolVersion() === ProtocolInfo::CURRENT_PROTOCOL){
			throw new InvalidArgumentException("Can't register an adapter for current protocol version");
		}
		if(isset(self::$protocolAdapters[$adapter->getProtocolVersion()])) {
            throw new InvalidArgumentException("Can't override protocol adapter with version {$adapter->getProtocolVersion()}");
        }
		self::$protocolAdapters[$adapter->getProtocolVersion()] = $adapter;
        $adapter->initArgTypeMapper(); //пока что так, мб в будущем че нить и изменится....
	}

	/**
	 * @param int $protocolVersion
	 *
	 * @return ProtocolAdapter|null
	 */
	public static function get(int $protocolVersion) : ?ProtocolAdapter{
		$adapter = self::$protocolAdapters[$protocolVersion] ?? null;
		if ($adapter === NULL && isset(self::PROTOCOL_ADAPTERS[$protocolVersion])) {
			self::register($adapter = new (self::PROTOCOL_ADAPTERS[$protocolVersion]));
		}
		return $adapter;
	}

	/**
	 * @return ProtocolAdapter[]
	 */
	public static function getAll() : array{
		return self::$protocolAdapters;
	}

	private function __construct(){
		// oof
	}
}

