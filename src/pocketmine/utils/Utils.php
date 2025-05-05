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
 * Various Utilities used around the code
 */

namespace pocketmine\utils;

use DaveRandom\CallbackValidator\CallbackType;
use pocketmine\errorhandler\ErrorTypeToStringMap;
use pocketmine\thread\ThreadCrashInfoFrame;
use pocketmine\thread\ThreadManager;
use function bin2hex;
use function chunk_split;
use function count;
use function dechex;
use function exec;
use function fclose;
use function file;
use function file_exists;
use function file_get_contents;
use function get_class;
use function get_current_user;
use function get_loaded_extensions;
use function getenv;
use function gettype;
use function hexdec;
use function implode;
use function is_array;
use function is_object;
use function is_string;
use function memory_get_usage;
use function ord;
use function php_uname;
use function phpversion;
use function preg_grep;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function proc_close;
use function proc_open;
use function sha1;
use function socket_bind;
use function socket_close;
use function socket_create;
use function spl_object_id;
use function str_pad;
use function str_replace;
use function str_split;
use function stream_get_contents;
use function stripos;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use function sys_get_temp_dir;
use function trim;
use const AF_INET;
use const PHP_EOL;
use const PHP_INT_MAX;
use const PHP_INT_SIZE;
use const PHP_MAXPATHLEN;
use const SOCK_DGRAM;
use const SOL_UDP;
use const STR_PAD_LEFT;
use const STR_PAD_RIGHT;

/**
 * Big collection of functions
 */
class Utils{
    public const string CLEAN_PATH_SRC_PREFIX = "pmsrc";
    public const string CLEAN_PATH_PLUGINS_PREFIX = "plugins";

    public static $os;
    private static ?UUID $serverUniqueId = null;

    /**
     * Generates an unique identifier to a callable
     *
     * @phpstan-param anyCallable $variable
     */
    public static function getCallableIdentifier(callable $variable): string{
        if(is_array($variable)){
            return sha1(strtolower(spl_object_hash($variable[0])) . "::" . strtolower($variable[1]));
        }elseif(is_string($variable)){
            return sha1(strtolower($variable));
        }else{
            throw new AssumptionFailedError("Unhandled callable type");
        }
    }

	/**
	 * Gets this machine / server instance unique ID
	 * Returns a hash, the first 32 characters (or 16 if raw)
	 * will be an identifier that won't change frequently.
	 * The rest of the hash will change depending on other factors.
	 *
	 * @param string $extra optional, additional data to identify the machine
	 *
	 * @return UUID
	 */
	public static function getMachineUniqueId(string $extra = "") : UUID{
		if(self::$serverUniqueId !== null and $extra === ""){
			return self::$serverUniqueId;
		}

		$machine = php_uname("a");
		$machine .= file_exists("/proc/cpuinfo") ? implode(preg_grep("/(model name|Processor|Serial)/", file("/proc/cpuinfo"))) : "";
		$machine .= sys_get_temp_dir();
		$machine .= $extra;
		$os = Utils::getOS();
		if($os === "win"){
			@exec("ipconfig /ALL", $mac);
			$mac = implode("\n", $mac);
			if(preg_match_all("#Physical Address[. ]{1,}: ([0-9A-F\\-]{17})#", $mac, $matches)){
				foreach($matches[1] as $i => $v){
					if($v == "00-00-00-00-00-00"){
						unset($matches[1][$i]);
					}
				}
				$machine .= implode(" ", $matches[1]); //Mac Addresses
			}
		}elseif($os === "linux"){
			if(file_exists("/etc/machine-id")){
				$machine .= file_get_contents("/etc/machine-id");
			}else{
				@exec("ifconfig 2>/dev/null", $mac);
				$mac = implode("\n", $mac);
				if(preg_match_all("#HWaddr[ \t]{1,}([0-9a-f:]{17})#", $mac, $matches)){
					foreach($matches[1] as $i => $v){
						if($v == "00:00:00:00:00:00"){
							unset($matches[1][$i]);
						}
					}
					$machine .= implode(" ", $matches[1]); //Mac Addresses
				}
			}
		}elseif($os === "android"){
			$machine .= @file_get_contents("/system/build.prop");
		}elseif($os === "mac"){
			$machine .= `system_profiler SPHardwareDataType | grep UUID`;
		}
		$data = $machine . PHP_MAXPATHLEN;
		$data .= PHP_INT_MAX;
		$data .= PHP_INT_SIZE;
		$data .= get_current_user();
		foreach(get_loaded_extensions() as $ext){
			$data .= $ext . ":" . phpversion($ext);
		}

		$uuid = UUID::fromData($machine, $data);

		if($extra === ""){
			self::$serverUniqueId = $uuid;
		}

		return $uuid;
	}

