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

namespace pocketmine\network\mcpe\encryption;

use pocketmine\network\mcpe\JwtUtils;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use function igbinary_serialize;
use function igbinary_unserialize;
use function openssl_error_string;
use function openssl_pkey_get_details;
use function openssl_pkey_new;
use function random_bytes;

class PrepareEncryptionTask extends AsyncTask{

	private static $SERVER_PRIVATE_KEY = null;

	/** @var string */
	private $serverPrivateKey;
	/** @var string */
	private $serverPublickey;

	/** @var string|null */
	private $aesKey = null;
	/** @var string|null */
	private $handshakeJwt = null;
	/** @var string */
	private $clientPub;
	private $serverTokenRandom;

	/**
	 * @phpstan-param \Closure(string $encryptionKey, string $handshakeJwt) : void $onCompletion
	 */
	public function __construct(string $clientPub, \Closure $onCompletion){
		if(self::$SERVER_PRIVATE_KEY === null){
			$serverPrivateKey = openssl_pkey_new(["ec" => ["curve_name" => "secp384r1"]]);

			if($serverPrivateKey === false){
				throw new \RuntimeException("openssl_pkey_new() failed: " . openssl_error_string());
			}
			self::$SERVER_PRIVATE_KEY = $serverPrivateKey;
		}

		$this->serverPrivateKey = igbinary_serialize(openssl_pkey_get_details(self::$SERVER_PRIVATE_KEY));
		$this->clientPub = $clientPub;

		Server::getInstance()->getScheduler()->storeLocalComplex($this, $onCompletion);
	}

    /**
     * @throws \Exception
     */
	public function onRun() : void{
		/** @var mixed[] $serverPrivDetails */
		$serverPrivDetails = igbinary_unserialize($this->serverPrivateKey);
		$serverPriv = openssl_pkey_new($serverPrivDetails);

		if($serverPriv === false) throw new  \InvalidArgumentException("Failed to restore server signing key from details");

		$clientPub = JwtUtils::parseDerPublicKey($this->clientPub);
		$sharedSecret = EncryptionUtils::generateSharedSecret($serverPriv, $clientPub);
        $salt = random_bytes(16);
		$this->aesKey = EncryptionUtils::generateKey($sharedSecret, $salt);

		$derServPublicKey = JwtUtils::emitDerPublicKey($serverPriv);

		$this->serverPublickey = base64_encode($derServPublicKey);
		$this->handshakeJwt = EncryptionUtils::generateServerHandshakeJwt($derServPublicKey, $serverPriv, $salt);

		$this->serverTokenRandom = $salt;
	}

	public function onCompletion(Server $server) : void{
		/**
		 * @var \Closure $callback
		 * @phpstan-var \Closure(string $encryptionKey, string $handshakeJwt) : void $callback
		 */
		$callback = $this->fetchLocal();
		if($this->aesKey === null || $this->handshakeJwt === null){
			throw new \InvalidArgumentException("Something strange happened here ...");
		}
		$callback($this->aesKey, $this->handshakeJwt, $this->serverPublickey, $this->serverTokenRandom);
	}
}

