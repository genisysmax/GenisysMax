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

use pmmp\thread\ThreadSafeArray;
use pocketmine\errorhandler\ErrorToExceptionHandler;
use pocketmine\Server;
use function error_get_last;
use function error_reporting;
use function implode;
use function register_shutdown_function;
use function set_exception_handler;

trait CommonThreadPartsTrait{
    /**
     * @var ThreadSafeArray|ThreadSafeClassLoader[]|null
     * @phpstan-var ThreadSafeArray<int, ThreadSafeClassLoader>|null
     */
    private ?ThreadSafeArray $classLoaders = null;
    protected ?string $composerAutoloaderPath = null;
	protected ?ThreadCrashInfo $crashInfo = null;

	/** @var bool */
	protected $isKilled = false;

	/**
	 * @return ThreadCrashInfo|null
	 */
	public function getCrashInfo() : ?ThreadCrashInfo{
		return $this->crashInfo;
	}

    /**
     * @return ThreadSafeClassLoader[]
     */
    public function getClassLoaders() : ?array{
        return $this->classLoaders !== null ? (array) $this->classLoaders : null;
    }

    /**
     * @param ThreadSafeClassLoader[] $autoloaders
     */
    public function setClassLoaders(?array $autoloaders = null) : void{
        $this->composerAutoloaderPath = \pocketmine\COMPOSER_AUTOLOADER_PATH;

        if($autoloaders === null){
            $autoloaders = [Server::getInstance()->getLoader()];
        }

        if($this->classLoaders === null){
            $loaders = $this->classLoaders = new ThreadSafeArray();
        }else{
            $loaders = $this->classLoaders;
            foreach($this->classLoaders as $k => $autoloader){
                unset($this->classLoaders[$k]);
            }
        }
        foreach($autoloaders as $autoloader){
            $loaders[] = $autoloader;
        }
    }

    /**
     * Registers the class loaders for this thread.
     *
     * @internal
     */
    public function registerClassLoaders() : void{
        if($this->composerAutoloaderPath !== null){
            require $this->composerAutoloaderPath;
        }
        $autoloaders = $this->classLoaders;
        if($autoloaders !== null){
            foreach($autoloaders as $autoloader){
                /** @var ThreadSafeClassLoader $autoloader */
                $autoloader->register(false);
            }
        }
    }

	final public function run() : void{
		error_reporting(-1);
		$this->registerClassLoaders();
		//set this after the autoloader is registered
		ErrorToExceptionHandler::set();
		set_exception_handler($this->onUncaughtException(...));
		register_shutdown_function($this->onShutdown(...));

		$this->onRun();
		$this->isKilled = true;
	}

	/**
	 * Called by set_exception_handler() when an uncaught exception is thrown.
	 */
	protected function onUncaughtException(\Throwable $e) : void{
		$this->synchronized(function() use ($e) : void{
			$this->crashInfo = ThreadCrashInfo::fromThrowable($e, $this->getThreadName());
			\GlobalLogger::get()->logException($e);
		});
	}

	/**
	 * Called by register_shutdown_function() when the thread shuts down. This may be because of a benign shutdown, or
	 * because of a fatal error. Use isKilled to determine which.
	 */
	protected function onShutdown() : void{
		$this->synchronized(function() : void{
			if(!$this->isTerminated() && $this->crashInfo === null){
				$last = error_get_last();
				if($last !== null){
					//fatal error
					$crashInfo = ThreadCrashInfo::fromLastErrorInfo($last, $this->getThreadName());
				}else{
					//probably misused exit()
					//$crashInfo = ThreadCrashInfo::fromThrowable(new \RuntimeException("Thread crashed without an error - perhaps exit() was called?"), $this->getThreadName());
					return;
				}
				$this->crashInfo = $crashInfo;

				$lines = [];
				//mimic exception printed format
				$lines[] = "Fatal error: " . $crashInfo->makePrettyMessage();
				$lines[] = "--- Stack trace ---";
				foreach($crashInfo->getTrace() as $frame){
					$lines[] = "  " . $frame->getPrintableFrame();
				}
				$lines[] = "--- End of fatal error information ---";
				\GlobalLogger::get()->critical(implode("\n", $lines));
			}
		});
	}

	/**
	 * Runs code on the thread.
	 */
	abstract protected function onRun() : void;

	public function getThreadName() : string{
		return (new \ReflectionClass($this))->getShortName();
	}
}