	/**
	 * @deprecated
	 * @see Internet::getIP()
	 *
	 * @param bool $force default false, force IP check even when cached
	 *
	 * @return string|bool
	 */
	public static function getIP(bool $force = false){
		return Internet::getIP($force);
	}

	/**
	 * Returns a readable identifier for the class of the given object. Sanitizes class names for anonymous classes.
	 *
	 * @throws \ReflectionException
	 */
	public static function getNiceClassName(object $obj) : string{
		$reflect = new \ReflectionClass($obj);
		if($reflect->isAnonymous()){
			$filename = $reflect->getFileName();

			return "anonymous@" . ($filename !== false ?
					Filesystem::cleanPath($filename) . "#L" . $reflect->getStartLine() :
					"internal"
				);
		}

		return $reflect->getName();
	}

	/**
	 * @param mixed[][] $trace
	 * @phpstan-param list<array<string, mixed>> $trace
	 *
	 * @return string[]
	 */
	public static function printableTrace(array $trace, int $maxStringLength = 80) : array{
		$messages = [];
		for($i = 0; isset($trace[$i]); ++$i){
			$params = "";
			if(isset($trace[$i]["args"]) || isset($trace[$i]["params"])){
				if(isset($trace[$i]["args"])){
					$args = $trace[$i]["args"];
				}else{
					$args = $trace[$i]["params"];
				}
				/** @var mixed[] $args */

				$paramsList = [];
				$offset = 0;
				foreach($args as $argId => $value){
					$paramsList[] = ($argId === $offset ? "" : "$argId: ") . self::stringifyValueForTrace($value, $maxStringLength);
					$offset++;
				}
				$params = implode(", ", $paramsList);
			}
			$messages[] = "#$i " . (isset($trace[$i]["file"]) ? Filesystem::cleanPath($trace[$i]["file"]) : "") . "(" . (isset($trace[$i]["line"]) ? $trace[$i]["line"] : "") . "):" . (isset($trace[$i]["class"]) ? $trace[$i]["class"] . (($trace[$i]["type"] === "dynamic" || $trace[$i]["type"] === "->") ? "->" : "::") : "") . (is_array($trace[$i]) ? ($trace[$i]["function"] . "(" . Utils::printable($params) . ")") : "");
		}
		return $messages;
	}

	/**
	 * @phpstan-template TValue
	 * @phpstan-param TValue|false $value
	 * @phpstan-param string|\Closure() : string $context
	 * @phpstan-return TValue
	 */
	public static function assumeNotFalse(mixed $value, \Closure|string $context = "This should never be false") : mixed{
		if($value === false){
			throw new AssumptionFailedError("Assumption failure: " . (is_string($context) ? $context : $context()) . " (THIS IS A BUG)");
		}
		return $value;
	}

	/**
	 * Similar to {@link Utils::printableTrace()}, but associates metadata such as file and line number with each frame.
	 * This is used to transmit thread-safe information about crash traces to the main thread when a thread crashes.
	 *
	 * @param mixed[][] $rawTrace
	 * @phpstan-param list<array<string, mixed>> $rawTrace
	 *
	 * @return ThreadCrashInfoFrame[]
	 */
	public static function printableTraceWithMetadata(array $rawTrace, int $maxStringLength = 80) : array{
		$printableTrace = self::printableTrace($rawTrace, $maxStringLength);
		$safeTrace = [];
		foreach($printableTrace as $frameId => $printableFrame){
			$rawFrame = $rawTrace[$frameId];
			$safeTrace[$frameId] = new ThreadCrashInfoFrame(
				$printableFrame,
				$rawFrame["file"] ?? "unknown",
				$rawFrame["line"] ?? 0
			);
		}

		return $safeTrace;
	}

