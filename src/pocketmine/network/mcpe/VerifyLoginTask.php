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

namespace pocketmine\network\mcpe;

use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use function base64_decode;
use function chr;
use function count;
use function explode;
use function igbinary_serialize;
use function igbinary_unserialize;
use function json_decode;
use function ltrim;
use function openssl_verify;
use function ord;
use function str_split;
use function strlen;
use function strtr;
use function time;
use function wordwrap;
use const OPENSSL_ALGO_SHA384;

class VerifyLoginTask extends AsyncTask{

	/**
    * Old Mojang root auth key. This was used since the introduction of Xbox Live authentication in 0.15.0.
    * This key is expected to be replaced by the key below in the future, but this has not yet happened as of
    * 2023-07-01.
    * Ideally we would place a time expiry on this key, but since Mojang have not given a hard date for the key change,
    * and one bad guess has already caused a major outage, we can't do this.
    * TODO: This needs to be removed as soon as the new key is deployed by Mojang's authentication servers.
    */
	public const MOJANG_OLD_ROOT_PUBLIC_KEY = "MHYwEAYHKoZIzj0CAQYFK4EEACIDYgAE8ELkixyLcwlZryUQcu1TvPOmI2B7vX83ndnWRUaXm74wFfa5f/lwQNTfrLVHa2PmenpGI6JhIMUJaWZrjmMj90NoKNFSNBuKdm8rYiXsfaz3K36x/1U26HpG0ZxK/V1V";

	/**
    * New Mojang root auth key. Mojang notified third-party developers of this change prior to the release of 1.20.0.
    * Expectations were that this would be used starting a "couple of weeks" after the release, but as of 2023-07-01,
    * it has not yet been deployed.
    */
	public const MOJANG_ROOT_PUBLIC_KEY = "MHYwEAYHKoZIzj0CAQYFK4EEACIDYgAECRXueJeTDqNRRgJi/vlRufByu/2G0i2Ebt6YMar5QX/R0DIIyrJMcUpruK4QveTfJSTp3Shlq4Gk34cD/4GUWwkv0DVuzeuB+tXija7HBxii03NHDbPAD0AKnLr2wdAp";

	private const CLOCK_DRIFT_MAX = 60 * 60 * 24 * 7;

	/** @var string */
	protected $chainJwts;
	/** @var string */
	protected $clientDataJwt;

	/**
	 * @var string|null
	 * Whether the keychain signatures were validated correctly. This will be set to an error message if any link in the
	 * keychain is invalid for whatever reason (bad signature, not in nbf-exp window, etc). If this is non-null, the
	 * keychain might have been tampered with. The player will always be disconnected if this is non-null.
	 */
	protected $error = "Unknown";
	/**
	 * @var bool
	 * Whether the player is logged into Xbox Live. This is true if any link in the keychain is signed with the Mojang
	 * root public key.
	 */
	protected $authenticated = false;

	public function __construct(Player $player, LoginPacket $packet){
		$this->chainJwts = igbinary_serialize($packet->chainData["chain"]);
		$this->clientDataJwt = $packet->clientDataJwt;
		parent::__construct([$player, $packet]);
	}

	public function onRun(){
		/** @var string[] $chainJwts */
		$chainJwts = igbinary_unserialize($this->chainJwts); //Get it in a local variable to make sure it stays unserialized

		try{
			$currentKey = null;
			$first = true;

			foreach($chainJwts as $jwt){
				$this->validateToken($jwt, $currentKey, $first);
				$first = false;
			}

			$this->validateToken($this->clientDataJwt, $currentKey);

			$this->error = null;
		}catch(VerifyLoginException $e){
			$this->error = $e->getMessage();
		}
	}

	/**
	 * @param string $jwt
	 * @param string|null $currentPublicKey
	 * @param bool $first
	 */
	private function validateToken(string $jwt, ?string &$currentPublicKey, bool $first = false) : void{
		$rawParts = explode('.', $jwt);
		if(count($rawParts) !== 3){
			throw new VerifyLoginException("Wrong number of JWT parts, expected 3, got " . count($rawParts));
		}
		[$headB64, $payloadB64, $sigB64] = $rawParts;

		$headers = json_decode(base64_decode(strtr($headB64, '-_', '+/'), true), true);

		if($currentPublicKey === null){
			if(!$first){
				throw new VerifyLoginException("Previous keychain link does not have expected public key.");
			}

			//First link, check that it is self-signed
			$currentPublicKey = $headers["x5u"];
		}elseif($headers["x5u"] !== $currentPublicKey){
			//Fast path: if the header key doesn't match what we expected, the signature isn't going to validate anyway
			throw new VerifyLoginException("Failed to verify keychain link signature.");
		}

		$plainSignature = base64_decode(strtr($sigB64, '-_', '+/'), true);

		//OpenSSL wants a DER-encoded signature, so we extract R and S from the plain signature and crudely serialize it.

		if(strlen($plainSignature) !== 96){
			throw new VerifyLoginException("Wrong signature length, expected 96, got " . strlen($plainSignature));
		}

		[$rString, $sString] = str_split($plainSignature, 48);

		$rString = ltrim($rString, "\x00");
		if(ord($rString[0]) >= 128){ //Would be considered signed, pad it with an extra zero
			$rString = "\x00" . $rString;
		}

		$sString = ltrim($sString, "\x00");
		if(ord($sString[0]) >= 128){ //Would be considered signed, pad it with an extra zero
			$sString = "\x00" . $sString;
		}

		//0x02 = Integer ASN.1 tag
		$sequence = "\x02" . chr(strlen($rString)) . $rString . "\x02" . chr(strlen($sString)) . $sString;
		//0x30 = Sequence ASN.1 tag
		$derSignature = "\x30" . chr(strlen($sequence)) . $sequence;

		$v = openssl_verify("$headB64.$payloadB64", $derSignature, "-----BEGIN PUBLIC KEY-----\n" . wordwrap($currentPublicKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----\n", OPENSSL_ALGO_SHA384);
		if($v !== 1){
			throw new VerifyLoginException("Failed to verify keychain link signature.");
		}

		if($currentPublicKey === self::MOJANG_ROOT_PUBLIC_KEY || $currentPublicKey === self::MOJANG_OLD_ROOT_PUBLIC_KEY){
			$this->authenticated = true; //we're signed into xbox live
		}

		$claims = json_decode(base64_decode(strtr($payloadB64, '-_', '+/'), true), true);

		$time = time();
		if(isset($claims["nbf"]) and $claims["nbf"] > $time + self::CLOCK_DRIFT_MAX){
			throw new VerifyLoginException("Token can't be used yet - check the server's date/time matches the client.");
		}

		if(isset($claims["exp"]) and $claims["exp"] < $time - self::CLOCK_DRIFT_MAX){
			throw new VerifyLoginException("Token has expired - check the server's date/time matches the client.");
		}

		$currentPublicKey = $claims["identityPublicKey"] ?? null; //if there are further links, the next link should be signed with this
	}

	public function onCompletion(Server $server){
		/**
		 * @var Player $player
		 * @var LoginPacket $packet
		 */
		[$player, $packet] = $this->fetchLocal();
		if(!$player->isConnected()){
			$server->getLogger()->error("Player " . $player->getName() . " was disconnected before their login could be verified");
		}else{
			$player->onVerifyCompleted($packet, $this->error, $this->authenticated);
		}
	}
}


