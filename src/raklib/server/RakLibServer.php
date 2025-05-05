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

use pmmp\thread\ThreadSafeArray;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\thread\log\ThreadSafeLogger;
use pocketmine\thread\NonThreadSafeValue;
use pocketmine\thread\Thread;
use pocketmine\thread\ThreadCrashException;
use pocketmine\thread\ThreadSafeClassLoader;
use raklib\generic\Socket;
use raklib\RakLib;
use raklib\utils\InternetAddress;
use function array_reverse;
use function error_reporting;
use function function_exists;
use function gc_enable;
use function get_class;
use function getcwd;
use function gettype;
use function ini_set;
use function is_object;
use function method_exists;
use function mt_rand;
use function realpath;
use function str_replace;
use function strval;
use function substr;
use function xdebug_get_function_stack;
use const DIRECTORY_SEPARATOR;
use const PHP_INT_MAX;
use const PTHREADS_INHERIT_NONE;

//use pmmp\thread\Thread as NativeThread;

class RakLibServer extends Thread{
	/** @var NonThreadSafeValue */
	private NonThreadSafeValue $address;

	/** @var ThreadSafeLogger */
	protected ThreadSafeLogger $logger;

	/** @var ThreadSafeClassLoader */
	protected ThreadSafeClassLoader $classLoader;

	/** @var bool */
	protected bool $shutdown = false;
	/** @var bool */
	protected bool $ready = false;

	/** @var ThreadSafeArray */
	protected ThreadSafeArray $externalQueue;
	/** @var ThreadSafeArray */
	protected ThreadSafeArray $internalQueue;

	/** @var string */
	protected string $mainPath;

	/** @var int */
	protected int $serverId = 0;
	/** @var int */
	protected int $maxMtuSize;
	/** @var \Volatile|int[] */
	private NonThreadSafeValue $protocolVersions;

	/** @var SleeperNotifier|null */
	protected $mainThreadNotifier;
	/** @var SleeperNotifier|null */
	protected $internalThreadNotifier;

	/**
	 * @param ThreadSafeLogger             $logger
	 * @param ThreadSafeClassLoader        $classLoader
	 * @param InternetAddress              $address
	 * @param int                          $maxMtuSize
	 * @param int[]                        $protocolVersions
	 * @param SleeperNotifier|null         $sleeper
	 */
	public function __construct(ThreadSafeLogger $logger, ThreadSafeClassLoader $classLoader, InternetAddress $address, int $maxMtuSize = 1492, array $protocolVersions = [], ?SleeperNotifier $sleeper = null){
		$this->address = new NonThreadSafeValue($address);

		$this->serverId = mt_rand(0, PHP_INT_MAX);
		$this->maxMtuSize = $maxMtuSize;

		$this->logger = $logger;
		$this->classLoader = $classLoader;

		$this->externalQueue = new ThreadSafeArray;
		$this->internalQueue = new ThreadSafeArray;

		if(\Phar::running(true) !== ""){
			$this->mainPath = \Phar::running(true);
		}else{
			$this->mainPath = realpath(getcwd()) . DIRECTORY_SEPARATOR;
		}

		$this->protocolVersions = new NonThreadSafeValue(count($protocolVersions) === 0 ? [RakLib::DEFAULT_PROTOCOL_VERSION] : $protocolVersions);

		$this->mainThreadNotifier = $sleeper;
	}

	public function isShutdown() : bool{
		return $this->shutdown === true;
	}

	public function shutdown() : void{
		$this->shutdown = true;
	}

	/**
	 * Returns the RakNet server ID
	 * @return int
	 */
	public function getServerId() : int{
		return $this->serverId;
	}

	public function getProtocolVersions() : array{
		return $this->protocolVersions->deserialize();
	}

	/**
	 * @return ThreadSafeLogger
	 */
	public function getLogger() : ThreadSafeLogger{
		return $this->logger;
	}