	/**
	 * @return mixed[][]
	 * @phpstan-return list<array<string, mixed>>
	 */
	public static function currentTrace(int $skipFrames = 0) : array{
		++$skipFrames; //omit this frame from trace, in addition to other skipped frames
		if(function_exists("xdebug_get_function_stack") && count($trace = @xdebug_get_function_stack()) !== 0){
			$trace = array_reverse($trace);
		}else{
			$e = new \Exception();
			$trace = $e->getTrace();
		}
		for($i = 0; $i < $skipFrames; ++$i){
			unset($trace[$i]);
		}
		return array_values($trace);
	}

	/**
	 * @return string[]
	 */
	public static function printableCurrentTrace(int $skipFrames = 0) : array{
		return self::printableTrace(self::currentTrace(++$skipFrames));
	}


	private static function printableExceptionMessage(\Throwable $e) : string{
		$errstr = preg_replace('/\s+/', ' ', trim($e->getMessage()));

		$errno = $e->getCode();
		if(is_int($errno)){
			try{
				$errno = ErrorTypeToStringMap::get($errno);
			}catch(\InvalidArgumentException $ex){
				//pass
			}
		}

		$errfile = $e->getFile();
		$errline = $e->getLine();

		return get_class($e) . ": \"$errstr\" ($errno) in \"$errfile\" at line $errline";
	}

	/**
	 * @param mixed[] $trace
	 * @return string[]
	 */
	public static function printableExceptionInfo(\Throwable $e, $trace = null) : array{
		if($trace === null){
			$trace = $e->getTrace();
		}

		$lines = [self::printableExceptionMessage($e)];
		$lines[] = "--- Stack trace ---";
		foreach(Utils::printableTrace($trace) as $line){
			$lines[] = "  " . $line;
		}
		for($prev = $e->getPrevious(); $prev !== null; $prev = $prev->getPrevious()){
			$lines[] = "--- Previous ---";
			$lines[] = self::printableExceptionMessage($prev);
			foreach(Utils::printableTrace($prev->getTrace()) as $line){
				$lines[] = "  " . $line;
			}
		}
		$lines[] = "--- End of exception information ---";
		return $lines;
	}

	private static function stringifyValueForTrace(mixed $value, int $maxStringLength) : string{
		return match(true){
			is_object($value) => "object " . self::getNiceClassName($value) . "#" . spl_object_id($value),
			is_array($value) => "array[" . count($value) . "]",
			is_string($value) => "string[" . strlen($value) . "] " . substr(Utils::printable($value), 0, $maxStringLength),
			is_bool($value) => $value ? "true" : "false",
			is_int($value) => "int " . $value,
			is_float($value) => "float " . $value,
			$value === null => "null",
			default => gettype($value) . " " . Utils::printable((string) $value)
		};
	}

    /**
     * Returns the current Operating System
     * Windows => win
     * MacOS => mac
     * iOS => ios
     * Android => android
     * Linux => Linux
     * BSD => bsd
     * Other => other
     *
     * @param bool $recalculate
     *
     * @return OS
     */
	public static function getOS(bool $recalculate = false) : OS{
		if(self::$os === null || $recalculate){
			$uname = php_uname("s");
            if(stripos($uname, "Darwin") !== false){
                if(strpos(php_uname("m"), "iP") === 0){
                    self::$os = OS::IOS;
                }else{
                    self::$os = OS::MACOS;
                }
            }elseif(stripos($uname, "Win") !== false || $uname === "Msys"){
                self::$os = OS::WINDOWS;
            }elseif(stripos($uname, "Linux") !== false){
                if(@file_exists("/system/build.prop")){
                    self::$os = OS::ANDROID;
                }else{
                    self::$os = OS::LINUX;
                }
            }elseif(stripos($uname, "BSD") !== false || $uname === "DragonFly"){
                self::$os = OS::BSD;
            }else{
                self::$os = OS::UNKNOWN;
            }
        }

        return self::$os;
    }

	/**
	 * Generator which forces array keys to string during iteration.
	 * This is necessary because PHP has an anti-feature where it casts numeric string keys to integers, leading to
	 * various crashes.
	 *
	 * @phpstan-template TKeyType of string
	 * @phpstan-template TValueType
	 * @phpstan-param array<TKeyType, TValueType> $array
	 * @phpstan-return \Generator<TKeyType, TValueType, void, void>
	 */
	public static function stringifyKeys(array $array) : \Generator{
		foreach($array as $key => $value){ // @phpstan-ignore-line - this is where we fix the stupid bullshit with array keys :)
			yield (string) $key => $value;
		}
	}

