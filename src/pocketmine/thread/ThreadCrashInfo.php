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

namespace pocketmine\thread;

use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;
use pocketmine\errorhandler\ErrorTypeToStringMap;
use pocketmine\utils\Filesystem;
use pocketmine\utils\Utils;
use function get_class;
use function sprintf;

final class ThreadCrashInfo extends ThreadSafe{

	/** @phpstan-var ThreadSafeArray<int, ThreadCrashInfoFrame> */
	private ThreadSafeArray $trace;

	/**
	 * @param ThreadCrashInfoFrame[] $trace
	 */
	public function __construct(
		private string $type,
		private string $message,
		private string $file,
		private int $line,
		array $trace,
		private string $threadName
	){
		$this->trace = ThreadSafeArray::fromArray($trace);
	}

	public static function fromThrowable(\Throwable $e, string $threadName) : self{
		return new self(get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(), Utils::printableTraceWithMetadata($e->getTrace()), $threadName);
	}

	/**
	 * @phpstan-param array{type: int, message: string, file: string, line: int} $info
	 */
	public static function fromLastErrorInfo(array $info, string $threadName) : self{
		try{
			$class = ErrorTypeToStringMap::get($info["type"]);
		}catch(\InvalidArgumentException){
			$class = "Unknown error type (" . $info["type"] . ")";
		}
		return new self($class, $info["message"], $info["file"], $info["line"], Utils::printableTraceWithMetadata(Utils::currentTrace()), $threadName);
	}

	public function getType() : string{ return $this->type; }

	public function getMessage() : string{ return $this->message; }

	public function getFile() : string{ return $this->file; }

	public function getLine() : int{ return $this->line; }

	/**
	 * @return ThreadCrashInfoFrame[]
	 */
	public function getTrace() : array{
		return (array) $this->trace;
	}

	public function getThreadName() : string{ return $this->threadName; }

	public function makePrettyMessage() : string{
		return sprintf("%s: \"%s\" in \"%s\" on line %d", $this->type ?? "Fatal error", $this->message, Filesystem::cleanPath($this->file), $this->line);
	}
}