	/**
	 * @return ThreadSafeArray
	 */
	public function getExternalQueue() : ThreadSafeArray{
		return $this->externalQueue;
	}

	/**
	 * @return ThreadSafeArray
	 */
	public function getInternalQueue() : ThreadSafeArray{
		return $this->internalQueue;
	}

	public function pushMainToThreadPacket(string $str) : void{
		$this->internalQueue[] = $str;
		if($this->internalThreadNotifier !== null){
			$this->internalThreadNotifier->wakeupSleeper();
		}
	}

	public function readMainToThreadPacket() : ?string{
		return $this->internalQueue->shift();
	}

	public function pushThreadToMainPacket(string $str) : void{
		$this->externalQueue[] = $str;
		if($this->mainThreadNotifier !== null){
			$this->mainThreadNotifier->wakeupSleeper();
		}
	}

	public function readThreadToMainPacket() : ?string{
		return $this->externalQueue->shift();
	}

	public function getTrace($start = 0, $trace = null){
		if($trace === null){
			if(function_exists("xdebug_get_function_stack")){
				$trace = array_reverse(xdebug_get_function_stack());
			}else{
				$e = new \Exception();
				$trace = $e->getTrace();
			}
		}

		$messages = [];
		$j = 0;
		for($i = (int) $start; isset($trace[$i]); ++$i, ++$j){
			$params = "";
			if(isset($trace[$i]["args"]) or isset($trace[$i]["params"])){
				if(isset($trace[$i]["args"])){
					$args = $trace[$i]["args"];
				}else{
					$args = $trace[$i]["params"];
				}
				foreach($args as $name => $value){
					$params .= (is_object($value) ? get_class($value) . " " . (method_exists($value, "__toString") ? $value->__toString() : "object") : gettype($value) . " " . @strval($value)) . ", ";
				}
			}
			$messages[] = "#$j " . (isset($trace[$i]["file"]) ? $this->cleanPath($trace[$i]["file"]) : "") . "(" . (isset($trace[$i]["line"]) ? $trace[$i]["line"] : "") . "): " . (isset($trace[$i]["class"]) ? $trace[$i]["class"] . (($trace[$i]["type"] === "dynamic" or $trace[$i]["type"] === "->") ? "->" : "::") : "") . $trace[$i]["function"] . "(" . substr($params, 0, -2) . ")";
		}

		return $messages;
	}

	public function cleanPath($path){
		return str_replace(["\\", ".php", "phar://", str_replace(["\\", "phar://"], ["/", ""], $this->mainPath)], ["/", "", "", ""], $path);
	}

	public function startAndWait(int $options = PTHREADS_INHERIT_NONE) : void{
		$this->start($options);
		$this->synchronized(function(){
			while(!$this->ready and $this->crashInfo === null){
				$this->wait();
			}
			$crashInfo = $this->getCrashInfo();
			if($crashInfo !== null){
				if ($crashInfo->getType() === SocketException::class) {
					throw new SocketException($crashInfo->getMessage());
				}
				throw new ThreadCrashException("RakLib failed to start", $crashInfo);
			}
		});
	}

	public function onRun() : void{
		if($this->classLoader !== null){
			$this->classLoader->register(true);
		}

		gc_enable();
		ini_set("memory_limit", '-1');

		error_reporting(-1);
		ini_set("display_errors", '1');
		ini_set("display_startup_errors", '1');
		\GlobalLogger::set($this->logger);

		$socket = new Socket($this->address->deserialize());

		$internalThreadNotifier = new SleeperNotifier();
		$manager = new SessionManager($this, $socket, $this->maxMtuSize, $internalThreadNotifier);
		$this->internalThreadNotifier = $internalThreadNotifier;
		$this->synchronized(function(){
			$this->ready = true;
			$this->notify();
		});
		
		while(!$this->isShutdown()) {
			$manager->tickProcessor();
		}
		$manager->waitShutdown();
	}
}