	/**
	 * @return int[]
	 */
	public static function getRealMemoryUsage() : array{
		$stack = 0;
		$heap = 0;

		if(Utils::getOS() === "linux" or Utils::getOS() === "android"){
			$mappings = file("/proc/self/maps");
			foreach($mappings as $line){
				if(preg_match("#([a-z0-9]+)\\-([a-z0-9]+) [rwxp\\-]{4} [a-z0-9]+ [^\\[]*\\[([a-zA-z0-9]+)\\]#", trim($line), $matches) > 0){
					if(strpos($matches[3], "heap") === 0){
						$heap += hexdec($matches[2]) - hexdec($matches[1]);
					}elseif(strpos($matches[3], "stack") === 0){
						$stack += hexdec($matches[2]) - hexdec($matches[1]);
					}
				}
			}
		}

		return [$heap, $stack];
	}

	/**
	 * @param bool $advanced
	 *
	 * @return int[]|int
	 */
	public static function getMemoryUsage(bool $advanced = false){
		$reserved = memory_get_usage();
		$VmSize = null;
		$VmRSS = null;
		if(Utils::getOS() === "linux" or Utils::getOS() === "android"){
			$status = file_get_contents("/proc/self/status");
			if(preg_match("/VmRSS:[ \t]+([0-9]+) kB/", $status, $matches) > 0){
				$VmRSS = $matches[1] * 1024;
			}

			if(preg_match("/VmSize:[ \t]+([0-9]+) kB/", $status, $matches) > 0){
				$VmSize = $matches[1] * 1024;
			}
		}

		//TODO: more OS

		if($VmRSS === null){
			$VmRSS = memory_get_usage();
		}

		if(!$advanced){
			return $VmRSS;
		}

		if($VmSize === null){
			$VmSize = memory_get_usage(true);
		}

		return [$reserved, $VmRSS, $VmSize];
	}

	public static function getThreadCount() : int{
		if(Utils::getOS() === "linux" or Utils::getOS() === "android"){
			if(preg_match("/Threads:[ \t]+([0-9]+)/", file_get_contents("/proc/self/status"), $matches) > 0){
				return (int) $matches[1];
			}
		}
		//TODO: more OS

		return count(ThreadManager::getInstance()->getAll()) + 3; //RakLib + MainLogger + Main Thread
	}

	/**
	 * @param bool $recalculate
	 * @return int
	 */
	public static function getCoreCount(bool $recalculate = false) : int{
		static $processors = 0;

		if($processors > 0 and !$recalculate){
			return $processors;
		}else{
			$processors = 0;
		}

		switch(Utils::getOS()){
			case "linux":
			case "android":
				if(file_exists("/proc/cpuinfo")){
					foreach(file("/proc/cpuinfo") as $l){
						if(preg_match('/^processor[ \t]*:[ \t]*[0-9]+$/m', $l) > 0){
							++$processors;
						}
					}
				}else{
					if(preg_match("/^([0-9]+)\\-([0-9]+)$/", trim(@file_get_contents("/sys/devices/system/cpu/present")), $matches) > 0){
						$processors = (int) ($matches[2] - $matches[1]);
					}
				}
				break;
			case "bsd":
			case "mac":
				$processors = (int) `sysctl -n hw.ncpu`;
				break;
			case "win":
				$processors = (int) getenv("NUMBER_OF_PROCESSORS");
				break;
		}
		return $processors;
	}

	/**
	 * Returns a prettified hexdump
	 *
	 * @param string $bin
	 *
	 * @return string
	 */
	public static function hexdump(string $bin) : string{
		$output = "";
		$bin = str_split($bin, 16);
		foreach($bin as $counter => $line){
			$hex = chunk_split(chunk_split(str_pad(bin2hex($line), 32, " ", STR_PAD_RIGHT), 2, " "), 24, " ");
			$ascii = preg_replace('#([^\x20-\x7E])#', ".", $line);
			$output .= str_pad(dechex($counter << 4), 4, "0", STR_PAD_LEFT) . "  " . $hex . " " . $ascii . PHP_EOL;
		}

		return $output;
	}


	/**
	 * Returns a string that can be printed, replaces non-printable characters
	 *
	 * @param mixed $str
	 *
	 * @return string
	 */
	public static function printable($str) : string{
		if(!is_string($str)){
			return gettype($str);
		}

		return preg_replace('#([^\x20-\x7E])#', '.', $str);
	}

	/*
	public static function angle3D($pos1, $pos2){
		$X = $pos1["x"] - $pos2["x"];
		$Z = $pos1["z"] - $pos2["z"];
		$dXZ = sqrt(pow($X, 2) + pow($Z, 2));
		$Y = $pos1["y"] - $pos2["y"];
		$hAngle = rad2deg(atan2($Z, $X) - M_PI_2);
		$vAngle = rad2deg(-atan2($Y, $dXZ));

		return array("yaw" => $hAngle, "pitch" => $vAngle);
	}*/

	/**
	 * @deprecated
	 * @see Internet::getURL()
	 *
	 * @param string  $page
	 * @param int     $timeout default 10
	 * @param array   $extraHeaders
	 * @param string  &$err    Will be set to the output of curl_error(). Use this to retrieve errors that occured during the operation.
	 * @param array[] &$headers
	 * @param int     &$httpCode
	 *
	 * @return bool|mixed false if an error occurred, mixed data if successful.
	 */
	public static function getURL(string $page, int $timeout = 10, array $extraHeaders = [], &$err = null, &$headers = null, &$httpCode = null){
		return Internet::getURL($page, $timeout, $extraHeaders, $err, $headers, $httpCode);
	}

	/**
	 * @deprecated
	 * @see Internet::postURL()
	 *
	 * @param string       $page
	 * @param array|string $args
	 * @param int          $timeout
	 * @param array        $extraHeaders
	 * @param string       &$err Will be set to the output of curl_error(). Use this to retrieve errors that occured during the operation.
	 * @param array[]      &$headers
	 * @param int          &$httpCode
	 *
	 * @return bool|mixed false if an error occurred, mixed data if successful.
	 */
	public static function postURL(string $page, $args, int $timeout = 10, array $extraHeaders = [], &$err = null, &$headers = null, &$httpCode = null){
		return Internet::postURL($page, $args, $timeout, $extraHeaders, $err, $headers, $httpCode);
	}

	/**
	 * @deprecated
	 * @see Internet::simpleCurl()
	 *
	 * @param string        $page
	 * @param float|int     $timeout      The maximum connect timeout and timeout in seconds, correct to ms.
	 * @param string[]      $extraHeaders extra headers to send as a plain string array
	 * @param array         $extraOpts    extra CURLOPT_* to set as an [opt => value] map
	 * @param callable|null $onSuccess    function to be called if there is no error. Accepts a resource argument as the cURL handle.
	 *
	 * @return array a plain array of three [result body : string, headers : array[], HTTP response code : int]. Headers are grouped by requests with strtolower(header name) as keys and header value as values
	 *
	 * @throws \RuntimeException if a cURL error occurs
	 */
	public static function simpleCurl(string $page, $timeout = 10, array $extraHeaders = [], array $extraOpts = [], callable $onSuccess = null){
		return Internet::simpleCurl($page, $timeout, $extraHeaders, $extraOpts, $onSuccess);
	}

	public static function javaStringHash(string $string) : int{
		$hash = 0;
		for($i = 0, $len = strlen($string); $i < $len; $i++){
			$ord = ord($string[$i]);
			if($ord & 0x80){
				$ord -= 0x100;
			}
			$hash = 31 * $hash + $ord;
			while($hash > 0x7FFFFFFF){
				$hash -= 0x100000000;
			}
			while($hash < -0x80000000){
				$hash += 0x100000000;
			}
			$hash &= 0xFFFFFFFF;
		}
		return $hash;
	}

	/**
	 * @param string      $command Command to execute
	 * @param string|null &$stdout Reference parameter to write stdout to
	 * @param string|null &$stderr Reference parameter to write stderr to
	 *
	 * @return int process exit code
	 */
	public static function execute(string $command, string &$stdout = null, string &$stderr = null) : int{
		$process = proc_open($command, [
			["pipe", "r"],
			["pipe", "w"],
			["pipe", "w"]
		], $pipes);

		if($process === false){
			$stderr = "Failed to open process";
			$stdout = "";

			return -1;
		}

		$stdout = stream_get_contents($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);

		foreach($pipes as $p){
			fclose($p);
		}

		return proc_close($process);
	}

	/**
	 * @param string $token
	 *
	 * @return mixed[]
	 */
	public static function decodeJWT(string $token) : array{
		[$headB64, $payloadB64, $sigB64] = explode(".", $token);

		$rawPayloadJSON = base64_decode(strtr($payloadB64, '-_', '+/'), true);
		if($rawPayloadJSON === false){
			throw new \InvalidArgumentException("Payload base64 is invalid and cannot be decoded");
		}
		$decodedPayload = json_decode($rawPayloadJSON, true);
		if(!is_array($decodedPayload)){
			throw new \InvalidArgumentException("Decoded payload should be array, " . gettype($decodedPayload) . " received");
		}

		return $decodedPayload;
	}

	public static function cleanPath($path){
		$pmPath = defined(\pocketmine\PATH) ? \pocketmine\PATH : "";
		$pluginPath = defined(\pocketmine\PLUGIN_PATH) ? \pocketmine\PLUGIN_PATH : "";
		return str_replace(["\\", ".php", "phar://", str_replace(["\\", "phar://"], ["/", ""], $pmPath), str_replace(["\\", "phar://"], ["/", ""], $pluginPath)], ["/", "", "", "", ""], $path);
	}

	/**
	 * @param int $port
	 * 
	 * @return bool
	 */
	public static function isPortAvailable(int $port) : bool{
		$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		$res = @socket_bind($sock, "0.0.0.0", $port) === true;
		socket_close($sock);
		return $res;
	}

	/**
	 * Reallocates memory for array. Takes a bit of time.
	 *
	 * @param array &$array
	 */
	public static function reallocateArray(array &$array) : void{
		$old_array = $array;
		$array = [];
		foreach($old_array as $k => $v){
			if(is_array($v)){
				self::reallocateArray($v);
			}
			$array[$k] = $v;
		}
	}

	/**
	 * Verifies that the given callable is compatible with the desired signature. Throws a TypeError if they are
	 * incompatible.
	 *
	 * @param callable $signature Dummy callable with the required parameters and return type
	 * @param callable $subject Callable to check the signature of
	 *
	 * @throws \TypeError
	 */
	public static function validateCallableSignature(callable $signature, callable $subject) : void{
		if(!self::checkCallableSignature($signature, $subject)){
			throw new \TypeError("Declaration of callable `" . CallbackType::createFromCallable($subject) . "` must be compatible with `" . CallbackType::createFromCallable($signature) . "`");
		}
	}

	/**
	 * Checks that the given callable is compatible with the desired signature.
	 *
	 * @param callable $signature Dummy callable with the required parameters and return type
	 * @param callable $subject Callable to check the signature of
	 *
	 * @return bool
	 */
	public static function checkCallableSignature(callable $signature, callable $subject) : bool{
		try{
			return CallbackType::createFromCallable($signature)->isSatisfiedBy($subject);
		}catch(\Throwable $e){
			return false;
		}
	}

	/**
	 * Returns a readable identifier for the given Closure, including file and line.
	 *
	 * @param \Closure $closure
	 *
	 * @return string
	 * @throws \ReflectionException
	 */
	public static function getNiceClosureName(\Closure $closure) : string{
		$func = new \ReflectionFunction($closure);
		if(substr($func->getName(), -strlen('{closure}')) !== '{closure}'){
			//closure wraps a named function, can be done with reflection or fromCallable()
			//isClosure() is useless here because it just tells us if $func is reflecting a Closure object

			$scope = $func->getClosureScopeClass();
			if($scope !== null){ //class method
				return
					$scope->getName() .
					($func->getClosureThis() !== null ? "->" : "::") .
					$func->getName(); //name doesn't include class in this case
			}

			//non-class function
			return $func->getName();
		}
		return "closure@" . Filesystem::cleanPath($func->getFileName()) . "#L" . $func->getStartLine();
	}

	/**
	 * @param int $severity
	 * @param string $message
	 * @param string $file
	 * @param int $line
	 *
	 * @return bool
	 * @throws \ErrorException
	 */
	public static function errorExceptionHandler(int $severity, string $message, string $file, int $line) : bool{
		if(error_reporting() & $severity){
			throw new \ErrorException($message, 0, $severity, $file, $line);
		}

		return true; //stfu operator
	}
}


